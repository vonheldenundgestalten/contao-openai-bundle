<?php

use Codebuster\GptBundle\Controller\GptController;

require_once __DIR__ . '/../src/Controller/GptController.php';

$controller = new GptController();
$method = new ReflectionMethod(GptController::class, 'buildMessages');
$method->setAccessible(true);

$messages = $method->invoke($controller, 'Create a short SEO title.', 'Deutscher Seiteninhalt');

if ($messages !== [
    ['role' => 'system', 'content' => 'Create a short SEO title.'],
    ['role' => 'system', 'content' => 'MANDATORY OUTPUT LANGUAGE: Detect the predominant language inside <page_content> and write the entire SEO output only in that language. Do not use the language of the prompt unless it matches the page content.'],
    ['role' => 'user', 'content' => "<page_content>\nDeutscher Seiteninhalt\n</page_content>"],
]) {
    fwrite(STDERR, "SEO prompt messages do not enforce the page-content language.\n");
    exit(1);
}

$languageAwareTitlePrompt = 'MANDATORY OUTPUT LANGUAGE: First detect the predominant language of the supplied page content. Write the entire SEO title only in that language. Do not use the language of this prompt unless it matches the page content. Write a concise and compelling SEO page title of 5 to 6 words. Return only the title.';
$messages = $method->invoke($controller, $languageAwareTitlePrompt, 'Deutscher Seiteninhalt');

if ($messages !== [
    ['role' => 'system', 'content' => $languageAwareTitlePrompt],
    ['role' => 'user', 'content' => "<page_content>\nDeutscher Seiteninhalt\n</page_content>"],
]) {
    fwrite(STDERR, "A language-aware custom SEO prompt duplicates the mandatory instruction.\n");
    exit(1);
}

$messages = $method->invoke($controller, 'Rewrite this selection.', '');

if ($messages !== [['role' => 'user', 'content' => 'Rewrite this selection.']]) {
    fwrite(STDERR, "TinyMCE prompt messages were changed unexpectedly.\n");
    exit(1);
}

fwrite(STDOUT, "Prompt construction tests passed.\n");
