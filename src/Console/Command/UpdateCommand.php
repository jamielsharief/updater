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

class UpdateCommand extends ApplicationCommand
{
    protected $name = 'update';
    protected $description = 'Updates the installation';

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

        $this->addOption('dev', [
            'description' => 'Updates from the development branch',
            'type' => 'boolean'
        ]);
    }

    /**
     * @return void
     */
    protected function execute(): void
    {
        $updater = $this->loadConfiguration();

        $this->loadRepository($updater);

        $count = 0;
        while ($this->process($updater)) {
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

        if (! $nextVersion && $this->options('dev')) {
            $nextVersion = $package->dev();
        }
        
        if (! $nextVersion) {
            return false;
        }

        $this->status('Downloading', $updater->package, $nextVersion);
        $archive = $this->fetchArchive($package, $nextVersion);

        $this->processArchive($archive);

        return true;
    }
}
