<?php

namespace Contao {
    final class Config
    {
        public static array $values = [];

        public static function get($key)
        {
            return self::$values[$key] ?? null;
        }
    }

    class Backend {}
}

namespace {
    use Codebuster\GptBundle\Controller\GptController;
    use Contao\Config;

    require_once __DIR__ . '/../src/Controller/GptController.php';
    require_once __DIR__ . '/../src/Resources/contao/dca/tl_gpt_config.php';

    function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    $controller = new GptController();
    $buildRequest = new ReflectionMethod(GptController::class, 'buildRequestData');
    $buildRequest->setAccessible(true);
    $field = $GLOBALS['TL_DCA']['tl_gpt_config']['fields']['gpt_model_chat'];
    $loadModel = $field['load_callback'][0];

    foreach ($field['options'] as $model) {
        Config::$values = ['gpt_model_chat' => $model, 'gpt_temp' => '0.7', 'gpt_max_tokens' => '500'];
        $data = $buildRequest->invoke($controller, 'Write an SEO title.', 'Page content');
        check($data['model'] === $model && $loadModel($model) === $model, 'Picker and request disagree for ' . $model);
        if ($model === 'gpt-6.1-sol') {
            check($data['reasoning_effort'] === 'low', 'Sol requires supported reasoning effort.');
            check(!isset($data['temperature']), 'Sol must omit unsupported temperature.');
            check($data['max_completion_tokens'] === 4596, 'Sol needs allowance for reasoning.');
        } else {
            check($data['temperature'] === 0.7 && $data['max_completion_tokens'] === 500, 'Sampling or token settings changed for ' . $model);
            if ($model !== 'gpt-4.1-mini') {
                check($data['reasoning_effort'] === 'none', 'Disable reasoning for ' . $model);
            } else {
                check(!isset($data['reasoning_effort']), 'GPT-4.1 does not support reasoning effort.');
            }
        }
    }

    foreach (['gpt-5.4-nano', 'gpt-5-mini', 'unknown-model', '', null] as $model) {
        Config::$values = ['gpt_model_chat' => $model];
        $data = $buildRequest->invoke($controller, 'Write an SEO title.', 'Page content');
        check(!in_array($model, $field['options'], true), 'Unsupported model still selectable.');
        check($data['model'] === 'gpt-6-luna' && $loadModel($model) === 'gpt-6-luna', 'Unsupported selections must fall back in picker and request.');
        check($data['max_completion_tokens'] === 300, 'Default token budget changed.');
    }

    Config::$values = ['gpt_model_chat' => 'gpt-6.1-sol'];
    $data = $buildRequest->invoke($controller, 'Write an SEO title.', 'Page content');
    check($data['max_completion_tokens'] === 4396, 'Default Sol budget must include reasoning allowance.');
    fwrite(STDOUT, "Model selection tests passed.\n");
}
