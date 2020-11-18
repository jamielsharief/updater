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
namespace Updater\Test\Fixture;

use RuntimeException;
use Updater\Utility\Json;
use Origin\Filesystem\Folder;

class AppFixture
{
    protected $url;
    protected $directory;
    
    public function __construct(string $url)
    {
        $this->url = $url;
        $this->directory = sys_get_temp_dir() . '/testing/' . uniqid();

        mkdir($this->directory, 0775, true);
        if (! Folder::copy(dirname(__DIR__, 1) . '/Fixture/app', $this->directory)) {
            throw new RuntimeException('Error creating directory');
        }
        $updater = new Json($this->directory .'/updater.json');
        $data = $updater->read();
        $data['url'] = $this->url;
        $updater->save($data);
    }

    /**
     * Unloads the Fixture
     *
     * @return void
     */
    public function delete()
    {
        Folder::delete($this->directory(), ['recursive' => true]);
    }

    /**
     * Directory helper
     *
     * @return string
     */
    public function directory(): string
    {
        return $this->directory;
    }
}
