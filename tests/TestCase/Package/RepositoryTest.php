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
use Updater\Package\Package;
use PHPUnit\Framework\TestCase;
use Updater\Package\Repository;
use Origin\HttpClient\Exception\HttpClientException;
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
        $this->assertEquals('ab204f71', hash_file('crc32', $link));
    }

    /**
     * This uses the test satis server
     * @see tests/TestServer/README.md
     */
    public function testSatis()
    {
        $repository = new Repository('http://127.0.0.1:8000');
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

        $zip = (new Zip())->open($link);
       
        $meta = $package->get('0.1.0');
        $this->assertEquals('jamielsharief/updater-demo', $meta['name']);

        $this->assertZIPFileCount(2, $zip);
        $this->assertZIPHasFiles(['README.md','composer.json'], $zip);
        $this->assertEquals('fafea3b7', hash('crc32', $zip->get('composer.json')));
    }

    /**
     * // TODO: auth not working on travis.
     *
     * @return void
     */
    public function testSatisDownloadAuthenticationUnauthorized()
    {
        $repository = new Repository('http://127.0.0.1:8000');
        $package = $repository->get('jamielsharief/blockchain');
        $this->assertInstanceOf(Package::class, $package);
        $this->assertTrue($package->has('0.1.0'));
       
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
        $zip = (new Zip())->open($link);

        // CRC failing on travisCI so
        $meta = $package->get('0.1.0');
        $this->assertEquals('jamielsharief/blockchain', $meta['name']);
        $this->assertZIPFileCount(17, $zip);
        $this->assertZIPHasFiles(['src/Blockchain.php'], $zip);
        $this->assertEquals('0673c4f2', hash('crc32', $zip->get('src/Block.php')));
    }

    public function testSatisNotFound()
    {
        $repository = new Repository('http://127.0.0.1:8000');
        $this->expectException(HttpClientException::class);
        $repository->get('foo/bar');
    }

    protected function assertZIPFileCount(int $count, Zip $zip)
    {
        $this->assertCount($count, $zip->list());
    }

    protected function assertZIPHasFiles(array $files, Zip $zip)
    {
        foreach ($files as $file) {
            $this->assertZIPHasFile($file, $zip);
        }
    }

    protected function assertZIPHasFile(string $file, Zip $zip)
    {
        $found = false;
        foreach ($zip->list() as $item) {
            if ($item['name'] === $file) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }
}
