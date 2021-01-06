<?php
/**
 * Updater
 * Copyright 2020-2021 Jamiel Sharief.
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
use Updater\Package\Archive;
use Updater\Package\Package;
use Updater\Package\Composer;
use Updater\Package\Repository;
use Origin\Console\Command\Command;
use Origin\HttpClient\Exception\HttpException;
use Origin\HttpClient\Exception\ConnectionException;
use Origin\HttpClient\Exception\ClientErrorException;

class ApplicationCommand extends Command
{
    /**
     * @var string
     */
    protected $workingDirectory;

    /**
     * @var \Updater\Utility\Json
     */
    protected $lockFile;

    /**
     * @var \Updater\Package\Repository
     */
    protected $repository;

    /**
     * @return string
     */
    protected function lockFilePath(): string
    {
        return $this->workingDirectory . '/updater.lock';
    }

    /**
     * @return string
     */
    protected function configurationPath(): string
    {
        return $this->workingDirectory . '/updater.json';
    }

    /**
     * Checks if the project has been initialized
     *
     * @return boolean
     */
    protected function initialized(): bool
    {
        return file_exists($this->lockFilePath());
    }

    /**
     * startup hook
     *
     * @return void
     */
    protected function startup(): void
    {
        $this->io->out('<yellow>' . $this->banner() . '</yellow>');
        
        $workingDirectory = $this->arguments('working-directory');

        if ($workingDirectory) {
            $workingDirectory = $this->normalizePath($workingDirectory);
        }
      
        $this->workingDirectory = $workingDirectory ?? getcwd();

        $this->lockFile = new Json($this->lockFilePath());
    }

    /**
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        $path = realpath($path);
        if (! $path) {
            $this->io->error('Invalid directory');
            $this->abort();
        }

        return $path;
    }

    /**
     * Loads the updater.json and returns an instance of the Configuration object
     *
     * @return \Updater\Configuration
     */
    protected function loadConfiguration(): Configuration
    {
        $path = $this->configurationPath();

        if (! file_exists($path)) {
            $this->throwError('updater.json not found', 'Could not find updater.json in ' .  dirname($path));
        }

        return Configuration::fromString(file_get_contents($path));
    }

    /**
    * Loads composer credentials
    *
    * @param string $url
    * @return array
    */
    protected function loadCredentials(string $url): array
    {
        return (new Composer($this->workingDirectory))->credentials(
            parse_url($url, PHP_URL_HOST)
        );
    }

    /**
    * Displays a status message for a package/version etc
    *
    * @param string $message
    * @param string $package
    * @param string $version
    * @return void
    */
    protected function status(string $message, string $package, string $version): void
    {
        $this->io->out(
            sprintf('- <green>%s</green> <white>%s</white> (<yellow>%s</yellow>)', $message, $package, $version)
        );
    }

    /**
     * Starts the check for updates process, and returns the package meta
     *
     * @param string $name
     * @return \Updater\Package\Package
     */
    protected function fetchPackageInfo(string $name): Package
    {
        $message = 'Error getting package info';

        try {
            return $this->repository->get($name);
        } catch (ConnectionException $exception) {
            $this->throwError('Connection Error', $exception->getMessage());
        } catch (ClientErrorException $exception) {
            if ($exception->getCode() === 404) {
                $this->throwError('Not Found', "Package '{$name}' could not be found");
            }
            $message = $exception->getMessage();
        }
       
        $this->throwError('HTTP Error', $message);
    }

    /**
     * Archive handler
     *
     * @param \Updater\Package\Archive $archive
     * @return void
     */
    protected function processArchive(Archive $archive): void
    {
        $this->runScripts('before', $archive->config());

        $this->status('Extracting', $archive->package(), $archive->version());
        $archive->extract($this->workingDirectory);

        $this->runScripts('after', $archive->config());

        $archive->close();
        $archive->delete();

        if ($this->options('dev') === false) {
            $this->io->out('- Updating lock file');
            $this->updateLockFile($archive->version());
        }
    }

    /**
     * Gets the version archive
     *
     * @param \Updater\Package\Package $package
     * @param string $version
     * @return \Updater\Package\Archive
     */
    protected function fetchArchive(Package $package, string $version): Archive
    {
        try {
            $archive = $package->download($version);
        } catch (HttpException $exception) {
            if (! $this->options('no-interaction') && $exception->getCode() === 401) {
                $this->askForCredentials($this->loadConfiguration());

                return $this->fetchArchive($package, $version);
            }
            $this->throwError('HTTP Error', $exception->getMessage());
        }
        
        if (! $archive->hasConfig()) {
            $this->throwError('Invalid Package', 'Package does not have updater.json');
        }

        return $archive;
    }

    /**
     * @param string $version
     * @return void
     */
    protected function updateLockFile(string $version): void
    {
        $lock['version'] = $version;
        $lock['modified'] = date('Y-m-d H:i:s');
       
        if (! $this->lockFile->save($lock)) {
            $this->throwError('Unable to write to updater.json');
        }
    }

    /**
     * Executes the before and after scripts
     *
     * @param string $type
     * @param \Updater\Configuration $config
     * @return void
     */
    protected function runScripts(string $type, Configuration $config): void
    {
        $this->io->out("- Running <yellow>{$type}</yellow> scripts");
        foreach ($config->scripts($type) as $script) {
            $this->io->out(" <white>></white> {$script}");
            $this->debug($this->executeCommand($script));
        }
    }

    /**
     * Executes a script or command
     *
     * @param string $command
     * @return string|null
     */
    protected function executeCommand(string $command): ?string
    {
        return shell_exec("cd {$this->workingDirectory} && {$command} 2>&1");
    }

    /**
     * Loads the REPO
     *
     * @param \Updater\Configuration $config
     * @return void
     */
    protected function loadRepository(Configuration $config): void
    {
        if (! $this->initialized()) {
            $this->throwError('Updater not initialized', 'Please run <green>updater init</green> to initialize the project.');
        }
        $this->repository = new Repository($config->url, $this->loadCredentials($config->url));
    }

    /**
        * @param \Updater\Configuration $updater
        * @return void
        */
    protected function askForCredentials(Configuration $updater): void
    {
        $auth = new Json($this->workingDirectory . '/auth.json');
        $host = parse_url($updater->url, PHP_URL_HOST);

        $this->io->nl();
        $this->io->error('Authentication required');
        
        $username = $this->io->ask('username');
        $password = $this->io->askSecret('password');
        
        $auth->save([
            'http-basic' => [
                $host => [
                    'username' => $username,
                    'password' => $password
                ]
            ]
        ]);

        $this->repository->setCredentials($username, $password);
    }

    /**
     * @return string
     */
    protected function banner(): string
    {
        return <<< EOT
     __  __          __      __           
    / / / /___  ____/ /___ _/ /____  _____
   / / / / __ \/ __  / __ `/ __/ _ \/ ___/
  / /_/ / /_/ / /_/ / /_/ / /_/  __/ /    
  \____/ .___/\__,_/\__,_/\__/\___/_/     
      /_/       

EOT;
    }
}
