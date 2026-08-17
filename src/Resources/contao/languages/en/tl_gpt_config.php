<?php

$strTable = "tl_gpt_config";

$GLOBALS["TL_LANG"][$strTable] = [
    "config_legend"         => "General settings",
    "contao_legend"         => "Contao settings",
    "gptseo_legend"         => "GPTSEO settings",
    "gpt_hidden_elements"   => ["Include hidden elements","Aktivate to include hidden elements."],
    "gpt_custom_fields"     => ["Custom fields","Select additional table fields. IMPORTANT: Serialized content is not yet supported."],
    "gpt_token"             => ["OpenAI Token","Enter the token here - <a href='https://platform.openai.com/account/api-keys' target='_blank' style='font-weight:bold;'>Generate tokens here</a>"],
    "gpt_allowed_tables"    => ["Allowed tables", "Page articles are enabled by default. Enable additional content sources, such as news, when needed."],
    "gpt_model_chat"        => ["OpenAI chat model","GPT-5.6 Luna is the cost-efficient default for SEO generation. Compare models in the <a href='https://developers.openai.com/api/docs/models' target='_blank' style='font-weight:bold;'>OpenAI model catalog</a>."],
    "gpt_title_prompt"      => ["SEO title prompt","A ready-to-use prompt is provided by default. Change it here to match your editorial style."],
    "gpt_desc_prompt"       => ["SEO description prompt","A ready-to-use prompt with a 160-character limit is provided by default."],
    "gpt_temp"              => ["Temperature","Controls output variation between 0 and 1. The default of 0.5 balances consistency and variation."],
    "gpt_max_tokens"        => ["Maximum output tokens","Limits generated output. The default is 300 tokens."]
];
