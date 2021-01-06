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
namespace Updater\Utility;

use RuntimeException;

class Json
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @return boolean
     */
    public function exists(): bool
    {
        return file_exists($this->path);
    }

    /**
     * Reads the JSON
     *
     * @return array
     */
    public function read(): array
    {
        return static::parse(file_get_contents($this->path));
    }

    /**
     * Saves data as JSON encoded string
     *
     * @param mixed $data
     * @return boolean
     */
    public function save($data): bool
    {
        return (bool) file_put_contents($this->path, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Json.parse
     *
     * @see https://www.php.net/manual/en/function.json-decode.php
     *
     * @param string $json
     * @param boolean $array
     * @return array|object
     */
    public static function parse(string $json, bool $array = true)
    {
        $data = json_decode($json, $array);

        if (json_last_error()) {
            throw new RuntimeException('Error decoding JSON:  ' . json_last_error_msg());
        }

        return $data;
    }
}
