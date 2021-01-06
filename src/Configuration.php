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
namespace Updater;

use RuntimeException;
use Origin\Validation\ValidateTrait;

/**
 * UpdaterConfig
 *
 * TODO: Check out @link https://json-schema.org/
 */
final class Configuration
{
    use ValidateTrait;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $package;

    /**
     * @var string
     */
    public $version;

    /**
     * @var array
     */
    public $scripts = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        $this->validate('url', [
            'required',
            'url'
        ]);

        /**
         * @see https://getcomposer.org/schema.json
         */
        $this->validate('package', [
            'required',
            'custom' => [
                'rule' => ['regex','/^[a-z0-9]([_.-]?[a-z0-9]+)*\/[a-z0-9](([_.]?|-{0,2})[a-z0-9]+)*$/']
            ]
        ]);

        $this->validate('scripts', 'array');
    }

    /**
     * Gets the scripts
     *
     * @param string $type
     * @return array
     */
    public function scripts(string $type): array
    {
        $scripts = [];
        if (isset($this->scripts[$type])) {
            $scripts = (array) $this->scripts[$type];
        }

        return $scripts;
    }

    /**
     * Saves the configuration
     *
     * @param string $path
     * @return boolean
     */
    public function save(string $path): bool
    {
        return (bool) file_put_contents($path, (string) $this);
    }

    /**
     * Creates a new UpdaterConfig from a JSON string
     *
     * @param string $json
     * @return static
     */
    public static function fromString(string $json): Configuration
    {
        $data = json_decode($json, true);
        
        if (json_last_error()) {
            throw new RuntimeException('Error decoding JSON:  ' . json_last_error_msg());
        }

        return new static($data);
    }

    /**
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
    
    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}
