<?php

/*
 * VB Ai Translate Config Options
 */
return [
    
    //What is the source language
    //Please provide just one input locale
    'source-locale'      => 'en',
    
    //Which parent directories should be translated?
    //The locale above should be present as a sub-directory in each of these locations.
    //But do not include it here. These are the parent directories.
    'source_directories' => [
        'lang',
        'vendor/visualbuilder/email-templates/resources/lang'
    ],
    
    'target_locales' => [
        'ar'    => 'Arabic',
        'de'    => 'German',
        'en_GB' => 'English British',
        'en_US' => 'English USA',
        'es'    => 'Spanish',
        'fr'    => 'French',
        'uk'    => 'Ukrainian',
    ],
    
    //Pricing data from here: https://openai.com/pricing#language-models
    'ai-models' => [
        'gpt-3.5-turbo'     => ['max_tokens' => 4096, 'input_price' => 0.0015, 'output_price' => 0.002],
        'gpt-3.5-turbo-16k' => ['max_tokens' => 16384, 'input_price' => 0.003, 'output_price' => 0.004],
        'gpt-4'             => ['max_tokens' => 8192, 'input_price' => 0.03, 'output_price' => 0.06],
        'gpt-4-32k'         => ['max_tokens' => 32768, 'input_price' => 0.06, 'output_price' => 0.12],
    ],
    
    'known_locales' => [
        'af'    => 'Afrikaans',
        'ar'    => 'Arabic',
        'az'    => 'Azerbaijani',
        'be'    => 'Belarusian',
        'bg'    => 'Bulgarian',
        'bn'    => 'Bengali',
        'bs'    => 'Bosnian',
        'ca'    => 'Catalan',
        'cs'    => 'Czech',
        'cy'    => 'Welsh',
        'da'    => 'Danish',
        'de'    => 'German',
        'el'    => 'Greek',
        'en_GB' => 'English British',
        'en_US' => 'English USA',
        'es'    => 'Spanish',
        'et'    => 'Estonian',
        'eu'    => 'Basque',
        'fa'    => 'Persian',
        'fi'    => 'Finnish',
        'fr'    => 'French',
        'ga'    => 'Irish',
        'gd'    => 'Scottish Gaelic',
        'gl'    => 'Galician',
        'gu'    => 'Gujarati',
        'ha'    => 'Hausa',
        'he'    => 'Hebrew',
        'hi'    => 'Hindi',
        'hr'    => 'Croatian',
        'hu'    => 'Hungarian',
        'hy'    => 'Armenian',
        'id'    => 'Indonesian',
        'ig'    => 'Igbo',
        'is'    => 'Icelandic',
        'it'    => 'Italian',
        'ja'    => 'Japanese',
        'ka'    => 'Georgian',
        'kk'    => 'Kazakh',
        'km'    => 'Khmer',
        'kn'    => 'Kannada',
        'ko'    => 'Korean',
        'ku'    => 'Kurdish',
        'lo'    => 'Lao',
        'lt'    => 'Lithuanian',
        'lv'    => 'Latvian',
        'mk'    => 'Macedonian',
        'ml'    => 'Malayalam',
        'mn'    => 'Mongolian',
        'mr'    => 'Marathi',
        'ms'    => 'Malay',
        'my'    => 'Burmese',
        'ne'    => 'Nepali',
        'nl'    => 'Dutch',
        'no'    => 'Norwegian',
        'pa'    => 'Punjabi',
        'pl'    => 'Polish',
        'pt_BR' => 'Portuguese (Brazil)',
        'pt_PT' => 'Portuguese (Portugal)',
        'ro'    => 'Romanian',
        'ru'    => 'Russian',
        'si'    => 'Sinhala',
        'sk'    => 'Slovak',
        'sl'    => 'Slovenian',
        'so'    => 'Somali',
        'sq'    => 'Albanian',
        'sr'    => 'Serbian',
        'sv'    => 'Swedish',
        'sw'    => 'Swahili',
        'ta'    => 'Tamil',
        'te'    => 'Telugu',
        'th'    => 'Thai',
        'tr'    => 'Turkish',
        'uk'    => 'Ukrainian',
        'ur'    => 'Urdu',
        'vi'    => 'Vietnamese',
        'xh'    => 'Xhosa',
        'yi'    => 'Yiddish',
        'zh_CN' => 'Chinese (Simplified)',
        'zh_TW' => 'Chinese (Traditional)',
        'zu'    => 'Zulu',
    ]


];