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
namespace Updater\Test\TestCase;

use RuntimeException;
use Updater\Configuration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testValidates()
    {
        $config = new Configuration([
            'url' => 'http://127.0.0.1:8000',
            'package' => 'foo/bar',
            'scripts' => []
        ]);
        $this->assertTrue($config->validates());
    }

    public function testToString()
    {
        $config = new Configuration([
            'url' => 'http://127.0.0.1:8000',
            'package' => 'foo/bar',
            'scripts' => []
        ]);
        $this->assertIsString((string) $config);
    }

    public function testFromStringException()
    {
        $this->expectException(RuntimeException::class);
        Configuration::fromString('{foo');
    }

    public function testSave()
    {
        $tmpFile = sys_get_temp_dir() . '/' . uniqid();
        $config = new Configuration([
            'url' => 'http://127.0.0.1:8000',
            'package' => 'foo/bar',
            'scripts' => []
        ]);
        $this->assertTrue($config->save($tmpFile));

        return $tmpFile;
    }

    /**
     * @depends testSave
     *
     * @param string $path
     * @return void
     */
    public function testFromString(string $path)
    {
        $config = Configuration::fromString(file_get_contents($path));
        $this->assertEquals('http://127.0.0.1:8000', $config->url);
        $this->assertEquals('foo/bar', $config->package);
        $this->assertEquals([], $config->scripts);
    }
}
