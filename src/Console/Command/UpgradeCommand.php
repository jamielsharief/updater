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

class UpgradeCommand extends ApplicationCommand
{
    protected $name = 'upgrade';
    protected $description = 'Upgrades to the next major release';

    protected function initialize(): void
    {
        $this->addArgument('working-directory', [
            'description' => 'The working directory',
            'type' => 'string',
        ]);

        $this->addOption('version', [
            'description' => 'Updates from a specific version',
            'type' => 'string',
            'short' => 'v'
        ]);
    }

    /**
     * @return void
     */
    protected function execute(): void
    {
        $updater = $this->loadConfiguration();

        $this->loadRepository($updater);

        $lock = $this->lockFile->read();

        $this->status('Checking for upgrades', $updater->package, $lock['version']);
        $package = $this->fetchPackageInfo($updater->package);
        $nextVersion = $package->nextVersion($lock['version']);
        if ($nextVersion) {
            $this->io->nl();
            $this->io->warning('There are updates available');
            $this->io->out('Please update your system first before upgrading');
            $this->exit();
        }

        $nextMajorVersion = $this->wantsVersion() ? $this->getVersion($package) : $package->nextMajorVersion($lock['version']);
 
        if (! $nextMajorVersion) {
            $this->io->nl();
            $this->io->warning('No upgrades found');
            $this->exit();
        }

        $this->status('Downloading', $updater->package, $nextMajorVersion);
        $archive = $this->fetchArchive($package, $nextMajorVersion);

        $this->processArchive($archive);

        $this->io->nl();
        $this->io->success('Application upgraded');
    }
}
