<?php

/**
 * Back end modules
 */
$GLOBALS['BE_MOD']['gptcontao']['gpt_config'] = array(
    'tables' => array('tl_gpt_config')
);


$GLOBALS['BE_MOD']['design']['page']['gpt_page_seo'] = array('\Codebuster\GptBundle\Classes\GptClass', 'seoModal');

// Style sheet
if (defined('TL_MODE') && TL_MODE == 'BE')
{
	$GLOBALS['TL_CSS'][] = 'bundles/gpt/css/chatgpt.css|static';
}

