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

use Origin\HttpClient\Http;
use Origin\HttpClient\Exception\HttpClientException;

class Repository
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $username = null;

    /**
     * @var string
     */
    private $password = null;

    /**
     * @var \Origin\HttpClient\Http
     */
    private $http;

    /**
     * @param string $url
     * @param array $options The following options keys are supported:
     *  - username: http-basic username
     *  - password: http-basic password
     */
    public function __construct(string $url, array $options = [])
    {
        $options += ['username' => null,'password' => null];

        $this->url = rtrim($url, '/');

        $this->username = $options['username'];
        $this->password = $options['password'];
        
        $this->http = new Http($this->httpOptions());
    }

    /**
     * Gets the package information from the repository
     *
     * @param string $package
     * @return \Updater\Package\Package|null
     */
    public function get(string $package): ? Package
    {
        if ($this->isPackagist()) {
            return $this->getFromPackagist($package);
        }

        return $this->getFromSatisServer($package);
    }

    /**
     * @param string $package
     * @return \Updater\Package\Package
     */
    private function getFromPackagist(string $package): Package
    {
        $packages = $this->sendGetRequest("{$this->url}/p/{$package}.json");

        return new Package($this, $packages['packages'][$package]);
    }

    /**
     * @param string $package
     * @return \Updater\Package\Package
     */
    private function getFromSatisServer(string $package): Package
    {
        $data = $this->sendGetRequest("{$this->url}/packages.json");

        $includes = isset($data['includes']) ? array_keys($data['includes']) : [];

        foreach ($includes as $include) {
            $packages = $this->sendGetRequest("{$this->url}/{$include}");
            if (isset($packages['packages'][$package])) {
                return new Package($this, $packages['packages'][$package]);
            }
        }

        throw new HttpClientException('Not Found', 404);
    }

    /**
     * Checks if the a URL belongs to packagist.org
     *
     * @return boolean
     */
    private function isPackagist(): bool
    {
        return parse_url($this->url, PHP_URL_HOST) === 'packagist.org';
    }

    /**
     * Downloads a package and returns a path for the zip file
     *
     * @param string $url
     * @return string
     */
    public function download(string $url): string
    {
        $tmpFile = sys_get_temp_dir() . '/' . uniqid() . '.zip';
 
        $fp = fopen($tmpFile, 'w+');
       
        $this->sendGetRequest($url, [
            'curl' => [
                CURLOPT_FILE => $fp,
                CURLOPT_HEADER => false  #!important
            ]
        ]);

        fclose($fp);

        return $tmpFile;
    }

    /**
     * @param string $url
     * @param array $options
     * @return array|null
     */
    protected function sendGetRequest(string $url, array $options = []): ? array
    {
        $response = $this->http->get($url, $options);

        return $response->ok() ? $response->json() : null;
    }

    /**
     * Options for the HTTP client
     *
     * @return array
     */
    private function httpOptions(): array
    {
        $options = ['userAgent' => 'Updater'];
        
        if ($this->username && $this->password) {
            $options['auth'] = [
                'username' => $this->username,
                'password' => $this->password
            ];
        }

        return $options;
    }
}
