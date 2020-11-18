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
use Updater\Package\Package;
use Updater\Package\Version;
use Updater\Package\Composer;
use Updater\Package\Repository;
use Origin\HttpClient\Exception\HttpException;
use Origin\HttpClient\Exception\ConnectionException;

/**
 * Main command
 */
class UpdateCommand extends ApplicationCommand
{
    protected $name = 'update';
    protected $description = 'Updates the installation';

    /**
     * @var \Updater\Package\Repository
     */
    protected $repository;

    /**
     * Initialize Hook
     *
     * @return void
     */
    protected function initialize(): void
    {
        $this->addArgument('working-directory', [
            'description' => 'The working directory',
            'type' => 'string',
        ]);

        $this->addOption('all', [
            'description' => 'Downloads and processes all available updates',
            'type' => 'boolean'
        ]);
    }

    /**
     * @return void
     */
    protected function execute(): void
    {
        $config = $this->loadConfiguration();

        if (! $this->initialized()) {
            $this->throwError('Updater not initialized', 'Please run <green>updater init</green> to initialize the project.');
        }

        $this->repository = new Repository($config->url, $this->loadCredentials($config->url));

        $count = 0;
        while ($this->process($config)) {
            $count ++;
            if ($this->options('all') === false) {
                break;
            }
        }

        $this->io->nl();

        if ($count) {
            $this->io->success(sprintf('Processed %d updates', $count));
        } else {
            $this->io->warning('No updates found');
        }
    }

    /**
     * @param \Updater\Configuration $updater
     * @return boolean
     */
    private function process(Configuration $updater): bool
    {
        $this->io->nl();

        $lock = $this->lockFile->read();

        $this->status('Checking for updates', $updater->package, $lock['version']);
        $package = $this->fetchPackageInfo($updater->package);
        $nextVersion = $package->nextVersion($lock['version']);
        if (! $nextVersion) {
            return false;
        }

        $this->status('Downloading', $updater->package, $nextVersion);
        $archive = $package->download($nextVersion);

        if (! $archive->hasConfig()) {
            $this->throwError('Invalid Package', sprintf("Package '%s' does not have updater.json", $updater->package));
        }

        $this->runScripts('before', $archive->config());

        $this->status('Extracting', $updater->package, $nextVersion);
        $archive->extract($this->workingDirectory);

        $this->runScripts('after', $archive->config());
        $archive->delete();

        $this->io->out('- Updating lock file');
        $this->updateLockFile($nextVersion);

        return true;
    }

    /**
     * Starts the check for updates process, and returns the package meta
     *
     * @param string $name
     * @return \Updater\Package\Package
     */
    private function fetchPackageInfo(string $name): Package
    {
        try {
            $package = $this->repository->get($name);
        } catch (ConnectionException $exception) {
            $this->throwError('Connection Error', $exception->getMessage());
        } catch (HttpException $exception) {
            if ($exception->getCode() === 404) {
                $this->throwError('Not Found', "Package '{$name}' could not be found");
            }
            $this->throwError('HTTP Error', $exception->getMessage());
        }

        if (! isset($package)) {
            $this->throwError('Unkown Error', 'Error getting package info');
        }

        return $package;
    }

    /**
     * @param string $version
     * @return void
     */
    private function updateLockFile(string $version): void
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
    private function runScripts(string $type, Configuration $config): void
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
     * Displays a status message for a package/version etc
     *
     * @param string $message
     * @param string $package
     * @param string $version
     * @return void
     */
    private function status(string $message, string $package, string $version): void
    {
        $this->io->out(
            sprintf('- <green>%s</green> <white>%s</white> (<yellow>%s</yellow>)', $message, $package, $version)
        );
    }

    /**
     * Loads composer credentials
     *
     * @param string $url
     * @return array
     */
    private function loadCredentials(string $url): array
    {
        return (new Composer($this->workingDirectory))->credentials(
            parse_url($url, PHP_URL_HOST)
        );
    }
}
