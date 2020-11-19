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
namespace Updater\Package;

use InvalidArgumentException;

/**
 * Version
 * @see https://semver.org/spec/v2.0.0.html
 */
class Version
{
    const PATTERN = '/^([0-9]+)\.([0-9]+)\.([0-9]+)$/';
 
    /**
    * Checks for the next version to be run, so if last update was 1.1.0, then it will update to 1.1.1 before 1.2
    *
    * @param string $currentVersion
    * @return string|null
    */
    public function next(string $currentVersion, array $list): ? string
    {
        return $this->filter($list, $this->getMajorVersion($currentVersion), $currentVersion);
    }

    /**
     * Gets the next version in the next major release
     *
     * @param string $currentVersion
     * @param array $list
     * @return string|null
     */
    public function nextMajor(string $currentVersion, array $list): ?string
    {
        return $this->filter($list, $this->getMajorVersion($currentVersion) + 1, $currentVersion);
    }

    /**
     * Finds the next version in order
     *
     * @param array $list
     * @param integer $currentMajorVersion
     * @param string $currentVersion
     * @return string|null
     */
    private function filter(array $list, int $currentMajorVersion, string $currentVersion): ?string
    {
        foreach ($this->normailizeList($list) as $version) {
            $majorVersion = $this->getMajorVersion($version);
            if ($majorVersion === $currentMajorVersion && version_compare($version, $currentVersion) > 0) {
                return $version;
            }
        }

        return null;
    }

    /**
     * Validates a semantic version
     *
     * @param string $version
     * @return boolean
     */
    public static function validate($version): bool
    {
        return is_string($version) && preg_match(self::PATTERN, $version, $version);
    }

    /**
     * Ensures that the list is in proper order by version and removes any versions which might have string such
     * as dev, alpha beta etc.
     *
     * @param array $list
     * @return array
     */
    private function normailizeList(array $list): array
    {
        // sort the list by version
        usort($list, 'version_compare');
       
        // Remove any release with a string e.g. 1.0.0-alpha
        return array_filter($list, function ($subject) {
            return preg_match(self::PATTERN, $subject);
        });
    }

    /**
     * Gets the major version from a sematic version stirng
     *
     * @param string $version
     * @return integer|null
     */
    private function getMajorVersion(string $version): ? int
    {
        if (preg_match(self::PATTERN, $version, $matches)) {
            return (int) $matches[1];
        }

        throw new InvalidArgumentException('Invalid version ' . $version);
    }
}
