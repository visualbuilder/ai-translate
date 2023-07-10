<?php

namespace Visualbuilder\AiTranslate\Helpers;

use OpenAI\Laravel\Facades\OpenAI;

class OpenAiHelper
{
    /**
     * Replace tokens with *** and save the original values
     * Format the submitted lines with line numbers
     *
     * Do the translation...
     *
     * Restore the attribute tokens, remove the line numbers and match up with the original strings.
     *
     * If any are missing the original string will be used instead. Actually - may be better to exclude from the
     * destination, then it will be able to have another go later.
     *
     * Missing strings will be merged when not overwriting.
     *
     *TODO Split this function up and save errors to include an error report at the end.  Pain to have to scroll back
     * through the history to find them
     *
     * TODO Capture the before and after to an array so each chunked result can be tablulated as we go in the console.
     *
     * TODO Right to Left Arabic sometimes switches the attribute colon to the end of the string not the start.
     * Need to resolve.
     *
     * @param $command
     * @param $chunk
     * @param $sourceLocale
     * @param $targetLanguage
     * @param $model
     *
     * @return array
     */
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

        $translatedChunk = [];
        // Combine the original keys with the translated strings
        if (count(array_keys($chunk)) === count($translatedStrings)) {
            $translatedChunk = array_combine(array_keys($chunk), $translatedStrings);
        } else {
            // Handle situation when the translation result doesn't match with the original number of keys
            $command->error("Mismatch in translation keys and translated strings for chunk. Keys: " . count(array_keys($chunk)) . " Translated Strings: " . count($translatedStrings));
            $command->newLine();

            // assign untranslated lines to their original value.
            // Actually this is not useful.  Better not to exist
            //            foreach (array_keys($chunk) as $i => $key) {
            //                $translatedChunk[$key] = $translatedStrings[$i] ?? $chunk[$key];
            //            }
        }

        if($placeholdersUsed > 0) {
            $command->warn('Response with tokens replaced');
            self::displayArrayInNumberedLines(array_values($translatedChunk), $command);
        }

        // Return both the translated chunk and the usage
        return [
            'translatedChunk' => $translatedChunk,
            'usage' => $response->usage,
        ];
    }

    public static function addLineNumbersToArrayofStrings($lines)
    {
        return array_map(function ($k, $v) { return ($k + 1) . ". {$v}"; }, array_keys($lines), $lines);
    }

    public static function displayArrayInNumberedLines($lines, $command)
    {
        // Add line numbers to each string
        $lines = self::addLineNumbersToArrayofStrings($lines);
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
