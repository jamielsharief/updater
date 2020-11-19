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

use Updater\Utility\Json;
use Updater\Package\Composer;
use PHPUnit\Framework\TestCase;

class MockComposer extends Composer
{
    public function set(string $key, $value)
    {
        $this->$key = $value;
    }
}

class ComposerTest extends TestCase
{
    public function testLoadAuth()
    {
        $tmpDirectory = sys_get_temp_dir() . '/' . uniqid();
        mkdir($tmpDirectory, 0775);

        $credentials = [
            'localhost' => [
                'username' => 'admin',
                'password' => 'secret'
            ]
        ];

        $json = new Json($tmpDirectory . '/auth.json');
        $json->save([
            'http-basic' => $credentials
        ]);

        $composer = new Composer($tmpDirectory);
        $this->assertEquals($credentials['localhost'], $composer->auth()['localhost']);
    }

    public function testCredentials()
    {
        $composer = new MockComposer();
        $composer->set('auth', [
            'localhost' => [
                'username' => 'admin',
                'password' => 'secret'
            ]
        ]);
        $this->assertEmpty($composer->credentials('example.com'));
        $this->assertEquals(
            $composer->credentials('localhost'), [
                'username' => 'admin',
                'password' => 'secret'
            ]
        );
        $this->assertEmpty($composer->credentials('example.com'));
    }
}
