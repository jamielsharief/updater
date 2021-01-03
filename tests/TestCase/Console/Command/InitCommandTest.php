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

    public function testRequriesAuth()
    {
        // create fixture that requires auth
        $directory = sys_get_temp_dir() . '/' . uniqid();
        mkdir($directory, 0775);
        $json = '{"url":"http://127.0.0.1:8000","package":"jamielsharief/blockchain","scripts":{"before":[],"after":[]}}';
        file_put_contents($directory. '/updater.json', $json);

        $this->exec("init {$directory} --version 0.1.0", ['foo','bar','user','1234']);

        $this->assertOutputContains('Updater initialized');
        $this->assertErrorContains('Authentication required');
        
        $authPath = $directory. '/auth.json';
        
        $this->assertFileExists($authPath);
        $this->assertEquals('725395d6da46b0d83e666194a74011b2', hash_file('md5', $authPath));
    }
}
