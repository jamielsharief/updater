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
namespace Updater\Test\TestCase\Utility;

use RuntimeException;
use Updater\Utility\Json;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testParseError()
    {
        $this->expectException(RuntimeException::class);
        Json::parse('{foo');
    }
}
