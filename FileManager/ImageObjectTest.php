<?php

namespace OneMightyRoar\PhpFileManager\Tests;

use OneMightyRoar\PhpFileManager\ImageObject;

/**
 * ImageObjectTest
 *
 * @uses AbstractFileObjectTest
 * @package OneMightyRoar\PhpFileManager\Tests
 */
class ImageObjectTest extends AbstractFileObjectTest
{

    protected function getTestFile()
    {
        return $this->getTestFileByBaseName('photo.jpg');
    }

    public function testGetImageResource()
    {
        $image_obj = new ImageObject($this->getTestFile());

        $this->assertInternalType('resource', $image_obj->getImageResource());
    }

    public function testGetSize()
    {
        $image_obj = new ImageObject($this->getTestFile());

        $this->assertCount(7, $image_obj->getSize());

        return $image_obj->getSize();
    }

    /**
     * @depends testGetSize
     */
    public function testGetMeta($size)
    {
        $image_obj = new ImageObject($this->getTestFile());

        $this->assertSame($size, $image_obj->getMeta());
    }

    /**
     * @depends testGetSize
     */
    public function testGetWidth($size)
    {
        $image_obj = new ImageObject($this->getTestFile());

        $this->assertSame($size[0], $image_obj->getWidth());
    }

    /**
     * @depends testGetSize
     */
    public function testGetHeight($size)
    {
        $image_obj = new ImageObject($this->getTestFile());

        $this->assertSame($size[1], $image_obj->getHeight());
    }

    /**
     * @depends testGetSize
     */
    public function testGetType($size)
    {
        $image_obj = new ImageObject($this->getTestFile());

        $this->assertSame($size[2], $image_obj->getType());
    }

    /**
     * @depends testGetSize
     */
    public function testGetDimensionsString($size)
    {
        $image_obj = new ImageObject($this->getTestFile());

        $this->assertSame($size[3], $image_obj->getDimensionsString());
    }

    /**
     * @depends testGetSize
     */
    public function testGetMime($size)
    {
        $image_obj = new ImageObject($this->getTestFile());

        $this->assertSame($size['mime'], $image_obj->getMime());
    }

    /**
     * @depends testGetSize
     */
    public function testGetChannels($size)
    {
        $image_obj = new ImageObject($this->getTestFile());

        $this->assertSame($size['channels'], $image_obj->getChannels());
    }

    /**
     * @depends testGetSize
     */
    public function testGetBits($size)
    {
        $image_obj = new ImageObject($this->getTestFile());

        $this->assertSame($size['bits'], $image_obj->getBits());
    }

    public function testMultipleFormatCompliance()
    {
        $test_file_jpg = $this->getTestFileByBaseName('photo.jpg');
        $test_file_base64 = $this->getTestFileByBaseName('photo.base64');

        $wrapped_binary = ImageObject::createFromBuffer(file_get_contents($test_file_jpg));
        $wrapped_base64 = ImageObject::createFromBase64Encoded(file_get_contents($test_file_base64));
        $raw_binary = new ImageObject($test_file_jpg);
        $raw_base64_text = new ImageObject('data://image/jpeg;base64,'. file_get_contents($test_file_base64));

        // Transitively check equality
        $this->assertSame($wrapped_binary->getMimeType(), $wrapped_base64->getMimeType());
        $this->assertSame($wrapped_binary->getMimeType(), $raw_binary->getMimeType());
        $this->assertSame($wrapped_binary->getMimeType(), $raw_base64_text->getMimeType());

        $this->assertSame($wrapped_binary->getMimeType(), $wrapped_binary->detectMimeType());
        $this->assertSame($wrapped_binary->detectMimeType(), $wrapped_base64->detectMimeType());
        $this->assertSame($wrapped_binary->detectMimeType(), $raw_binary->detectMimeType());
        $this->assertSame($wrapped_binary->detectMimeType(), $raw_base64_text->detectMimeType());

        $this->assertSame($wrapped_binary->detectMimeType(), $wrapped_binary->getMime());
        $this->assertSame($wrapped_binary->getMime(), $wrapped_base64->getMime());
        $this->assertSame($wrapped_binary->getMime(), $raw_binary->getMime());
        $this->assertSame($wrapped_binary->getMime(), $raw_base64_text->getMime());


        $this->assertSame($wrapped_binary->getRaw(), $wrapped_base64->getRaw());
        $this->assertSame($wrapped_binary->getRaw(), $raw_binary->getRaw());
        $this->assertSame($wrapped_binary->getRaw(), $raw_base64_text->getRaw());


        $this->assertSame($wrapped_binary->getBase64(), $wrapped_base64->getBase64());
        $this->assertSame($wrapped_binary->getBase64(), $raw_binary->getBase64());
        $this->assertSame($wrapped_binary->getBase64(), $raw_base64_text->getBase64());
    }
}
