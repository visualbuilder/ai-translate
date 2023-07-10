<?php

namespace Visualbuilder\AiTranslate\Helpers;

use OpenAI\Laravel\Facades\OpenAI;

class OpenAiHelper
{
    public static function translateChunk($command, $chunk, $sourceLocale, $targetLanguage, $model)
    {
        // Combine all the strings in the chunk into one prompt with line numbers
        $lines = $originalLines = array_values($chunk);

        // Prepare the placeholders array
        $placeholders = [];

        // Replace the placeholders with *** in the lines
        // Due to GPT-3.5 being unable to ignore tokens when translating.
        // This is not required for GPT4 but maybe it's useful as it shortens the string and there's no point in
        // risking tokens getting translated.
        $lines = array_map(function ($line) use (&$placeholders) {
            return preg_replace_callback('/:(\w+)/', function ($matches) use (&$placeholders) {
                $placeholders[] = $matches[0]; // Store the placeholders

                return '***'; // Replace them with ***
            }, $line);
        }, $lines);

        $placeholdersUsed = count($placeholders);

        // Add line numbers to each string
        $lines = array_map(function ($k, $v) { return ($k + 1) . ". {$v}"; }, array_keys($lines), $lines);

        $linesString = implode("\n", $lines);

        // Add instructions for model to not change placeholders
        $prompt = "Translate each line from $sourceLocale into $targetLanguage".($placeholdersUsed ? " making sure to ignore all placeholders represented by '***'" : '').":\n";

        // Get the total tokens from all the strings in the chunk
        $totalTokens = OpenAiHelper::estimateTokensFromString($prompt.$linesString);
        $command->comment("Request tokens: $totalTokens");
        $command->comment("Request: $prompt");
        $command->warn('Source Lines');
        self::displayArrayInNumberedLines($originalLines, $command);

        $response = OpenAI::chat()->create([
                                               'model' => $model,
                                               'messages' => [
                                                   [
                                                       'role' => 'system',
                                                       'content' => $prompt.$linesString,
                                                   ],
                                                   [
                                                       'role' => 'user',
                                                       'content' => '',
                                                   ],
                                               ],
                                               'max_tokens' => $totalTokens * 3,
                                               'temperature' => 0.1,
                                           ]);

        // Extract the 'content' from the response
        $translatedContent = $response->choices[0]->message->content;
        $command->warn('Raw response');
        $command->info($translatedContent);
        $command->newLine();

        // Split the translated response back into separate strings
        $translatedStrings = explode("\n", trim($translatedContent));

        // Remove the line numbers from the translated strings
        $translatedStrings = array_map(function ($s) { return preg_replace('/^\d+\.\s/', '', $s); }, $translatedStrings);



        // Replace *** back with placeholders in the translated strings
        $translatedStrings = array_map(function ($line) use (&$placeholders) {
            return preg_replace_callback('/\*\*\*/', function ($matches) use (&$placeholders) {
                return array_shift($placeholders); // Replace *** back with the placeholders
            }, $line);
        }, $translatedStrings);

        // Combine the original keys with the translated strings
        if (count(array_keys($chunk)) === count($translatedStrings)) {
            $translatedChunk = array_combine(array_keys($chunk), $translatedStrings);
        } else {
            // Handle situation when the translation result doesn't match with the original number of keys
            $command->error("Mismatch in translation keys and translated strings for chunk. Keys: " . count(array_keys($chunk)) . " Translated Strings: " . count($translatedStrings));
            $command->newLine();

            // assign untranslated lines to their original value.
            $translatedChunk = [];
            foreach (array_keys($chunk) as $i => $key) {
                $translatedChunk[$key] = $translatedStrings[$i] ?? $chunk[$key];
            }
        }

        if($placeholdersUsed > 0) {
            $command->warn('Response with tokens replaced');
            self::displayArrayInNumberedLines(array_values($translatedChunk), $command);
        }

        // Extract the 'usage' from the response
        $usage = $response->usage;

        // Return both the translated chunk and the usage
        return [
            'translatedChunk' => $translatedChunk,
            'usage' => $usage,
        ];
    }

    public static function displayArrayInNumberedLines($lines, $command)
    {
        // Add line numbers to each string
        $lines = array_map(function ($k, $v) { return ($k + 1) . ". {$v}"; }, array_keys($lines), $lines);
        $command->info(implode("\n", $lines));
        $command->newLine();
    }

    public static function translateString($string, $target_lang): string
    {
        $result = OpenAI::completions()->create([
            'model' => 'text-davinci-003',
            'prompt' => "Translate from en_GB into $target_lang: $string",
            //Total
            'max_tokens' => self::estimateTokensFromString($string) * 3,
            //low temp = more accurate and less random
            'temperature' => 0.1,
        ]);

        return trim($result->choices[0]->text);
    }

    /**
     * 1 token = approx 4 characters.
     *
     * @param $string
     * @return int
     */
    public static function estimateTokensFromString($string)
    {
        return self::estimateTokens(strlen($string));
    }

    public static function estimateTokens($length)
    {
        return (int)round($length / 4);
    }
}
