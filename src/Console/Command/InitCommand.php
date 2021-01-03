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
        
        $this->askForCredentialsIfNeeded($updater);

        $this->lockFile->save([
            'version' => $version,
            'created' => date('Y-m-d H:i:s'),
            'modified' => date('Y-m-d H:i:s')
        ]);

        $this->io->success('Updater initialized');
    }

    /**
     * Checks if username/password is required, and then checks if correct, if not
     * it will ask again.
     *
     * @param Configuration $updater
     * @return void
     */
    private function askForCredentialsIfNeeded(Configuration $updater)
    {
        if ($this->requiresAuthentication($updater)) {
            $this->askForCredentials($updater);
            $this->askForCredentialsIfNeeded($updater);
        }
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
}
