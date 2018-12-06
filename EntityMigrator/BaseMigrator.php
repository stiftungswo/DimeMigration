<?php

namespace EntityMigrator;

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

        // TODO Add .env package and configure this with .env files
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3010',
            'database' => 'dime',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ], 'oldDime');

        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '33306',
            'database' => 'dime',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ], 'newDime');

        $capsule->setAsGlobal();

        $this->capsule = $capsule;
    }
}
