<?php

namespace Codebuster\GptBundle\Controller;

use Codebuster\GptBundle\Classes\GptClass;
use Contao\Config;
use Contao\System;
use JsonException;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/_gpt', name: GptController::class, defaults: ['_scope' => 'backend', '_token_check' => true])]
class GptController
{
    private const DEFAULT_MODEL = 'gpt-5.6-luna';
    private const DEFAULT_TITLE_PROMPT = 'Write a concise and compelling SEO page title of 5 to 6 words for the supplied page content. Return only the title.';
    private const DEFAULT_DESCRIPTION_PROMPT = 'Write a clear and appealing SEO meta description of no more than 160 characters including spaces for the supplied page content. Return only the description.';
    private const SEO_LANGUAGE_INSTRUCTION = 'MANDATORY OUTPUT LANGUAGE: Detect the predominant language inside <page_content> and write the entire SEO output only in that language. Do not use the language of the prompt unless it matches the page content.';
    private const SEO_CONFIGURED_LANGUAGE_INSTRUCTION = 'MANDATORY OUTPUT LANGUAGE: The page language configured in the CMS is <%s>. Write the entire SEO output only in this language. This configured language is authoritative; do not infer the output language from the prompt or page content.';
    private const DEFAULT_TEMPERATURE = 0.5;
    private const DEFAULT_MAX_TOKENS = 300;
    private const SUPPORTED_MODELS = [
        'gpt-5.6-luna',
        'gpt-5.6-terra',
        'gpt-5.6-sol',
        'gpt-5.4-mini',
        'gpt-5.4-nano',
        'gpt-5.4',
        'gpt-5-mini',
        'gpt-4.1-mini',
    ];

