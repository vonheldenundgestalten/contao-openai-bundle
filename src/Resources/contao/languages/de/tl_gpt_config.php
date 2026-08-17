<?php

$strTable = "tl_gpt_config";

$GLOBALS["TL_LANG"][$strTable] = [
    "config_legend"         => "Allgemeine Einstellungen",
    "contao_legend"         => "Contao Einstellungen",
    "gptseo_legend"         => "GPTSEO Einstellungen",
    "gpt_hidden_elements"   => ["Ausgeblendete Elemente berücksichtigen","Anhaken um auch ausgeblendete Elemente mit einzubeziehen."],
    "gpt_custom_fields"     => ["Benutzerdefinierte Felder","Weitere Tabellen-Felder auswählen. WICHTIG: Serialisierter Inhalt wird noch nicht unterstützt."],
    "gpt_token"             => ["OpenAI Token","Tragen Sie hier den Token ein - <a href='https://platform.openai.com/account/api-keys' target='_blank' style='font-weight:bold;'>Hier Token generieren</a>"],
    "gpt_allowed_tables"    => ["Erlaubte Tabellen", "Seitenartikel sind standardmäßig aktiviert. Weitere Inhaltsquellen wie Nachrichten können bei Bedarf freigegeben werden."],
    "gpt_model_chat"        => ["OpenAI-Chatmodell","GPT-5.6 Luna ist das kosteneffiziente Standardmodell für SEO-Texte. Modelle können im <a href='https://developers.openai.com/api/docs/models' target='_blank' style='font-weight:bold;'>OpenAI-Modellkatalog</a> verglichen werden."],
    "gpt_title_prompt"      => ["SEO-Titel-Prompt","Ein direkt nutzbarer Prompt ist voreingestellt und kann an den redaktionellen Stil angepasst werden."],
    "gpt_desc_prompt"       => ["SEO-Beschreibungs-Prompt","Ein direkt nutzbarer Prompt mit einer Begrenzung auf 160 Zeichen ist voreingestellt."],
    "gpt_temp"              => ["Temperatur","Steuert die Variation der Ausgabe zwischen 0 und 1. Der Standardwert 0,5 verbindet Konsistenz mit Variation."],
    "gpt_max_tokens"        => ["Maximale Ausgabe-Token","Begrenzt die generierte Ausgabe. Der Standardwert beträgt 300 Token."]
];
