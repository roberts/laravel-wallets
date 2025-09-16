<?php

namespace Roberts\LaravelWallets\Commands;

use Illuminate\Console\Command;

class WalletsCommand extends Command
{
    public $signature = 'wallets';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
