<?php

namespace Visualbuilder\AiTranslate\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vb:ai:install
							{--F|force : Force overwrite existing files}
                            ';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Visual Builder AI Translation';
    
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $this->info('Installing Visual Builder AI Translation');
        
        if(!$this->configExists('ai-translate.php')) {
            $this->publishConfiguration();
            $this->info('Published configuration files');
        }
        else {
            if($this->option('force') || $this->shouldOverwriteConfig()) {
                $this->info('Overwriting configuration files...');
                $this->publishConfiguration($force = true);
            }
            else {
                $this->info('Existing configuration was not overwritten');
            }
        }
        
        $this->info("Success!! You will need to add your OpenAI key to the config/openai.php file.");
        $this->newLine();
        
        return Command::SUCCESS;
    }
    
    private function configExists($fileName) {
        return File::exists(config_path($fileName));
    }
    
    private function publishConfiguration($forcePublish = false) {
        $params = [
            '--provider' => "Visualbuilder\AiTranslate\AiTranslateServiceProvider",
            '--tag'      => "config",
            '--force'    => $forcePublish
        ];
        
        $this->call('vendor:publish', $params);
        
        if(!$this->configExists('openai.php')) {
            //Publish OpenAI config if it's not there
            $params = [
                '--provider' => "OpenAI\Laravel\ServiceProvider",
                '--force'    => $forcePublish
            ];
            
            $this->info('Publishing Open AI Config.');
            $this->call('vendor:publish', $params);
        }
        
        //Do this if Spatie Translation Loaded v2
//        if(!$this->configExists('translation-loader.php')) {
//            //Publish Translation Config
//            $params = [
//                '--provider' => "Spatie\TranslationLoader\TranslationServiceProvider",
//                '--tag'      => "config"
//            ];
//
//            $this->call('vendor:publish', $params);
//        }
    }
    
    private function shouldOverwriteConfig() {
        return $this->confirm(
            'config/ai-translate.php file already exists. Do you want to overwrite it?', false
        );
    }
}
