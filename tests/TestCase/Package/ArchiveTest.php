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
namespace Updater\Test\TestCase\Package;

use Origin\Zip\Zip;
use Updater\Package\Archive;
use PHPUnit\Framework\TestCase;
use Updater\Exception\UpdaterException;

class ArchiveTest extends TestCase
{
    public function testConfigNotFound()
    {
        $tmpFile = sys_get_temp_dir() . '/' . uniqid();
        $zip = new Zip();
        $zip->create($tmpFile);
        $zip->add(__FILE__);
        $zip->close();
        $archive = new Archive($tmpFile);

        $this->expectException(UpdaterException::class);
        $archive->config();
    }

    public function testInvalidConfig()
    {
        $configFile = sys_get_temp_dir()  . '/' . 'updater.json';
        file_put_contents($configFile, json_encode(['foo' => 'bar']));
        
        $tmpFile = sys_get_temp_dir() . '/' . uniqid();

        $zip = new Zip();
        $zip->create($tmpFile);
        $zip->add($configFile);
        $zip->close();
        $archive = new Archive($tmpFile);

        $this->expectException(UpdaterException::class);
        $archive->config();
    }
}
