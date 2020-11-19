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
use Updater\Configuration;
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
        $fixture = new AppFixture('http://127.0.0.1:8000');

        $this->exec('update ' . $fixture->directory());
        $this->assertExitError();
        $this->assertErrorContains('Updater not initialized');
    }

    public function testNormailzePathError()
    {
        $this->exec('update /etc/password');
        $this->assertExitError();
        $this->assertErrorContains('Invalid directory');
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

        $this->exec("update {$directory} --verbose");
        $this->assertExitSuccess();
        $this->assertOutputContains('Processed 1 updates');

        // Check Extraction
        $this->assertFileExists("{$directory}/src/Folder.php");
        $this->assertFileExists("{$directory}/tests/TestCase/FolderTest.php");

        // Check after script was run properly
        $this->assertOutputContains('<white>></white> composer update');
        $this->assertOutputContains('Loading composer repositories with package information');

        // Check lockfile was updated
        $this->assertEquals('0.2.0', $lockFile->read()['version']);
    }

    /**
     * TODO: depends testUpdate
     */
    public function testUpdateAll()
    {
        $fixture = new AppFixture('http://127.0.0.1:8000');
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

        return $directory;
    }

    /**
     * @depends testUpdateAll
     *
     * @param string $directory
     * @return void
     */
    public function testUpdateAllNoMore(string $directory)
    {
        $this->exec('update ' . $directory);
        $this->assertExitSuccess();
        $this->assertErrorContains('No updates found');
    }

    /**
     * Create and initialze
     *
     * @return AppFixture
     */
    protected function createAppFixture(string $version = '0.1.0')
    {
        $fixture = new AppFixture('http://127.0.0.1:8000');
        $directory = $fixture->directory();
    
        $this->exec("init {$directory} --version {$version}");
        $this->assertExitSuccess();
        $this->assertOutputContains('Updater initialized');

        return $fixture;
    }

    /**
     * 1st release does not include updater.json
     */
    public function testInvalidPackage()
    {
        $fixture = $this->createAppFixture('0.0.0');
     
        $this->exec('update ' . $fixture->directory());
        $this->assertExitError();
        $this->assertErrorContains("Package 'jamielsharief/updater-demo' does not have updater.json");
    }

    public function testFetchPackgeConnectionError()
    {
        $fixture = $this->createAppFixture();
        $filename = $fixture->directory() . '/updater.json';

        // modify fixture to cause error
        $config = Configuration::fromString(file_get_contents($filename));
        $config->url = 'https://some-domain-that-does-exist.com';
        $config->save($filename);
    
        $this->exec('update ' . $fixture->directory());
        $this->assertExitError();
        $this->assertErrorContains('Connection Error');
    }

    public function testFetchPackgeError404()
    {
        $fixture = $this->createAppFixture();
        $filename = $fixture->directory() . '/updater.json';

        // modify fixture to cause error
        $config = Configuration::fromString(file_get_contents($filename));
        $config->package = 'foo/bar';
        $config->save($filename);
    
        $this->exec('update ' . $fixture->directory());
        $this->assertExitError();
        $this->assertErrorContains("<text>Package 'foo/bar' could not be found</text>");
    }

    public function testDownloadPackage401()
    {
        $fixture = $this->createAppFixture();
        $filename = $fixture->directory() . '/updater.json';

        $config = Configuration::fromString(file_get_contents($filename));
        $config->package = 'jamielsharief/blockchain';
        $config->save($filename);
    
        $this->exec('update ' . $fixture->directory());
        $this->assertExitError();
        $this->assertErrorContains('401 Unauthorized');
    }

    /**
     * @return array
     */
    public function urlProvider(): array
    {
        return [
            ['http://127.0.0.1:8000'],
            ['https://packagist.org'],
        ];
    }
}
