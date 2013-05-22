<?php
/**
 * One Mighty Roar
 *
 * @copyright   2013 One Mighty Roar
 * @link        http://onemightyroar.com
 */

namespace OneMightyRoar\PhpFileManager;

use SplFileObject;
use finfo;
use InvalidArgumentException;
use RuntimeException;
use OneMightyRoar\PhpFileManager\Exceptions\InvalidBase64EncodedDataException;

/**
 * FileObject
 *
 * @uses SplFileObject
 * @package OneMightyRoar\PhpFileManager
 */
class FileObject extends SplFileObject
{

    /**
     * Class constants
     */

    const DEFAULT_NAME = 'temp';
    const DATA_WRAPPER_REGEX = '/^([A-Za-z0-9]+):[\/]*(.*?)(?:;(.*))?,/';

    /**
     * Class properties
     */

    /**
     * The actual "name" of the file object
     *
     * As in, not necessarily the name of a referenced file pointer
     * like how SplFileObject normally treats "filename", but instead
     * a name of a file that you can get and set
     *
     * @var string
     * @access protected
     */
    protected $name;

    /**
     * The MIME type of the file
     *
     * @link http://en.wikipedia.org/wiki/Internet_media_type
     * @var string
     * @access protected
     */
    protected $mime_type;


    /**
     * Class methods
     */

    /**
     * Create an instance of a FileObject from a binary string
     *
     * Allows the creation of a FileObject from a string obtained from a
     * simple "file_get_contents()" call or similar raw file read
     *
     * @param string $raw_binary_data
     * @param string $name
     * @static
     * @access public
     * @return FileObject
     */
    public static function createFromBinary($raw_binary_data, $name = null)
    {
        if (!is_string($raw_binary_data)) {
            throw new InvalidArgumentException('Expected a raw binary string');
        }

        // Create a temporary file name if none was given
        $name = $name ?: static::DEFAULT_NAME;

        // Set a default MIME-type. "octet-stream" is generic and RFC safe
        $mime_type = 'application/octet-stream';

        // Try and auto-detect the MIME-type
        try {
            $mime_type = static::detectMimeTypeFromBuffer($raw_binary_data);
        } catch (RuntimeException $e) {
            // Must have the fileinfo extension loaded to automatically detect the MIME type
        }

        // Wrap our binary data in a SplFileObject compatible data stream
        $stream_wrapped = 'data://'. $mime_type .','. bin2hex($raw_binary_data);

        $object = new static($stream_wrapped, 'r');
        $object->setName($name);
        $object->setMimeType($mime_type);

        return $object;
    }

    /**
     * Create an instance of a FileObject from a base64 encoded string
     *
     * @see FileObject::createFromBinary()
     * @param string $base64_encoded_data
     * @param string $name
     * @static
     * @access public
     * @return FileObject
     */
    public static function createFromBase64Encoded($base64_encoded_data, $name = null)
    {
        if (!is_string($base64_encoded_data)) {
            throw new InvalidArgumentException('Expected a base64 encoded string');
        }

        $decoded = static::base64Decode($base64_encoded_data);

        return static::createFromBinary($decoded, $name);
    }

    /**
     * A consistent and dependable way to decode base64 data
     *
     * @param string $base64_encoded_data
     * @throws InvalidBase64EncodedDataException If the base64 encoded data refuses to decode
     * @static
     * @access public
     * @return string
     */
    public static function base64Decode($base64_encoded_data)
    {
        $decoded = base64_decode(chunk_split($base64_encoded_data), true);

        if ($decoded === false) {
            throw new InvalidBase64EncodedDataException();
        }

        return $decoded;
    }

