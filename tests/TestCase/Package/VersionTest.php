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

use Updater\Package\Version;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    public function testNext()
    {
        $version = new Version();
        $this->assertEquals('1.2.1', $version->next('1.2.0', ['1.2','1.2.1','1.0','1.1.0']));
    }

    public function testNextMajor()
    {
        $version = new Version();
        $this->assertEquals('2.0.0', $version->nextMajor('1.0.0', ['1.0.0','1.1.0','2.0.0','2.1.0']));
    }

    public function testNoVersion()
    {
        $version = new Version();
        $this->expectException(InvalidArgumentException::class);
        $version->next('<-o->', ['1.0.0']);
    }
}
