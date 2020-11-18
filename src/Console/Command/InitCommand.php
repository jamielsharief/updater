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

use Updater\Package\Version;

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
     * Main method
     *
     * @return void
     */
    protected function execute(): void
    {
        $this->loadConfiguration();

        if ($this->initialized()) {
            $this->throwError('Already initialized', 'This installation has already been initialized');
        }

        $version = $this->options('version');
    
        while (! Version::validate($version)) {
            $version = $this->io->ask('What version number would like to start at, e.g. 1.0.0?');
        }
        
        $this->lockFile->save([
            'version' => $version,
            'created' => date('Y-m-d H:i:s'),
            'modified' => date('Y-m-d H:i:s')
        ]);
        $this->io->success('Updater initialized');
    }
}