    /**
     * Get the name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Set the name
     *
     * @param string $name
     * @return FileObject
     */
    public function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * Get the MIME type
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mime_type;
    }
    
    /**
     * Set the MIME type
     *
     * @param string $mime_type
     * @return FileObject
     */
    public function setMimeType($mime_type)
    {
        $this->mime_type = (string) $mime_type;

        return $this;
    }

    /**
     * Quickly check if the "fileinfo" extension has been loaded
     *
     * @throws RuntimeException If the "fileinfo" extension is not loaded or is disabled
     * @static
     * @access protected
     * @return void
     */
    protected static function checkFileinfoExtension()
    {
        // Make sure that the "fileinfo" extension is loaded/enabled
        if (!extension_loaded('fileinfo')) {
            throw new RuntimeException('Required "fileinfo" extension not loaded');
        }
    }

    /**
     * Try and detect the MIME type of the file
     *
     * @access public
     * @return string
     */
    public function detectMimeType()
    {
        static::checkFileinfoExtension();

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        return ($this->isWrapped() ?
            $finfo->buffer($this->getRaw())
            : $finfo->file($this->getPathname())
        );
    }

    /**
     * Try and detect the MIME type from a passed string buffer
     *
     * @param string $buffer The string buffer to detect the MIME type from
     * @static
     * @access public
     * @return string
     */
    public static function detectMimeTypeFromBuffer($buffer)
    {
        static::checkFileinfoExtension();

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        return $finfo->buffer($buffer);
    }

    /**
     * Get information about the data wrapper/protocol
     * used for the current file object
     *
     * @access public
     * @return array[string]
     */
    public function getWrapperInfo()
    {
        // Only get the first 100 characters of the pathname,
        // as it could be a full hex representation of a file
        $pathname = substr($this->getPathname(), 0, 100);

        preg_match(static::DATA_WRAPPER_REGEX, $pathname, $matches);

        if (count($matches) < 1) {
            return false;
        }

        // Give the array matches keys
        return array(
            'wrapper' => $matches[0],
            'protocol' => $matches[1],
            'MIME' => $matches[2],
            'base64' => !empty($matches[3]),
        );
    }

    /**
     * Quick check to see if the file object was created
     * from a protocol wrapper
     *
     * @access public
     * @return boolean
     */
    public function isWrapped()
    {
        return ($this->getWrapperInfo() !== false);
    }

    /**
     * Quick check to see if the file object was created
     * from a base64-style protocol wrapper
     *
     * @access public
     * @return boolean
     */
    public function isWrappedBase64()
    {
        if ($this->isWrapped()) {
            $info = $this->getWrapperInfo();

            return $info['base64'];
        }

        return false;
    }

    /**
     * Quick check to see if the file object was created
     * from a hexadecimal encoded protocol wrapper
     *
     * @access public
     * @return boolean
     */
    public function isWrappedHex()
    {
        if ($this->isWrapped()) {

            return ctype_xdigit(
                // Grab the wrapped data, but strip the protocol from the beginning
                ltrim(strstr($this->getPathname(), ','), ',')
            );
        }

        return false;
    }

    /**
     * Get the file's data as a string
     *
     * WARNING! NOTE! This can be very memory intensive, as it loops and stores
     * the contents of the file object as a string variable
     *
     * @access public
     * @return string
     */
    public function getRaw()
    {
        $this->rewind();

        $raw = '';

        while ($this->valid()) {
            $raw .= $this->fgets();
        }

        if ($this->isWrappedHex()) {
            return hex2bin($raw);
        }

        return $raw;
    }

    /**
     * Get the file data as a base64 encoded string
     *
     * By default, the base64 encoded string is split into new-lines to conform
     * with the specification in RFC 2045
     *
     * @param boolean $chunked Should we split the encoded data to conform to RFC 2045?
     * @access public
     * @return string
     */
    public function getBase64($chunked = true)
    {
        return ($chunked ?
            chunk_split(base64_encode($this->getRaw()))
            : base64_encode($this->getRaw())
        );
    }


    /**
     * Quick alias methods
     */

    /**
     * Check if the file is an image, based on its MIME-type
     *
     * @access public
     * @return boolean
     */
    public function isImage()
    {
        $mime_type = $this->getMimeType() ?: $this->detectMimeType();

        return (strpos($mime_type, 'image') === 0);
    }

    /**
     * Check if the file is audio, based on its MIME-type
     *
     * @access public
     * @return boolean
     */
    public function isAudio()
    {
        $mime_type = $this->getMimeType() ?: $this->detectMimeType();

        return (strpos($mime_type, 'audio') === 0);
    }

    /**
     * Check if the file is video, based on its MIME-type
     *
     * @access public
     * @return boolean
     */
    public function isVideo()
    {
        $mime_type = $this->getMimeType() ?: $this->detectMimeType();

        return (strpos($mime_type, 'video') === 0);
    }

    /**
     * Check if the file is application, based on its MIME-type
     *
     * @access public
     * @return boolean
     */
    public function isApplication()
    {
        $mime_type = $this->getMimeType() ?: $this->detectMimeType();

        return (strpos($mime_type, 'application') === 0);
    }

    /**
     * Check if the file is text, based on its MIME-type
     *
     * @access public
     * @return boolean
     */
    public function isText()
    {
        $mime_type = $this->getMimeType() ?: $this->detectMimeType();

        return (strpos($mime_type, 'text') === 0);
    }
}
