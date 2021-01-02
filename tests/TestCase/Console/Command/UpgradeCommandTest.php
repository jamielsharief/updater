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

class UpgradeCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    protected function createFixture()
    {
        $fixture = new AppFixture('http://127.0.0.1:8000');
        $directory = $fixture->directory();
       
        $this->exec("init {$directory} --version 0.1.0");
        $this->assertExitSuccess();
        $this->assertOutputContains('Updater initialized');

        return $fixture;
    }

    public function testUpdatesAreAvailable()
    {
        $fixture = $this->createFixture();
        $directory = $fixture->directory();
        
        $this->exec("upgrade {$directory}");
        $this->assertExitSuccess();
        $this->assertErrorContains('There are updates available');

        return $fixture;
    }

    /**
     * @depends testUpdatesAreAvailable
     *
     * @param AppFixture $fixture
     */
    public function testUpdateBeforeUpgrade(AppFixture $fixture)
    {
        $directory = $fixture->directory();
        $this->exec("update {$directory} --all");
        $this->assertExitSuccess();
        $this->assertOutputContains('Processed 2 updates');

        return $fixture;
    }

    /**
     * @depends testUpdateBeforeUpgrade
     *
     * @param AppFixture $fixture
     */
    public function testUpgrade(AppFixture $fixture)
    {
        $directory = $fixture->directory();
        $this->exec("upgrade {$directory}");
        $this->assertExitSuccess();
        $this->assertOutputContains('Application upgraded');

        return $fixture;
    }

    /**
    * @depends testUpgrade
    *
    * @param AppFixture $fixture
    */
    public function testUpgradeNoUpgrades(AppFixture $fixture)
    {
        $directory = $fixture->directory();
        $this->exec("upgrade {$directory}");
        $this->assertExitSuccess();
        $this->assertErrorContains('No upgrades found');

        return $fixture;
    }

    /**
    * @depends testUpgradeNoUpgrades
    *
    * @param AppFixture $fixture
    * @return void
    */
    public function testUpgradeFromDev(AppFixture $fixture)
    {
        $directory = $fixture->directory();
        $lockFile = new Json("{$directory}/updater.lock");
        $this->assertEquals('1.0.0', $lockFile->read()['version']);

        $this->exec("upgrade {$directory} --dev");
        $this->assertExitSuccess();
        $this->assertOutputContains('<green>Downloading</green> <white>jamielsharief/updater-demo</white> (<yellow>dev-main</yellow>)');
        $this->assertOutputContains('Application upgraded');
     
        // check lockfile was not adjusted
        $this->assertEquals('1.0.0', $lockFile->read()['version']);
    }
}
