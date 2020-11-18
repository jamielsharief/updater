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
namespace Updater\Package;

use Origin\Zip\Zip;
use Updater\Configuration;
use Origin\Filesystem\Folder;
use Updater\Exception\UpdaterException;

class Archive
{
    /**
     * @var \Origin\Zip\Zip
     */
    private $zip;

    /**
     * Path to ZIP archive
     *
     * @var string
     */
    private $path;

    /**
     * Config cache
     *
     * @var \Updater\Configuration
     */
    private $config;

    /**
     * GitHub puts the src in a seperate folder e.g. package-878ec3c, this
     * is to help locate.
     *
     * @var string|null
     */
    private $baseFolder;

    /**
     * @param string $path
     * @param array $options
     */
    public function __construct(string $path, array $options = [])
    {
        $options += ['baseFolder' => null];
        
        $this->zip = new Zip();
        
        $this->path = $path;
      
        $this->zip->open($path);
        $this->baseFolder = $options['baseFolder'];
    }

    /**
     * Checks if the archive has updater.json
     *
     * @return boolean
     */
    public function hasConfig(): bool
    {
        return $this->zip->exists($this->configPath());
    }

    /**
    * Build a Configuration using the updater.json that is in the package
    *
    * @return \Updater\Configuration
    */
    public function config(): Configuration
    {
        if (isset($this->config)) {
            return $this->config;
        }
       
        if (! $this->hasConfig()) {
            throw new UpdaterException('Config file updater.json not found in archive');
        }
        $config = Configuration::fromString($this->zip->get($this->configPath()));

        if (! $config->validates()) {
            throw new UpdaterException('Invalid updater.json');
        }

        return $this->config = $config;
    }

    /**
     * Gets the path for the updater.json
     *
     * @return string
     */
    protected function configPath(): string
    {
        return $this->baseFolder ? $this->baseFolder  . '/updater.json' : 'updater.json';
    }

    /**
     * Extracts the archhive to a directory
     *
     * @param string $path
     * @return void
     */
    public function extract(string $path): void
    {
        $tmpPath = sys_get_temp_dir() . '/archive/' . uniqid();
        mkdir($tmpPath, 0775, true);

        if (! $this->zip->extract($tmpPath)) {
            throw new UpdaterException('Error extracting ZIP');
        }

        if ($this->baseFolder) {
            $tmpPath .= '/' . $this->baseFolder;
        }

        if (! Folder::copy($tmpPath, $path)) {
            throw new UpdaterException('Error copying files');
        }
    }

    /**
     * Close the archive and deletes it
     *
     * @return void
     */
    public function close(): void
    {
        $this->zip->close();
    }

    /**
     * @return boolean
     */
    public function delete(): bool
    {
        return unlink($this->path);
    }
}
