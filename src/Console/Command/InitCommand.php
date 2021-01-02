<?php
/**
 * Updater
 * Copyright 2020 Jamiel Sharief.
 *
 * Licensed under The Apache License 2.0
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @license     https://opensource.org/licenses/Apache-2.0 Apache License 2.0
 */
declare(strict_types = 1);
namespace Updater\Console\Command;

use Updater\Utility\Json;
use Updater\Configuration;
use Updater\Package\Version;
use Updater\Package\Repository;
use Origin\HttpClient\Exception\ClientErrorException;

class InitCommand extends ApplicationCommand
{
    protected $name = 'init';
    protected $description = 'Initializes the installation';
    
    protected function initialize(): void
    {
        $this->addArgument('working-directory', [
            'description' => 'The working directory',
            'type' => 'string',
        ]);

        $this->addOption('version', [
            'description' => 'The current version of the installation',
            'type' => 'string'
        ]);
    }

    /**
     * @return void
     */
    protected function execute(): void
    {
        $updater = $this->loadConfiguration();

        $this->repository = new Repository($updater->url, $this->loadCredentials($updater->url));

        if ($this->initialized()) {
            $this->throwError('Already initialized', 'This installation has already been initialized');
        }

        $version = $this->options('version');
    
        while (! Version::validate($version)) {
            $version = $this->io->ask('What version number would like to start at, e.g. 1.0.0?');
        }
        
        if ($this->requiresAuthentication($updater)) {
            $this->io->error('Authentication required');
            $this->askForCredentials($updater);
        }

        $this->lockFile->save([
            'version' => $version,
            'created' => date('Y-m-d H:i:s'),
            'modified' => date('Y-m-d H:i:s')
        ]);

        $this->io->success('Updater initialized');
    }

    /**
     * @param \Updater\Configuration $updater
     * @return boolean
     */
    protected function requiresAuthentication(Configuration $updater): bool
    {
        $needsAuthentication = false;
     
        try {
            $package = $this->repository->get($updater->package);
           
            if ($package->releases()) {
                $package->download($package->releases()[0]);
            }
        } catch (ClientErrorException $exception) {
            $needsAuthentication = $exception->getCode() === 401;

            if ($exception->getCode() === 404) {
                $this->throwError('Not Found', 'Error getting package information');
            }
        }

        return $needsAuthentication;
    }

    /**
     * @param \Updater\Configuration $updater
     * @return void
     */
    protected function askForCredentials(Configuration $updater): void
    {
        $updater = $this->loadConfiguration();

        $auth = new Json($this->workingDirectory . '/auth.json');

        $auth->save([
            'http-basic' => [
                parse_url($updater->url, PHP_URL_HOST) => [
                    'username' => $this->io->ask('username'),
                    'password' => $this->io->ask('password')
                ]
            ]
        ]);
    }
}
