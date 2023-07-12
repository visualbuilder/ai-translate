<?php

namespace Visualbuilder\AiTranslate\Traits;

trait AiModelSelectable
{
    protected $model;

    public function selectAiModel()
    {
        if(! $this->option('cheapest') && ! $this->option('best')) {
            $model = $this->choice(
                'Which model would you like to use?',
                array_keys(config('ai-translate.ai-models')),
                array_keys(config('ai-translate.ai-models'))[ 0 ]
            );
        } else {
            $models = config('ai-translate.ai-models');
            $model = $this->option('cheapest') ? array_keys(config('ai-translate.ai-models'))[ 0 ] : end($models);
        }

        $this->info("Going to translate using $model");
        $this->model = $model;

        return true;
    }
}
