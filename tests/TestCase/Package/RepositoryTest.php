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

use Origin\HttpClient\Http;
use Updater\Package\Package;
use PHPUnit\Framework\TestCase;
use Updater\Package\Repository;
use Origin\HttpClient\Exception\ClientErrorException;

/**
 * Intial testing
 */
class RepositoryTest extends TestCase
{
    /**
     * @see https://packagist.org/apidoc
     * @todo cache result so only one request per day
     */
    public function testPackagist()
    {
        $repository = new Repository('https://packagist.org');
        $package = $repository->get('jamielsharief/blockchain'); // TODO: change to updater once committed
        $this->assertInstanceOf(Package::class, $package);
        $this->assertTrue($package->has('0.1.0'));

        return $package;
    }

    /**
     * @depends testPackagist
     *
     * @param Package $package
     * @return void
     */
    public function testPackagistDownload(Package $package)
    {
        $repository = new Repository('https://packagist.org');

        $link = $repository->download($package->url('0.1.0'));
        $this->assertEquals('f90b08592cf091bf82d7b18e30f36b83', hash_file('md5', $link));
    }

    /**
     * This uses the test satis server
     * @see tests/TestServer/README.md
     */
    public function testSatis()
    {
        $repository = new Repository('http://localhost:8000');
        $package = $repository->get('jamielsharief/updater-demo');
        $this->assertInstanceOf(Package::class, $package);
        $this->assertTrue($package->has('0.1.0'));

        return $package;
    }

    /**
     * @depends testSatis
     *
     * @param Package $package
     * @return void
     */
    public function testSatisDownload(Package $package)
    {
        $repository = new Repository('http://127.0.0.1:8000');
       
        $link = $repository->download($package->url('0.1.0'));
        //copy($link, dirname(__DIR__, 3) .  '/tmp/download.zip');
        $this->assertEquals('2d2e554b', hash_file('crc32', $link));
    }

    public function testSatisDownloadAuthenticationUnauthorized()
    {
        $repository = new Repository('http://127.0.0.1:8000');
        $package = $repository->get('jamielsharief/blockchain');
        $this->assertInstanceOf(Package::class, $package);
        $this->assertTrue($package->has('0.1.0'));

        print_r((new Http())->get($package->url('0.1.0')));
        
        $this->expectException(ClientErrorException::class);
        $repository->download($package->url('0.1.0'));
    }

    public function testSatisDownloadAuthentication()
    {
        $repository = new Repository('http://127.0.0.1:8000', [
            'username' => 'user',
            'password' => '1234'
        ]);
        $package = $repository->get('jamielsharief/blockchain');
        $this->assertInstanceOf(Package::class, $package);
        $this->assertTrue($package->has('0.1.0'));

        $link = $repository->download($package->url('0.1.0'));
       
        //copy($link, dirname(__DIR__, 3) .  '/tmp/download.zip');
        $this->assertEquals('7636b283', hash_file('crc32', $link));
    }
}
