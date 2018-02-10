<?php

namespace App\Providers;

use Log;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->setupLog();
    }

    protected function setupLog()
    {
        // Monolog
        $monoLog = Log::getMonolog();
        foreach($monoLog->getHandlers() as $handler) {
            $handler->setFormatter(new LineFormatter("[%datetime%] %channel%.%level_name%.%extra.process_id%: %message% (%extra.file%:%extra.line%)\n", 'Y-m-d H:i:s.u', true, true));
        }

        $monoLog->pushProcessor(new IntrospectionProcessor(Logger::DEBUG, [], 4));
        $monoLog->pushProcessor(new ProcessIdProcessor());

        // Channelog
        $this->app->bind('channelog', 'App\Helpers\ChannelWriterHelper');
    }
}
