<?php

namespace Hhxsv5\LaravelS\Swoole\Process;

use Hhxsv5\LaravelS\Components\Apollo\Apollo;
use Hhxsv5\LaravelS\Swoole\Coroutine\Context;
use Swoole\Coroutine;
use Swoole\Http\Server;
use Swoole\Process;

class ApolloServiceProcess implements CustomProcessInterface
{
    /**@var Apollo $apollo */
    protected static $apollo;

    public static function getDefinition()
    {
        return [
            'apollo-service' => [
                'class'    => self::class,
                'redirect' => false,
                'pipe'     => 0,
            ],
        ];
    }

    public static function callback(Server $swoole, Process $process)
    {
        $filename = base_path('.env');
        $env = getenv('LARAVELS_ENV');
        if ($env) {
            $filename .= '.' . $env;
        }

        self::$apollo = Apollo::createFromEnv();
        self::$apollo->startWatchNotification(function (array $notifications) use ($filename, $swoole) {
            $configs = self::$apollo->pullAllAndSave($filename);
            app('log')->info('[ApolloServiceProcess] Pull all configurations', $configs);
            $swoole->reload();
            if (Context::inCoroutine()) {
                Coroutine::sleep(5);
            } else {
                sleep(5);
            }
        });
    }

    public static function onReload(Server $swoole, Process $process)
    {
        // Stop the process...
        self::$apollo->stopWatchNotification();
    }
}