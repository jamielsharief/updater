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

use InvalidArgumentException;

/**
 * Composer Helper
 */
class Package
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var \Updater\Package\Repository
     */
    private $repository;

    /**
     * @param Repository $repository
     * @param array $package
     */
    public function __construct(Repository $repository, string $name, array $package)
    {
        $this->repository = $repository;
        $this->name = $name;
        $this->data = $package;
    }

    /**
     * Checks if a Package has a version
     *
     * @param string $version
     * @return boolean
     */
    public function has(string $version): bool
    {
        return isset($this->data[$version]);
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function releases(): array
    {
        return array_keys($this->data);
    }

    /**
     * Gets the development branch
     *
     * @return string
     */
    public function dev(): string
    {
        return isset($this->data['dev-main']) ? 'dev-main' : 'dev-master';
    }

    /**
     * Gets the URL for the ZIP archive
     *
     * @param string $version
     * @return string
     */
    public function url(string $version): string
    {
        return $this->get($version)['dist']['url'];
    }

    /**
     * Gets the information for a version
     *
     * @param string $version
     * @return array
     */
    public function get(string $version): array
    {
        if (! isset($this->data[$version])) {
            throw new InvalidArgumentException(sprintf('Release %s was not found', $version));
        }

        return $this->data[$version];
    }

    /**
     * Downloads the Archive
     *
     * Zips downloaded from GitHub have the src enclosed in directory using hash. I looked
     * at a repo that uses GitLab and it seems that they do not do this
     *
     * @param string $version
     * @return \Updater\Package\Archive
     */
    public function download(string $version): Archive
    {
        $meta = $this->get($version);
        $url = $meta['dist']['url'];

        $zip = $this->repository->download($url);
       
        $baseFolder = null;
        if (preg_match('/api.github.com/', $url)) {
            $baseFolder = str_replace('/', '-', $meta['name']) . '-' . substr($meta['dist']['reference'], 0, 7);
        }

        return new Archive($zip, ['baseFolder' => $baseFolder,'package' => $this->name, 'version' => $version]);
    }

    /**
     * Gets the next version in this package
     *
     * @param string $currentVersion
     * @return string|null
     */
    public function nextVersion(string $currentVersion): ?string
    {
        return (new Version())->next($currentVersion, $this->releases());
    }

    /**
     * Gets the next major version for this package
     *
     * @param string $currentVersion
     * @return string|null
     */
    public function nextMajorVersion(string $currentVersion): ?string
    {
        return (new Version())->nextMajor($currentVersion, $this->releases());
    }
}
