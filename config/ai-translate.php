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
        'resources/lang',
        //Developers can point to their packages to have them translated
        //Either in vendor or where ever they happen to be
        'vendor/visualbuilder/email-templates/resources/lang',
        'resources/lang/vendor/ekoukltd/laraconsent'
    ],
    
    //Put the languages you need in here
    'target_locales'        => [
        'ar'    => 'Arabic',
        'de'    => 'German',
        'en_GB' => 'English British',
        'en_US' => 'English USA',
        'es'    => 'Spanish',
        'fr'    => 'French',
        'uk'    => 'Ukrainian',
    ],
    
    //Pricing data from here: https://openai.com/pricing#language-models
    //Prices are per 1000 tokens (approx 4000 words)
    'ai-models'             => [
        'gpt-3.5-turbo'     => ['max_tokens' => 4096, 'input_price' => 0.0015, 'output_price' => 0.002],
        'gpt-3.5-turbo-16k' => ['max_tokens' => 16384, 'input_price' => 0.003, 'output_price' => 0.004],
        'gpt-4'             => ['max_tokens' => 8192, 'input_price' => 0.03, 'output_price' => 0.06],
        'gpt-4-32k'         => ['max_tokens' => 32768, 'input_price' => 0.06, 'output_price' => 0.12],
    ],
    
    //Records will be chunked to reduce the number of API requests.
    //We will not submit more than 1/3 of max_tokens per request to allow for longer translation responses like German.
    //If a chunk of a file fails, it will be omitted from the result.
    //If you hit a persistent issue then reduce this number to allow more lines to be translated and help find the
    // problematic string. You may also prefer to monitor fewer at a time.
    // If your translations are lots of short strings, this number could be much higher.
    'max_lines_per_request' => 40,
    
    //If Chat GPT throws an error, how many times should we gracefully retry with exponential backoff
    //This is useful for timeout errors.
    'max_retries'           => 5,
    
    //Copy any required from here to target_locales above to enable translation of those longuages.
    'known_locales'         => [
        'af'    => 'Afrikaans',
        'sq'    => 'Albanian',
        'ar'    => 'Arabic',
        'az'    => 'Azerbaijani',
        'eu'    => 'Basque',
        'be'    => 'Belarusian',
        'bg'    => 'Bulgarian',
        'bs'    => 'Bosnian',
        'ca'    => 'Catalan',
        'zh_CN' => 'Chinese (Simplified)',
        'zh_TW' => 'Chinese (Traditional)',
        'hr'    => 'Croatian',
        'cs'    => 'Czech',
        'da'    => 'Danish',
        'nl'    => 'Dutch',
        'en_GB' => 'English British',
        'en_US' => 'English USA',
        'et'    => 'Estonian',
        'fi'    => 'Finnish',
        'fr'    => 'French',
        'gl'    => 'Galician',
        'de'    => 'German',
        'el'    => 'Greek',
        'ha'    => 'Hausa',
        'he'    => 'Hebrew',
        'hi'    => 'Hindi',
        'hu'    => 'Hungarian',
        'is'    => 'Icelandic',
        'id'    => 'Indonesian',
        'ig'    => 'Igbo',
        'it'    => 'Italian',
        'ja'    => 'Japanese',
        'kk'    => 'Kazakh',
        'ko'    => 'Korean',
        'ku'    => 'Kurdish',
        'lv'    => 'Latvian',
        'lt'    => 'Lithuanian',
        'mk'    => 'Macedonian',
        'ms'    => 'Malay',
        'mn'    => 'Mongolian',
        'mr'    => 'Marathi',
        'no'    => 'Norwegian',
        'fa'    => 'Persian',
        'pl'    => 'Polish',
        'pt_BR' => 'Portuguese (Brazil)',
        'pt_PT' => 'Portuguese (Portugal)',
        'ro'    => 'Romanian',
        'ru'    => 'Russian',
        'gd'    => 'Scottish Gaelic',
        'sr'    => 'Serbian',
        'sk'    => 'Slovak',
        'sl'    => 'Slovenian',
        'so'    => 'Somali',
        'es'    => 'Spanish',
        'sw'    => 'Swahili',
        'sv'    => 'Swedish',
        'th'    => 'Thai',
        'tr'    => 'Turkish',
        'uk'    => 'Ukrainian',
        'ur'    => 'Urdu',
        'vi'    => 'Vietnamese',
        'cy'    => 'Welsh',
        'xh'    => 'Xhosa',
        'zu'    => 'Zulu',
    ],
    
    //These have problems when chunking results.
    //But they do work with max_lines_per_request set to 1x
    'problematic_locales'   => [
        'hy' => 'Armenian',
        'gu' => 'Gujarati',
        'kn' => 'Kannada',
        'ka' => 'Georgian',
        'km' => 'Khmer',
        'lo' => 'Lao',
        'ml' => 'Malayalam',
        'my' => 'Burmese',
        'pa' => 'Punjabi',
        'si' => 'Sinhala',
        'ta' => 'Tamil',
        'te' => 'Telugu',
        'bn' => 'Bengali',
        'el' => 'Greek',
        'yi' => 'Yiddish',
        'ne' => 'Nepali',
    ]

];