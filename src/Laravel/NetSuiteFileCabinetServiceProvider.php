<?php

namespace BajaFoundry\NetSuite\Flysystem\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use BajaFoundry\NetSuite\Flysystem\Adapter\NetSuiteFileCabinetAdapter;
use BajaFoundry\NetSuite\Flysystem\Client\NetSuiteClient;

/**
 * Laravel Service Provider for NetSuite FileCabinet Flysystem Adapter
 *
 * Registers the NetSuite FileCabinet driver with Laravel's filesystem manager,
 * enabling seamless integration with Laravel's Storage facade. Supports
 * configuration publishing and auto-discovery.
 *
 * @package BajaFoundry\NetSuite\Flysystem\Laravel
 * @author  Baja Foundry <info@baja-foundry.com>
 * @since   1.0.0-beta.1
 */
class NetSuiteFileCabinetServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services
     *
     * Publishes configuration files and registers the NetSuite FileCabinet
     * filesystem driver with Laravel's Storage facade.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/netsuite-filecabinet.php' => config_path('netsuite-filecabinet.php'),
        ], 'netsuite-filecabinet-config');

        Storage::extend('netsuite_filecabinet', function (Application $app, array $config) {
            $client = new NetSuiteClient([
                'base_url' => $config['base_url'],
                'consumer_key' => $config['consumer_key'],
                'consumer_secret' => $config['consumer_secret'],
                'token_id' => $config['token_id'],
                'token_secret' => $config['token_secret'],
                'realm' => $config['realm'],
                'timeout' => $config['timeout'] ?? 30,
            ]);

            $adapter = new NetSuiteFileCabinetAdapter(
                $client,
                $config['root_folder_id'] ?? '',
                $config['prefix'] ?? ''
            );

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }

    /**
     * Register the application services
     *
     * Merges the package configuration with the application's configuration.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/netsuite-filecabinet.php',
            'netsuite-filecabinet'
        );
    }
}
