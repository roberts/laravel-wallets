<?php

namespace Roberts\LaravelWallets\Commands;

use Illuminate\Console\Command;

class LaravelWalletsCommand extends Command
{
    public $signature = 'laravel-wallets';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
