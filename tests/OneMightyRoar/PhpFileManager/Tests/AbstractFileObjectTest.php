<?php

namespace OneMightyRoar\PhpFileManager\Tests;

use STRIPPED_FROM_HISTORY;

/**
 * AbstractFileObjectTest
 *
 * @uses STRIPPED_FROM_HISTORY
 * @package OneMightyRoar\PhpFileManager\Tests
 */
abstract class AbstractFileObjectTest extends STRIPPED_FROM_HISTORY
{

    const TEST_DATA_FILES_DIRECTORY = '/TestDataFiles/';


    /**
     * Test helpers
     */

    protected function getTestFiles($file = null)
    {
        $files = glob(__DIR__ . self::TEST_DATA_FILES_DIRECTORY . '*');

        if (null !== $file) {
            return $files[$file];
        }

        return $files;
    }

    protected function getTestFileByBaseName($basename)
    {
        $files = $this->getTestFiles();

        $found = null;

        $filtered = array_map(
            function ($key) use ($basename, &$found) {
                if (strpos(basename($key), $basename) !== false) {
                    $found = $key;
                }
            },
            $files
        );

        return $found;
    }

    protected function getTestWrappedBase64($string = null)
    {
        $string = $string ?: 'test text and hot dog flavored water';

        return 'data://text/plain;base64,'. base64_encode($string);
    }
}
