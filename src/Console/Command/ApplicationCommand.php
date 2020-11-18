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
use Origin\Console\Command\Command;

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
            $this->exit();
        }

        return $path;
    }

    /**
     * Loads the updater.json and returns an instance of the Configuration object
     *
     * @return \Updater\Configuration
     */
    protected function loadConfiguration(string $path = null): Configuration
    {
        $path = $this->configurationPath();

        if (! file_exists($path)) {
            $this->throwError('updater.json not found', 'Could not find updater.json in ' .  dirname($path));
        }

        return Configuration::fromString(file_get_contents($path));
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
