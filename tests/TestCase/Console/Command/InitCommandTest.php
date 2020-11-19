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
namespace Updater\Test\TestCase\Console\Command;

use Origin\TestSuite\OriginTestCase;
use Updater\Test\Fixture\AppFixture;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class InitCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public function testInitialize()
    {
        $fixture = new AppFixture('http://127.0.0.1:8000');
        $directory = $fixture->directory();
       
        $this->exec("init {$directory} --version 0.1.0");
        $this->assertExitSuccess();
        $this->assertOutputContains('Updater initialized');

        return $directory;
    }

    /**
     * @depends testInitialize
     *
     * @param string $directory
     * @return void
     */
    public function testInitializeError(string $directory)
    {
        $this->exec("init {$directory} --version 0.1.0");
        $this->assertExitError();
        $this->assertErrorContains('Already initialized');
    }

    public function testInitializeInteractive()
    {
        $fixture = new AppFixture('http://127.0.0.1:8000');
        $directory = $fixture->directory();
       
        $this->exec("init {$directory}", ['0.1.0']);
        $this->assertExitSuccess();
        $this->assertOutputContains('Updater initialized');

        return $directory;
    }
}
