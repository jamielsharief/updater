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

use Updater\Package\Package;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Updater\Package\Repository;

class PackageTest extends TestCase
{
    public function testGetInvalidArgument()
    {
        $repository = new Repository('http://127.0.0.1:8000');

        $package = new Package($repository, 'foo/bar', []);
        $this->expectException(InvalidArgumentException::class);
        $package->get('1.0.0');
    }
}
