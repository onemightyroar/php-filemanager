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

    public function testGetResource()
    {
        $image_obj = new ImageObject($this->getTestFile());

        $this->assertInternalType('resource', $image_obj->getResource());
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
}
