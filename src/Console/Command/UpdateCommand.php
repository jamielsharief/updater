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
            'type' => 'boolean',
            'short' => 'a'
        ]);

        $this->addOption('version', [
            'description' => 'Updates from a specific version',
            'type' => 'string',
            'short' => 'v'
        ]);

        $this->addOption('no-interaction', [
            'description' => 'Do not ask any interactive questions',
            'type' => 'boolean',
            'short' => 'n'
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

        $nextVersion = $this->wantsVersion() ? $this->getVersion($package) : $package->nextVersion($lock['version']);
      
        if (! $nextVersion) {
            return false;
        }

        $this->status('Downloading', $updater->package, $nextVersion);
        $archive = $this->fetchArchive($package, $nextVersion);

        $this->processArchive($archive);

        return true;
    }
}
