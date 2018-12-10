<?php

namespace EntityMigrator;

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

abstract class BaseMigrator
{

    /**
     * @var Capsule $capsule
     */
    protected $capsule;

    public function __construct()
    {
        $capsule = new Capsule;
        $dotenv = new Dotenv(dirname( dirname(__FILE__) ));
        $dotenv->load();

        $capsule->addConnection([
            'driver' => env('OLD_DB_CONNECTION'),
            'host' => env('OLD_DB_HOST'),
            'port' => env('OLD_DB_PORT'),
            'database' => env('OLD_DB_NAME'),
            'username' => env('OLD_DB_USERNAME'),
            'password' => env('OLD_DB_PASSWORD'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ], 'oldDime');

        $capsule->addConnection([
            'driver' => env('NEW_DB_CONNECTION'),
            'host' => env('NEW_DB_HOST'),
            'port' => env('NEW_DB_PORT'),
            'database' => env('NEW_DB_NAME'),
            'username' => env('NEW_DB_USERNAME'),
            'password' => env('NEW_DB_PASSWORD'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ], 'newDime');

        $capsule->setAsGlobal();

        $this->capsule = $capsule;
    }
}
