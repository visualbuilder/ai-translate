<?php

namespace Visualbuilder\AiTranslate\Console;

use Illuminate\Console\Command;
use Visualbuilder\AiTranslate\Helpers\OpenAiHelper;
use Visualbuilder\AiTranslate\Traits\AiModelSelectable;

class TranslateModel extends Command
{
    use AiModelSelectable;
    
    protected $signature   = 'vb:ai:translate:model
                            {--cheapest : Use the lowest cost model}
                            {--best : Use the best more accurate model - slower and more $$$$ }
                            {--force : Force re-translation of existing models}';
    protected $description = 'Translates model attributes based on the source language to the target languages';
    
    protected $sourceLocale;
    protected $targetLocales = [];
    
    public function __construct() {
        parent::__construct();
        $this->sourceLocale = config('ai-translate.source-locale');
        $this->targetLocales = config('ai-translate.target_locales');
       
    }
    
    public function handle() {
        
        $this->selectAiModel();
        
        $this->info('Starting translation process...');
        
        // Retrieve model configurations from the configuration file.
        $modelConfigurations = config('ai-translate.models_with_language_keys');
        
        foreach ($modelConfigurations as $config) {
            // Fetch the models in the source language.
            $modelClassName = $config[ 'model' ];
            $sourceModels = $this->getSourceModels($modelClassName);
            
            foreach ($sourceModels as $sourceModel) {
                foreach ($this->targetLocales as $locale => $language) {
                    if($locale === $this->sourceLocale) {
                        continue;
                    }
                    // Process each model for translation.
                    $this->processModelTranslation($sourceModel, $locale, $language, $config);
                }
            }
        }
        
        $this->info('Translation process completed.');
        
        return Command::SUCCESS;
    }
    
    /**
     * Fetch all models of the provided class name in the source language.
     */
    private function getSourceModels($modelClassName) {
        return $modelClassName::where('language', $this->sourceLocale)
                              ->get();
    }
    
    /**
     * Process a model for translation. If the --force flag is used, translations are overwritten even if they exist.
     */
    private function processModelTranslation($sourceModel, $targetLocale, $targetLanguage, $config) {
        // Fetch the target model. If it doesn't exist, create a new one.
        $targetModel = $this->getOrCreateTargetModel($sourceModel, $targetLocale, $config);
        
        $data = [];
        if($targetModel->wasRecentlyCreated || $this->option('force')) {
            foreach ($config[ 'translatable_attributes' ] as $attribute) {
                // Translate each attribute and update it in the target model.
                $response = OpenAiHelper::translate($sourceModel->{$attribute}, $targetLanguage, $this->model);
                $targetModel->{$attribute} = $response['tokens_replaced'];
                $data[]=[$sourceModel->{$attribute},$response['source'],$response['response'],$response['tokens_replaced']];
                
            }
            $this->table(["From ".$this->sourceLocale,'Tokenised',"To $targetLanguage",'Tokens Replaced'],$data);
            $targetModel->save();
            
//            foreach ($config[ 'translatable_html_attributes' ] as $attribute) {
//                // Translate each HTML attribute and update it in the target model.
//                $translatedValue = OpenAiHelper::translateHtml($sourceModel->{$attribute}, $targetLanguage);
//                $targetModel->{$attribute} = $translatedValue;
//            }
            
            // Save the updated target model.
            $targetModel->save();
        }
    }
    
    /**
     * Fetch an existing model in the target language. If it doesn't exist, replicate the source model.
     */
    private function getOrCreateTargetModel($sourceModel, $targetLocale, $config) {
        $modelClassName = $config[ 'model' ];
        $key = $config[ 'key' ];
        
        // Attempt to find the target model.
        $targetModel = $modelClassName::where($config[ 'locale_key' ], $targetLocale)
                                      ->where($key, $sourceModel->{$key})
                                      ->first();
        
        if(!$targetModel) {
            // If the target model doesn't exist, replicate the source model and save it as the target model.
            $targetModel = $sourceModel->replicate();
            $targetModel->{$config[ 'locale_key' ]} = $targetLocale;
            $targetModel->save();
        }
        
        return $targetModel;
    }
    
    public function selectAiModel() {
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
        
        return true;
    }
}
