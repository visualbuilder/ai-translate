<?php


it('example', function () {
    expect(true)->toBeTrue();
});

it('merges configuration file', function () {
    // assert that the ai-translate config is not null
    expect(config()->has('ai-translate'))->toBeTrue();
});


it('registers console commands', function () {
    // get the application's Artisan kernel
    $consoleKernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);

    // fetch all registered command names
    $commands = collect($consoleKernel->all())->map(function ($command) {
        return $command->getName();
    });

    // assert that your commands are present among registered commands
    expect($commands->contains('vb:ai:install'))->toBeTrue();
    expect($commands->contains('vb:ai:translate'))->toBeTrue();
});


it('Can install config', function () {

    //First run should go straight through
    $this->artisan('vb:ai:install --force')->assertExitCode(0);

    //Second run should prompt user to overwrite file
    $this->artisan('vb:ai:install')->expectsConfirmation('config/ai-translate.php file already exists. Do you want to overwrite it?', 'no')
        ->assertExitCode(1);
});
