<?php
/**
 * One Mighty Roar
 *
 * @copyright   2013 One Mighty Roar
 * @link        http://onemightyroar.com
 */

namespace OneMightyRoar\PhpFileManager;

use UnexpectedValueException;

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
     * Constructor
     *
     * @see FileObject::__construct()
     * @see SplFileObject::__construct()
     * @param string $filename
     * @param string $open_mode
     * @param bool $use_include_path
     * @param resource $context
     * @access public
     */
    public function __construct($filename, $open_mode = 'r', $use_include_path = false, $context = null)
    {
        parent::__construct($filename, $open_mode, $use_include_path, $context);

        if (!$this->isImage() && !$this->isTemp()) {
            throw new UnexpectedValueException('File is not an image');
        }
    }

    /**
     * Get the image as a PHP image resource
     *
     * This method has "dummy" parameters to comply with PHP strict standards, 
     * but does not actually use them in any capacity
     *
     * @see imagecreatefromstring()
     * @link http://www.php.net/manual/en/function.imagecreatefromstring.php
     * @access public
     * @return resource
     */
    public function getImageResource()
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
    public function getImageSize()
    {
        return (($this->isWrapped() || $this->isTemp()) ?
            getimagesizefromstring($this->getRaw())
            : getimagesize($this->getPathname())
        );
    }


    /**
     * Quick alias methods
     */

    /**
     * Alias of $this->getImageSize();
     *
     * @see ImageObject::getSize()
     * @access public
     * @return array
     */
    public function getMeta()
    {
        return $this->getImageSize();
    }

    /**
     * Alias of $this->getImageSize()[0];
     *
     * @see ImageObject::getSize()
     * @access public
     * @return int
     */
    public function getWidth()
    {
        $size = $this->getImageSize();

        return $size[0];
    }

    /**
     * Alias of $this->getImageSize()[1];
     *
     * @see ImageObject::getSize()
     * @access public
     * @return int
     */
    public function getHeight()
    {
        $size = $this->getImageSize();

        return $size[1];
    }

    /**
     * Alias of $this->getImageSize()[2];
     *
     * Get the PHP IMAGETYPE_XXX constant
     *
     * @see ImageObject::getSize()
     * @access public
     * @return int
     */
    public function getType()
    {
        $size = $this->getImageSize();

        return $size[2];
    }

    /**
     * Alias of $this->getImageSize()[3];
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
        $size = $this->getImageSize();

        return $size[3];
    }

    /**
     * Alias of $this->getImageSize()['mime'];
     *
     * Get the MIME-type of the image based on PHP's detection
     *
     * @see ImageObject::getSize()
     * @access public
     * @return int
     */
    public function getMime()
    {
        $size = $this->getImageSize();

        return $size['mime'];
    }

    /**
     * Alias of $this->getImageSize()['channels'];
     *
     * Get the RGB/CMYK channels of the image
     *
     * @see ImageObject::getSize()
     * @access public
     * @return int
     */
    public function getChannels()
    {
        $size = $this->getImageSize();

        return $size['channels'];
    }

    /**
     * Alias of $this->getImageSize()['bits'];
     *
     * Get the bit depth of the image
     *
     * @see ImageObject::getSize()
     * @access public
     * @return int
     */
    public function getBits()
    {
        $size = $this->getImageSize();

        return $size['bits'];
    }
}
