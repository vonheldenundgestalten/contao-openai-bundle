<?php

/**
 * File tl_gpt_config
 */

use Codebuster\GptBundle\Models\ContentElementsModel;
use Contao\DC_File;
use Contao\Database;

$strTable = 'tl_gpt_config';

$loadGptDefault = static function ($default): \Closure {
    return static function ($value) use ($default) {
        return $value === null || $value === '' ? $default : $value;
    };
};

$gptModels = ['gpt-5.6-luna', 'gpt-5.6-terra', 'gpt-5.6-sol', 'gpt-5.4-mini', 'gpt-5.4-nano', 'gpt-5.4', 'gpt-5-mini', 'gpt-4.1-mini'];
$loadGptModel = static function ($value) use ($gptModels) {
    return in_array($value, $gptModels, true) ? $value : 'gpt-5.6-luna';
};

$GLOBALS['TL_DCA'][$strTable] = [
//Config
    'config' => [
        'dataContainer' => \Contao\DC_File::class
    ],
    //Palettes
    'palettes' => [
        'default' => '
            {config_legend},gpt_token;
            {gptseo_legend},gpt_model_chat,gpt_title_prompt,gpt_desc_prompt,gpt_temp,gpt_max_tokens;
            {contao_legend},gpt_hidden_elements,gpt_custom_fields;gpt_allowed_tables'
    ],
    //Fields
    'fields' => [
        'gpt_token' => [
            'label' => &$GLOBALS['TL_LANG'][$strTable]['gpt_token'],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'clr','hideInput' => true]
        ],
        'gpt_model_chat' => [
            'label' => &$GLOBALS['TL_LANG'][$strTable]['gpt_model_chat'],
            'inputType' => 'select',
            'options' => $gptModels,
            'load_callback' => [$loadGptModel],
            'eval' => ['multiple' => false, 'tl_class' => 'clr w50']
        ],
        'gpt_title_prompt' => [
            'label' => &$GLOBALS['TL_LANG'][$strTable]['gpt_title_prompt'],
            'inputType' => 'textarea',
            'load_callback' => [$loadGptDefault('Write a concise and compelling SEO page title of 5 to 6 words for the supplied page content. Return only the title.')],
            'eval' => ['decodeEntities' => false,'allowHtml' => true, 'preserveTags' => true, 'tl_class' => 'clr']
        ],
        'gpt_desc_prompt' => [
            'label' => &$GLOBALS['TL_LANG'][$strTable]['gpt_desc_prompt'],
            'inputType' => 'textarea',
            'load_callback' => [$loadGptDefault('Write a clear and appealing SEO meta description of no more than 160 characters including spaces for the supplied page content. Return only the description.')],
            'eval' => ['tl_class' => 'clr']
        ],
        'gpt_temp' => [
            'label' => &$GLOBALS['TL_LANG'][$strTable]['gpt_temp'],
            'inputType' => 'text',
            'load_callback' => [$loadGptDefault('0.5')],
            'eval' => ['rgxp'=>'digit', 'maxlength' => 3, 'nospace'=>true,'tl_class' => 'w50']
        ],
        'gpt_max_tokens' => [
            'label' => &$GLOBALS['TL_LANG'][$strTable]['gpt_max_tokens'],
            'inputType' => 'text',
            'load_callback' => [$loadGptDefault('300')],
            'eval' => ['rgxp'=>'natural', 'nospace'=>true,'tl_class' => 'w50']
        ],
        'gpt_hidden_elements' => [
            'label' => &$GLOBALS['TL_LANG'][$strTable]['gpt_hidden_elements'],
            'inputType'               => 'checkbox',
            'eval'                    => ['tl_class' => 'clr w50'],
        ],
        'gpt_custom_fields' => [
            'label' => &$GLOBALS['TL_LANG'][$strTable]['gpt_custom_fields'],
            'inputType' => 'select',
            'options_callback' => [$strTable,'getContentFields'],
            'eval' => ['multiple' => true, 'chosen'=> true, 'tl_class' => 'w50']
        ],
        'gpt_allowed_tables' => [
            'label' => &$GLOBALS['TL_LANG'][$strTable]['gpt_allowed_tables'],
            'inputType'               => 'select',
            'load_callback'            => [$loadGptDefault(['tl_article'])],
            'options_callback'        => array('tl_gpt_config', 'getTables'),
            'eval'                    => array('chosen'=>true, 'multiple'=>true, 'tl_class'=>'w100')
        ]
    ]
];

class tl_gpt_config extends Contao\Backend {

    public function getContentFields(\Contao\DataContainer $dc) {

        $arrOptions = [];
        $database = Database::getInstance();
        $fields = $database->listFields('tl_content');

        foreach($fields AS $field) {
            if(in_array($field["type"],['text','varchar','mediumtext'])) {
                $arrOptions[$field["name"]] = $field["name"];
            }
        }

        // remove default and unnecessary
        unset($arrOptions["headline"]);
        unset($arrOptions["text"]);
        unset($arrOptions["type"]);
        unset($arrOptions["ptable"]);

        return $arrOptions;
    }

    /**
     * @param \Contao\DataContainer $dc
     * @return array
     */
    public function getTables($dc)
    {
        $groups = array();

        foreach ($GLOBALS['TL_MODELS'] as $k => $v)
        {
            $groups[] = $k;
        }

        return $groups;
    }
}
