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

use RuntimeException;
use Updater\Utility\Json;

/**
 * Composer Helper
 */
class Composer
{
    /**
     * Holds the authentication for http-basic
     *
     * @see https://getcomposer.org/doc/articles/authentication-for-private-packages.md#http-basic
     *
     * @var array
     */
    protected $auth = [];

    /**
     * @var string
     */
    protected $workingDirectory;

    /**
     * @param string $workingDirectory
     */
    public function __construct(string $workingDirectory = null)
    {
        $this->workingDirectory = $workingDirectory ?: getcwd();

        $this->loadAuth();
    }

    /**
     * Attempts to detect the home directory
     *
     * @return string
     */
    protected function homeDirectory(): string
    {
        $env = $this->isWindows() ? 'USERPROFILE' : 'HOME';

        $home = getenv($env);

        if (! $home) {
            throw new RuntimeException('Error getting HOME path');
        }

        return rtrim($home, '/');
    }

    /**
     * Checks if its windoz
     *
     * @see https://www.php.net/manual/en/migration53.global-constants.php
     *
     * @return boolean
     */
    protected function isWindows(): bool
    {
        return defined('PHP_WINDOWS_VERSION_BUILD');
    }

    /**
     * @return string
     */
    public function composerDirectory(): string
    {
        return $this->homeDirectory() . '/.composer';
    }

    /**
     * Checks auth.json details for a domain
     *
     * @example To create from the command line
     *
     * composer config http-basic.example.org username password --global
     *
     * @param string $domain
     * @return array
     */
    public function credentials(string $domain): array
    {
        foreach ($this->auth as $credentialsDomain => $credentials) {
            if ($credentialsDomain === $domain) {
                return $credentials;
            }
        }

        return [];
    }

    /**
     * Loads composer credentials
     *
     * @return void
     */
    protected function loadAuth(): void
    {
        foreach ($this->authFiles() as $file) {
            $config = new Json($file);
            if ($config->exists()) {
                $data = $config->read();
                if (isset($data['http-basic'])) {
                    $this->auth = array_merge($this->auth, $data['http-basic']);
                }
            }
        }
    }

    /**
     * @return array
     */
    protected function authFiles(): array
    {
        return  [
            $this-> composerDirectory() . '/auth.json',
            $this->workingDirectory . '/auth.json'
        ];
    }

    /**
     * Gets the loaded AUTH settings
     *
     * @return array
     */
    public function auth(): array
    {
        return $this->auth;
    }
}
