<?php

namespace Visualbuilder\AiTranslate\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\VarExporter\VarExporter;
use Visualbuilder\AiTranslate\Helpers\FileHelper;
use Visualbuilder\AiTranslate\Helpers\OpenAiHelper;

class TranslateStrings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vb:ai:translate
                            {--cheapest : Use the lowest cost model}
                            {--best : Use the best more accurate model - slower and more $$$$ }
                            {--force : Overwrite existing files}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translates all language files';
    
    protected $sourceData = [];
    
    protected $sourceLocale = "";
    protected $model = "";
    protected $maxInputStringLength = 2000;
    protected $maxRetries = 5;
    protected $totalPromptTokens = 0;
    protected  $totalCompletionTokens = 0;
    
    protected $overwrite = false;
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        
        $this->init();
        $files = $this->findSourceFiles();
        $this->translateFiles($files);
        return Command::SUCCESS;
    }
    
    private function init()
    {
        $this->sourceLocale = config('ai-translate.source-locale');
        $this->overwrite = (bool) $this->option('force');
    }
    
    
    private function findSourceFiles()
    {
        $jsonFiles = FileHelper::getJsonSourceFileList($this->sourceLocale);
        $phpFiles = FileHelper::getLanguageFileList($this->sourceLocale);
        return array_merge($jsonFiles,$phpFiles);
    }
    
    private function translateFiles($files)
    {
       
        $this->estimateCostAndSelectModel($files);
        
        foreach ($files as $sourceFile) {
            $this->sourceData = $this->readLanguageFile($sourceFile);
            $chunks = $this->chunkArray($this->sourceData);
            foreach (config('ai-translate.target_locales') as $targetLocale => $targetLanguage) {
                $this->handleTargetLocale($sourceFile, $chunks, $targetLocale, $targetLanguage);
            }
        }
        
        $this->newLine();
        $this->info("Total prompt tokens used: ".$this->totalPromptTokens);
        $this->info("Total completion tokens used: ".$this->totalCompletionTokens);
        $this->warn("Total Cost: $".$this->getCost());
    }
    
    /**
     * Deliberately keeping the chunks smaller to avoid timeout issues with long inputs.
     * With GPT-3.5 this equates to 2700 characters input per request
     * @return void
     */
    private function setMaxInputStringLength(){
        $maxTokens = config('ai-translate.ai-models')[ $this->model ][ 'max_tokens' ];
        $maxCharactersTotal = $maxTokens * 4;
        $this->maxInputStringLength = round($maxCharactersTotal / 6, 0);
    }
    
    /**
     * Create the target file and process the input chunked array
     *
     * @param $file
     * @param $chunks
     * @param $targetLocale
     * @param $targetLanguage
     *
     * @return void
     */
    private function handleTargetLocale($file, $chunks, $targetLocale, $targetLanguage) {
        if ($targetFile = $this->createCheckOrEmptyTargetFile($file, $targetLocale)) {
            $this->handleExistingTargetFile($targetFile, $chunks);
            
            foreach ($chunks as $chunk) {
                $this->processChunk($targetFile, $chunk, $targetLanguage);
            }
        }
    }
    
    /**
     * If the file contains records - diff with the source to only translate new strings.
     * @param $targetFile
     * @param $chunks
     *
     * @return void
     */
    private function handleExistingTargetFile($targetFile, &$chunks) {
        $targetArray = $this->readLanguageFile($targetFile);
        if (count($targetArray) !== 0 && !$this->overwrite) {
            $diffArray = array_diff_key($this->sourceData, $targetArray);
            $chunks = $this->chunkArray($diffArray);
        }
    }
    
    /**
     * Gracefully try translation, with some retries if fails and save the result to the target
     * @param $targetFile
     * @param $chunk
     * @param $targetLanguage
     *
     * @return void
     */
    private function processChunk($targetFile, $chunk, $targetLanguage) {
        $retryCount = 0;
        $complete = false;
        while ($retryCount < $this->maxRetries && !$complete) {
            try {
                $translatedChunk = OpenAiHelper::translateChunk($this, $chunk, $this->sourceLocale, $targetLanguage,$this->model);
                $this->appendResponse($targetFile, $translatedChunk['translatedChunk']);
                $complete = true;
            } catch (\Exception $e) {
                $this->handleRetry($retryCount, $e, $targetFile);
            }
        }
        $this->handleRetryFailure($retryCount, $targetFile);
    }
    
    private function handleRetry(&$retryCount, $exception, $targetFile) {
        $retryCount++;
        $this->error('An error occurred while processing the file: ' . $targetFile);
        $this->error('Error message: ' . $exception->getMessage());
        $this->info("Retrying $retryCount / $this->maxRetries Times in " . pow(2, $retryCount) . ' seconds');
        usleep(pow(2, $retryCount) * 1000000); // Wait for 2^retryCount seconds
    }
    
    private function handleRetryFailure($retryCount, $targetFile) {
        if ($retryCount === $this->maxRetries) {
            $this->error("Request failed after ".$this->maxRetries." retries");
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
        }
    }
    
    private function getCost(){
        $cost = ($this->totalPromptTokens / 1000) * config('ai-translate.ai-models')[ $this->model ][ 'input_price' ];
        $cost += ($this->totalCompletionTokens / 1000) * config('ai-translate.ai-models')[ $this->model ][ 'output_price' ];
        return $cost;
    }
    
    /**
     * Parse the input files and estimate the cost of translation
     * Ask the user which model they would like to use if they have not specified
     *
     * Set the model and the maximum input string length for the model
     * @param $files
     * @param $files
     *
     * @return true
     */
    private function estimateCostAndSelectModel($files) {
        $targetLanguages = config('ai-translate.target_locales');
        $targetCount = count($targetLanguages);
        
        $this->info("Found ".count($files)." files to translate into $targetCount languages");
        
        $stats = [];
        $totalItems = 0;
        $totalLength = 0;
        $sourceTokensTotal = 0;
        $targetTokensTotal = 0;
        $totalTokens = 0;
        
        foreach ($files as $file) {
            $lengths = FileHelper::countItemsAndStringLengths($file);
            $sourceTokens = OpenAiHelper::estimateTokens($lengths[ 'totalLength' ]);
            $stats[] = [
                $file,
                $lengths[ 'itemCount' ],
                $lengths[ 'totalLength' ],
                $sourceTokens,
                $sourceTokens * $targetCount,
                $sourceTokens + ($sourceTokens * $targetCount)
            
            ];
            
            // Accumulate totals
            $totalItems += $lengths[ 'itemCount' ];
            $totalLength += $lengths[ 'totalLength' ];
            $sourceTokensTotal += OpenAiHelper::estimateTokens($lengths[ 'totalLength' ]);
            $targetTokensTotal += $sourceTokens * $targetCount;
            $totalTokens += $sourceTokens + ($sourceTokens * $targetCount);
        }
        
        // Append totals to stats
        $stats[] = ['Total', $totalItems, $totalLength, $sourceTokensTotal, $targetTokensTotal, $totalTokens];
        
        $this->table(
            ['File', 'Lines', 'Total String Length', 'Source Tokens', 'Target Tokens', 'Total Tokens'], $stats
        );
        
        $this->info('Cost Estimations per AI Model');
        
        $totals = end($stats);
        
        $data = [];
        foreach (config('ai-translate.ai-models') as $key => $model) {
            $data[] = [
                $key,
                round(($totals[ 3 ] / 1000) * $model[ 'input_price' ], 2),
                round(($totals[ 4 ] / 1000) * $model[ 'output_price' ], 2),
                round(
                    (($totals[ 3 ] / 1000) * $model[ 'input_price' ]) + (($totals[ 4 ] / 1000)
                                                                         * $model[ 'output_price' ]), 2
                )
            ];
        }
        
        $this->table(
            ['Model', 'Input Cost ($)', 'Output Cost ($)', 'Total Cost ($)'], $data
        
        );
        $this->warn('Please notes costs are estimations only, prices will vary subject to the destination languages translated');
        
        if(!$this->option('cheapest') && !$this->option('best')) {
            $model = $this->choice(
                'Which model would you like to use?', array_keys(config('ai-translate.ai-models')), array_keys(config('ai-translate.ai-models'))[ 0 ]
            );
        }
        else {
            $models = config('ai-translate.ai-models');
            $model = $this->option('cheapest') ? array_keys(config('ai-translate.ai-models'))[ 0 ] : end($models);
        }
        
        $this->info("Going to translate using $model");
        $this->model = $model;
        $this->setMaxInputStringLength();
        return true;
    }
    
    /**
     * They should always be an array so we can just include it.
     * @param $file
     *
     * @return mixed
     */
    public function readLanguageFile($file) {
        if(!file_exists($file)){
            return [];
        }
        switch (FileHelper::getExtention($file)){
            case('php'):
                return include($file);
            case('json'):
                return json_decode(file_get_contents($file), true);
        }
    }
    
    
    
    /**
     * Splits a file into chunks of a given length
     *
     * @param  string  $file  The filename
     * @param  int  $maxLength  The maximum length of a chunk
     *
     * @return array The chunks
     */
    public function chunkArray($array) {
        $chunks = [];
        $chunk = [];
        $length = 0;
        
        // Flatten the array using Laravel's helper function
        $flattenedArray = Arr::dot($array);
        
        foreach ($flattenedArray as $key => $value) {
            // If the value is an empty array, skip to the next iteration
            if(is_array($value) && empty($value)) {
                continue;
            }
            
            $itemLength = strlen($value);
            if($length + $itemLength > $this->maxInputStringLength && !empty($chunk)) {
                // If adding this item will exceed the max length, save the current chunk and start a new one
                $chunks[] = $chunk;
                $chunk = [];
                $length = 0;
            }
            $chunk[ $key ] = $value;
            $length += $itemLength;
        }
        if(!empty($chunk)) {
            // Add the last chunk if it's not empty
            $chunks[] = $chunk;
        }
        
        return $chunks;
    }

    public function createCheckOrEmptyTargetFile($file, $locale) {
        // Replace source locale with target locale in the path
        switch(FileHelper::getExtention($file)){
            case('php'):
                //php files are in their own locale dir
                $newFile = str_replace('/'.$this->sourceLocale.'/', '/'.$locale.'/', $file);
                break;
            case('json'):
                //Json files are in the same dir
                $newFile = str_replace($this->sourceLocale.'.json', $locale.'.json', $file);
        }
        
        
        // If overwrite is false and file already exists, make sure it contains an array
        if(!$this->overwrite && file_exists($newFile)) {
            return is_array($this->readLanguageFile($newFile))?$newFile:false;
        }
        
        // Create directory structure if it doesn't exist
        $directory = dirname($newFile);
        if(!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        switch(FileHelper::getExtention($file)){
            case('php'):
                $comment = "/**\n * Auto Translated by visualbuilder/ai-translate on " . date("d/m/Y") . "\n */";
                file_put_contents($newFile, "<?php\n\n" . $comment . "\n\nreturn [\n\n];\n");
                break;
            case('json'):
                file_put_contents($newFile, "{}");
        }
        
        return $newFile;
    }
    
    public function appendResponse($filename, $translatedChunk) {
        // Fetch existing content
        $existingContent = $this->readLanguageFile($filename);
        
        // Undot the translated chunk
        $undottedTranslatedChunk = Arr::undot($translatedChunk);
        
        // Merge new translations with existing content
        $newContent = array_merge($existingContent, $undottedTranslatedChunk);

        $comment = "/**\n * Auto Translated by visualbuilder/ai-translate on " . date("d/m/Y") . "\n */";
        
        switch (FileHelper::getExtention($filename)){
            case('php'):
                $output = "<?php\n\n" . $comment . "\nreturn ".VarExporter::export($newContent).";\n";
            case('json'):
                $output = json_encode($newContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        // Write to the file
        file_put_contents($filename, $output);
    }
    
    /**
     * Recursively merges arrays
     *
     * @param  array  $array1  The first array
     * @param  array  $array2  The second array
     *
     * @return array The merged array
     */
    private function mergeArray($array1, $array2) {
        foreach ($array2 as $key => $value) {
            if(array_key_exists($key, $array1) && is_array($array1[ $key ]) && is_array($value)) {
                $array1[ $key ] = $this->mergeArray($array1[ $key ], $value);
            }
            else {
                $array1[ $key ] = $value;
            }
        }
        
        return $array1;
    }
}
