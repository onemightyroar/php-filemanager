<?php

namespace OneMightyRoar\PhpFileManager\Tests;

use STRIPPED_FROM_HISTORY;
use OneMightyRoar\PhpFileManager\FileObject;

/**
 * FileObjectTest
 *
 * @uses STRIPPED_FROM_HISTORY
 * @package OneMightyRoar\PhpFileManager\Tests
 */
class FileObjectTest extends STRIPPED_FROM_HISTORY
{

    const TEST_DATA_FILES_DIRECTORY = '/TestDataFiles/';

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

    public function testCreateFromBinary()
    {
        $test_file = $this->getTestFileByBaseName('photo.jpg');

        $binary_file_data = file_get_contents($test_file);
        $test_name = 'testtttttt';

        $file_object = FileObject::createFromBinary($binary_file_data);
        $file_object_with_name = FileObject::createFromBinary($binary_file_data, $test_name);

        $this->assertSame(FileObject::DEFAULT_NAME, $file_object->getName());
        $this->assertSame($test_name, $file_object_with_name->getName());

        $this->assertSame($binary_file_data, $file_object->getRaw(), $file_object_with_name->getRaw());

        return $file_object_with_name;
    }

    public function testCreateFromBase64Encoded()
    {
        $test_string = 'this is a test';
        $test_base64 = base64_encode($test_string);
        $test_name = 'testtttttt';

        $file_object = FileObject::createFromBase64Encoded($test_base64);
        $file_object_with_name = FileObject::createFromBase64Encoded($test_base64, $test_name);

        $this->assertSame(FileObject::DEFAULT_NAME, $file_object->getName());
        $this->assertSame($test_name, $file_object_with_name->getName());

        $this->assertSame($test_string, $file_object->getRaw(), $file_object_with_name->getRaw());

        return $file_object_with_name;
    }

    public function testCreateFromBase64EncodedWithImage()
    {
        $test_file = file_get_contents($this->getTestFileByBaseName('photo.base64'));
        $test_name = 'testtttttt';

        $file_object = FileObject::createFromBase64Encoded($test_file);
        $file_object_with_name = FileObject::createFromBase64Encoded($test_file, $test_name);

        $this->assertSame(FileObject::DEFAULT_NAME, $file_object->getName());
        $this->assertSame($test_name, $file_object_with_name->getName());

        $this->assertSame(base64_decode($test_file), $file_object->getRaw(), $file_object_with_name->getRaw());

        return $file_object_with_name;
    }

    public function testGetSetName()
    {
        $test_name = 'dog';

        $file_object = new FileObject(__FILE__);

        $this->assertNull($file_object->getName());

        $file_object->setName($test_name);

        $this->assertNotNull($file_object->getName());
        $this->assertSame($test_name, $file_object->getName());
    }

    public function testGetSetMimeType()
    {
        $test_mime_type = 'text/html';

        $file_object = new FileObject(__FILE__);

        $this->assertNull($file_object->getMimeType());

        $file_object->setMimeType($test_mime_type);

        $this->assertNotNull($file_object->getMimeType());
        $this->assertSame($test_mime_type, $file_object->getMimeType());
    }

    public function testDetectMimeType()
    {
        $test_file = $this->getTestFileByBaseName('photo.jpg');
        $test_mime = 'image/jpeg';

        $file_object = new FileObject($test_file);

        $this->assertNotNull($file_object->detectMimeType());
        $this->assertSame($test_mime, $file_object->detectMimeType());
    }

    public function testDetectMimeTypeFromBuffer()
    {
        $test_file = file_get_contents($this->getTestFileByBaseName('photo.jpg'));
        $test_mime = 'image/jpeg';

        $this->assertNotNull(FileObject::detectMimeTypeFromBuffer($test_file));
        $this->assertSame($test_mime, FileObject::detectMimeTypeFromBuffer($test_file));
    }

    /**
     * @depends testCreateFromBase64Encoded
     */
    public function testDetectMimeTypeFromBase64($file_object)
    {
        $this->assertNotNull($file_object->detectMimeType());
        $this->assertSame('text/plain', $file_object->detectMimeType());
    }
}
