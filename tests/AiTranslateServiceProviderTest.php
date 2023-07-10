<?php

use Visualbuilder\AiTranslate\Tests\TestCase;

uses(TestCase::class);

it('publishes configuration file', function () {
    // mock runningInConsole to return true
    $this->app->shouldReceive('runningInConsole')->once()->andReturn(true);
    
    // use the File facade to assert that the file has been copied to the correct location
    assertTrue(\File::exists(config_path('ai-translate.php')));
});

it('merges configuration file', function () {
    // assert that the ai-translate config is not null
    assertNotNull(config('ai-translate'));
});

it('registers console commands', function () {
    // get the application's Artisan kernel
    $consoleKernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);
    
    // fetch all registered command names
    $commands = collect($consoleKernel->all())->map(function ($command) {
        return $command->getName();
    });
    
    // assert that your commands are present among registered commands
    assertTrue($commands->contains('vb:ai:install'));
    assertTrue($commands->contains('vb:ai:translate'));
});
