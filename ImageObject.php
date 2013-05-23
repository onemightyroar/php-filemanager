<?php
/**
 * One Mighty Roar
 *
 * @copyright   2013 One Mighty Roar
 * @link        http://onemightyroar.com
 */

namespace OneMightyRoar\PhpFileManager;

/**
 * ImageObject
 *
 * An image file object, designed to make image file operations much easier
 *
 * @uses FileObject
 * @package OneMightyRoar\PhpFileManager
 */
class ImageObject extends FileObject
{

    /**
     * Get the image as a PHP image resource
     *
     * @see imagecreatefromstring()
     * @link http://www.php.net/manual/en/function.imagecreatefromstring.php
     * @access public
     * @return resource
     */
    public function getResource()
    {
        return imagecreatefromstring($this->getRaw());
    }

    /**
     * Get the image object's dimensions meta
     *
     * @see getimagesizefromstring()
     * @link http://www.php.net/manual/en/function.getimagesizefromstring.php
     * @access public
     * @return array
     */
    public function getSize()
    {
        return getimagesizefromstring($this->getRaw());
    }


    /**
     * Quick alias methods
     */

    /**
     * Alias of $this->getSize();
     *
     * @see ImageObject::getSize()
     * @access public
     * @return array
     */
    public function getMeta()
    {
        return $this->getSize();
    }

    /**
     * Alias of $this->getSize()[0];
     *
     * @see ImageObject::getSize()
     * @access public
     * @return int
     */
    public function getWidth()
    {
        $size = $this->getSize();

        return $size[0];
    }

    /**
     * Alias of $this->getSize()[1];
     *
     * @see ImageObject::getSize()
     * @access public
     * @return int
     */
    public function getHeight()
    {
        $size = $this->getSize();

        return $size[1];
    }

    /**
     * Alias of $this->getSize()[2];
     *
     * Get the PHP IMAGETYPE_XXX constant
     *
     * @see ImageObject::getSize()
     * @access public
     * @return int
     */
    public function getType()
    {
        $size = $this->getSize();

        return $size[2];
    }

    /**
     * Alias of $this->getSize()[3];
     *
     * Get the dimensions as an HTML compatible string
     * ex: `width="120" height="120"`
     *
     * @see ImageObject::getSize()
     * @access public
     * @return string
     */
    public function getDimensionsString()
    {
        $size = $this->getSize();

        return $size[3];
    }

    /**
     * Alias of $this->getSize()['mime'];
     *
     * Get the MIME-type of the image based on PHP's detection
     *
     * @see ImageObject::getSize()
     * @access public
     * @return int
     */
    public function getMime()
    {
        $size = $this->getSize();

        return $size['mime'];
    }

    /**
     * Alias of $this->getSize()['channels'];
     *
     * Get the RGB/CMYK channels of the image
     *
     * @see ImageObject::getSize()
     * @access public
     * @return int
     */
    public function getChannels()
    {
        $size = $this->getSize();

        return $size['channels'];
    }

    /**
     * Alias of $this->getSize()['bits'];
     *
     * Get the bit depth of the image
     *
     * @see ImageObject::getSize()
     * @access public
     * @return int
     */
    public function getBits()
    {
        $size = $this->getSize();

        return $size['bits'];
    }
}