    public function __invoke(Request $request): Response
    {
        $tokenChecker = System::getContainer()->get('contao.security.token_checker');

        if (!$tokenChecker->hasBackendUser()) {
            return $this->errorResponse('You must be logged in to the Contao back end.', Response::HTTP_FORBIDDEN);
        }

        $mode = (string) $request->query->get('mode', '');

        // Custom modes (e.g. "keywords") can be provided through the gptGetPrompt hook
        if ($mode !== 'tinymce' && !preg_match('/^[a-z][a-z0-9_]*$/', $mode)) {
            return $this->errorResponse('Unknown generation mode.', Response::HTTP_BAD_REQUEST);
        }

        $token = trim((string) Config::get('gpt_token'));

        if ($token === '') {
            return $this->errorResponse('Please add an OpenAI API key in the OpenAI settings.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $language = $this->getLanguage($request, $mode);
            $prompt = $this->getPrompt($request, $mode, $language);

            if ($prompt === '' && !in_array($mode, ['title', 'description', 'tinymce'], true)) {
                return $this->errorResponse('Unknown generation mode.', Response::HTTP_BAD_REQUEST);
            }

            $content = $this->getContent($request, $mode, $language);

            if ($prompt === '') {
                return $this->errorResponse('Please define a prompt in the OpenAI settings.', Response::HTTP_BAD_REQUEST);
            }

            if ($mode !== 'tinymce' && $content === '') {
                return $this->errorResponse('No page content was found to generate SEO metadata from.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return new JsonResponse([
                'content' => $this->doRequest($token, $prompt, $content, $language),
                'success' => true,
            ]);
        } catch (RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), Response::HTTP_BAD_GATEWAY);
        } catch (Throwable $exception) {
            return $this->errorResponse('The content could not be generated. Please check the OpenAI settings and try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getContent(Request $request, string $mode, string $language): string
    {
        if ($mode === 'tinymce') {
            return '';
        }

        $id = $request->query->getInt('id');
        $table = (string) $request->query->get('table', '');

        if ($id < 1 || $table === '') {
            throw new RuntimeException('The page or content source is missing.');
        }

        $content = $this->executeHook('gptGetContent', [$table, $id, $mode, $language]);

        if ($content !== null) {
            return trim($content);
        }

        return trim(GptClass::getContent($table, $id));
    }

    private function getPrompt(Request $request, string $mode, string $language): string
    {
        if ($mode === 'tinymce') {
            return trim((string) $request->query->get('prompt', ''));
        }

        $prompt = $this->executeHook('gptGetPrompt', [
            $mode,
            (string) $request->query->get('table', ''),
            $request->query->getInt('id'),
            $language,
        ]);

        if ($prompt !== null && trim($prompt) !== '') {
            return trim($prompt);
        }

        if (!in_array($mode, ['title', 'description'], true)) {
            return '';
        }

        $setting = $mode === 'title' ? 'gpt_title_prompt' : 'gpt_desc_prompt';
        $default = $mode === 'title' ? self::DEFAULT_TITLE_PROMPT : self::DEFAULT_DESCRIPTION_PROMPT;
        $prompt = trim((string) Config::get($setting));

        return $prompt !== '' ? $prompt : $default;
    }

    /**
     * Resolves the language configured on the page's website root, so it can
     * be enforced as the mandatory SEO output language.
     */
    private function getLanguage(Request $request, string $mode): string
    {
        if ($mode === 'tinymce' || (string) $request->query->get('table', '') !== 'tl_page') {
            return '';
        }

        return GptClass::getPageLanguage($request->query->getInt('id'));
    }

    /**
     * Returns the first string returned by a hook listener, null if no
     * listener handles the request.
     */
    private function executeHook(string $name, array $arguments): ?string
    {
        System::getContainer()->get('contao.framework')->initialize();

        foreach ($GLOBALS['TL_HOOKS'][$name] ?? [] as $callback) {
            $result = is_callable($callback)
                ? $callback(...$arguments)
                : System::importStatic($callback[0])->{$callback[1]}(...$arguments);

            if (is_string($result)) {
                return $result;
            }
        }

        return null;
    }

    private function doRequest(string $token, string $prompt, string $content, string $language = ''): string
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        $model = $this->getModel();
        $messages = $this->buildMessages($prompt, $content, $language);
        $postData = [
            'model' => $model,
            'messages' => $messages,
            'max_completion_tokens' => $this->getMaxTokens(),
            'temperature' => $this->getTemperature(),
        ];

        // GPT-5.4 and GPT-5.6 support sampling parameters when reasoning is disabled.
        if (preg_match('/^gpt-5\.(?:4|6)(?:-|$)/', $model)) {
            $postData['reasoning_effort'] = 'none';
        }

        try {
            $payload = json_encode($postData, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('The OpenAI request could not be encoded.', 0, $exception);
        }

        if (!function_exists('curl_init')) {
            throw new RuntimeException('The PHP cURL extension is required to connect to OpenAI.');
        }

        $curl = curl_init($url);

        if ($curl === false) {
            throw new RuntimeException('The OpenAI connection could not be initialized.');
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($curl);
        $curlError = curl_error($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($response === false) {
            throw new RuntimeException('OpenAI could not be reached' . ($curlError !== '' ? ': ' . $curlError : '.'));
        }

        try {
            $responseData = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('OpenAI returned an unreadable response.', 0, $exception);
        }

        if ($statusCode < 200 || $statusCode >= 300 || isset($responseData['error'])) {
            $message = $responseData['error']['message'] ?? 'OpenAI rejected the request.';
            throw new RuntimeException(sprintf('OpenAI error%s: %s', $statusCode > 0 ? ' ' . $statusCode : '', $message));
        }

        $result = $responseData['choices'][0]['message']['content'] ?? '';
        $result = trim((string) $result);

        if ($result === '') {
            throw new RuntimeException('OpenAI returned an empty response. Please try again.');
        }

        return trim($result, " \t\n\r\0\x0B\"");
    }

    private function buildMessages(string $prompt, string $content, string $language = ''): array
    {
        if ($content === '') {
            return [['role' => 'user', 'content' => $prompt]];
        }

        $messages = [['role' => 'system', 'content' => $prompt]];

        if ($language !== '') {
            $messages[] = [
                'role' => 'system',
                'content' => sprintf(self::SEO_CONFIGURED_LANGUAGE_INSTRUCTION, $language),
            ];
        } elseif (stripos($prompt, 'MANDATORY OUTPUT LANGUAGE:') === false) {
            $messages[] = ['role' => 'system', 'content' => self::SEO_LANGUAGE_INSTRUCTION];
        }

        $messages[] = ['role' => 'user', 'content' => "<page_content>\n" . $content . "\n</page_content>"];

        return $messages;
    }

    private function getTemperature(): float
    {
        $temperature = Config::get('gpt_temp');

        if (!is_numeric($temperature) || (float) $temperature < 0 || (float) $temperature > 1) {
            return self::DEFAULT_TEMPERATURE;
        }

        return (float) $temperature;
    }

    private function getModel(): string
    {
        $model = (string) Config::get('gpt_model_chat');

        return in_array($model, self::SUPPORTED_MODELS, true) ? $model : self::DEFAULT_MODEL;
    }

    private function getMaxTokens(): int
    {
        $maxTokens = (int) Config::get('gpt_max_tokens');

        return $maxTokens > 0 ? $maxTokens : self::DEFAULT_MAX_TOKENS;
    }

    private function errorResponse(string $message, int $statusCode): JsonResponse
    {
        return new JsonResponse([
            'content' => $message,
            'success' => false,
        ], $statusCode);
    }
}
