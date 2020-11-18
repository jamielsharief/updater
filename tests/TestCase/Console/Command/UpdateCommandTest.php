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

use Updater\Utility\Json;
use Origin\TestSuite\OriginTestCase;
use Updater\Test\Fixture\AppFixture;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class UpdateCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public function testConfigNotFound()
    {
        $this->exec('update');
        $this->assertExitError();
        $this->assertErrorContains('updater.json not found');
    }

    public function testNotInitialized()
    {
        $fixture = new AppFixture('http://localhost:8000');

        $this->exec('update ' . $fixture->directory());
        $this->assertExitError();
        $this->assertErrorContains('Updater not initialized');
    }

    /**
     * @dataProvider urlProvider
     */
    public function testUpdate(string $url)
    {
        $fixture = new AppFixture($url);
        $directory = $fixture->directory();
        $lockFile = new Json("{$directory}/updater.lock");
       
        $this->exec("init {$directory} --version 0.1.0");
        $this->assertExitSuccess();
        $this->assertOutputContains('Updater initialized');
        
        $this->assertFileExists("{$directory}/updater.lock");
        $this->assertEquals('0.1.0', $lockFile->read()['version']);

        $this->exec("update {$directory}");
        $this->assertExitSuccess();
        $this->assertOutputContains('Processed 1 updates');

        // Check Extraction
        $this->assertFileExists("{$directory}/src/Folder.php");
        $this->assertFileExists("{$directory}/tests/TestCase/FolderTest.php");

        // Check after script was run properly
        $this->assertOutputContains('<white>></white> composer update');
        $this->assertDirectoryExists("{$directory}/vendor/phpunit/phpunit");

        // Check lockfile was updated
        $this->assertEquals('0.2.0', $lockFile->read()['version']);
    }

    /**
     * @depends testUpdate
     */
    public function testUpdateAll()
    {
        $fixture = new AppFixture('http://localhost:8000');
        $directory = $fixture->directory();
        $lockFile = new Json("{$directory}/updater.lock");
       
        $this->exec('init ' .  $fixture->directory() . ' --version 0.1.0');
        $this->assertExitSuccess();
        $this->assertOutputContains('Updater initialized');
    
        $this->exec('update ' . $fixture->directory() .' --all --verbose');
        $this->assertExitSuccess();
        $this->assertOutputContains('Processed 2 updates');
        
        // check extraction, and script running
        $this->assertFileExists("{$directory}/hello.php");
        $this->assertOutputContains('Installing phpunit/phpunit'); // after script 0.2.0
        $this->assertOutputContains('nikic/php-parser'); // before script 0.3.0
        $this->assertOutputContains('application INFO: hello world'); // after script 0.3.0

        // Check lockfile was updated
        $this->assertEquals('0.3.0', $lockFile->read()['version']);
    }

    /**
     * @return array
     */
    public function urlProvider(): array
    {
        return [
            ['http://localhost:8000'],
            ['https://packagist.org'],
        ];
    }
}
