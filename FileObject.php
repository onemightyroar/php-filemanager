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
use UnexpectedValueException;
use ReflectionObject;
use OneMightyRoar\PhpFileManager\Exceptions\InvalidBase64EncodedDataException;

/**
 * FileObject
 *
 * Class to make dealing with files and protocol wrapped data much easier and convenient
 *
 * @uses SplFileObject
 * @package OneMightyRoar\PhpFileManager
 */
class FileObject extends SplFileObject
{

    /**
     * Class constants
     */

    /**
     * The default name to give protocol wrapped files
     *
     * @const string
     */
    const DEFAULT_NAME = 'temp';

    /**
     * The regular expression used to get info about the wrapper
     *
     * @const string
     */
    const DATA_WRAPPER_REGEX = '/^([A-Za-z0-9]+):[\/]*(.*?)(?:;(.*))?,/';

    /**
     * The default hashing algorithm to use for hashing methods
     *
     * @const string
     */
    const DEFAULT_HASH_ALGO = 'md5';

    /**
     * The default line length to use when limiting the data
     * returned in line-getting methods
     *
     * @note This has the habit of being used in N-1 contexts
     * @const int
     */
    const DEFAULT_MAX_LINE_LENGTH = 8193;


    /**
     * Class properties
     */

    /**
     * The resource types that are compatible with this class
     *
     * @static
     * @var array
     * @access protected
     */
    protected static $compatible_resource_types = array(
        'file',
        'stream',
    );

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
     * The class to use when converting the object to an image
     *
     * @var string
     * @access protected
     */
    protected $image_class;


    /**
     * Class methods
     */

    /**
     * Constructor
     *
     * @see SplFileObject::__construct()
     * @param string $filename
     * @param string $open_mode
     * @param bool $use_include_path
     * @param resource $context
     * @access public
     */
    public function __construct($filename, $open_mode = 'r', $use_include_path = false, $context = null)
    {
        // The SplFileObject constructor doesn't allow NULL contexts... :/
        if (null !== $context) {
            parent::__construct($filename, $open_mode, $use_include_path, $context);
        } else {
            parent::__construct($filename, $open_mode, $use_include_path);
        }


        $this->setName($this->getFilename());

        if ($this->isWrapped()) {
            // Get the MIME-type from the wrapper
            $info = $this->getWrapperInfo();

            $this->setMimeType($info['mime']);
        } else {
            // Try and auto-detect the MIME-type
            try {
                $this->setMimeType($this->detectMimeType());
            } catch (RuntimeException $e) {
                // Must have the fileinfo extension loaded to automatically detect the MIME type
            }
        }
    }

    /**
     * Create an instance of a FileObject from a binary string
     *
     * Allows the creation of a FileObject from a string obtained from a
     * simple "file_get_contents()" call or similar raw file read
     *
     * @param string $raw_binary_data
     * @param string $name
     * @throws InvalidArgumentException If the "$raw_binary_data" isn't a string
     * @static
     * @access public
     * @return FileObject
     */
    public static function createFromBuffer($raw_binary_data, $name = null)
    {
        // TODO: Convert to "is_buffer" or "is_binary" once available (PHP 6)
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
     * @see FileObject::createFromBuffer()
     * @param string $base64_encoded_data
     * @param string $name
     * @throws InvalidArgumentException If the "$base64_encoded_data" isn't a string
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

        return static::createFromBuffer($decoded, $name);
    }

    /**
     * Create an instance of a FileObject from another resource
     *
     * This allows the creation of a FileObject as a new temporary file
     * using another resource as the source of the data
     * 
     * NOTE! This will create a completely different "resource" by copying
     * the data from the passed resource. Make sure the resource is readable
     *
     * @param resource $resource
     * @param string $name
     * @throws InvalidArgumentException If the passed "resource" isn't in fact a resource
     * @throws UnexpectedValueException If the resource type isn't supported
     * @static
     * @access public
     * @return FileObject
     */
    public static function createFromResource($resource, $name = null)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Expected a resource');

        } elseif (!in_array(get_resource_type($resource), static::$compatible_resource_types)) {
            throw new UnexpectedValueException(
                'Incompatible resource type. Expected one of: ('
                . implode(',', static::$compatible_resource_types)
                . '), but was given: '. get_resource_type($resource)
            );
        }

        // Create a temporary file name if none was given
        $name = $name ?: static::DEFAULT_NAME;

        $object = new static('php://temp', 'r+');
        $object->setName($name);

        // Save the position of the passed file pointer
        $resource_position = ftell($resource);

        // Copy the data from the given resource into our new object
        rewind($resource);
        while (!feof($resource)) {
            $object->fwrite(fread($resource, static::DEFAULT_MAX_LINE_LENGTH));
        }

        fseek($resource, $resource_position);

        // Try and auto-detect the MIME-type
        try {
            $object->setMimeType($object->detectMimeType());
        } catch (RuntimeException $e) {
            // Must have the fileinfo extension loaded to automatically detect the MIME type
        }

        return $object;
    }

    /**
     * Create an instance of a FileObject from a generic string
     *
     * This will try its best to create a valid FileObject from a given string
     * by attempting to detect the given type, whether it be a:
     *  - File name
     *  - Native PHP resource
     *  - Binary string buffer
     *  - Base64-encoded string
     *
     * @param string $representation
     * @param string $name
     * @static
     * @access public
     * @return FileObject
     */
    public static function createFromDetectedType($representation, $name = null)
    {
        if (is_resource($representation)) {
            /**
             * Resource types
             */

            return static::createFromResource($representation, $name);

        } elseif (is_string($representation)) {
            /**
             * String types
             */

            // Suppress warnings/errors from path checking functions
            if ((@is_readable($representation) && @is_file($representation))
                || (static::isProtocolWrappedString($representation))) {

                $object = new static($representation);

                if (null !== $name) {
                    $object->setName($name);
                }

                return $object;

            } elseif (static::isBase64String($representation)) {
                return static::createFromBase64Encoded($representation, $name);

            } else {
                // TODO: Convert to "is_buffer" or "is_binary" once available (PHP 6)

                return static::createFromBuffer($representation, $name);
            }
        } else {
            throw new UnexpectedValueException(
                'Incompatible or unknown type. '. get_type($representation)
            );
        }
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
     * Naively check if a string is base64 encoded or not
     *
     * There is NO (at least in my findings) 100% positive way
     * to verify if a string is in fact a base64 encoded string
     * or not, so... this does the best it can to decide
     *
     * @param string $string
     * @static
     * @access public
     * @return boolean
     */
    public static function isBase64String($string)
    {
        return (base64_decode($string, true) !== false);
    }

    /**
     * Check if a string is a "protocol wrapped" string
     *
     * @param string $string
     * @static
     * @access public
     * @return boolean
     */
    public static function isProtocolWrappedString($string)
    {
        // Only get the first 100 characters of the string,
        // as it could be a full hex representation of a file
        $string = substr($string, 0, 100);

        return (bool) preg_match(static::DATA_WRAPPER_REGEX, $string);
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
     * Get the file object as a PHP file resource
     *
     * @note This "copies" the current file as a resource, since the internal
     * file resource (of SplFileObject) isn't available... even through reflection
     * @see fopen()
     * @link http://php.net/manual/en/function.fopen.php
     * @param string $mode
     * @param resource $context
     * @access public
     * @return resource
     */
    public function getResource($mode = 'r', $context = null)
    {
        // PHP's "fopen" ALSO doesn't allow NULL contexts #facepalm
        if (null !== $context) {
            return fopen($this->getPathname(), $mode, false, $context);
        } else {
            return fopen($this->getPathname(), $mode, false);
        }
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
            'mime' => $matches[2],
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
                substr(strstr($this->getPathname(), ','), 1)
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
        // Save our current position
        $position = $this->ftell();

        $this->rewind();

        $raw = '';

        while ($this->valid()) {
            $raw .= $this->fgets();
        }

        // Go back to our original position
        $this->fseek($position);

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
     * Get a hash representation of the file's data
     *
     * @param string $algo Defaults to self::DEFAULT_HASH_ALGO
     * @throws InvalidArgumentException If the "$algo" isn't a system supported hashing algorithm
     * @access public
     * @return string
     */
    public function getHash($algo = self::DEFAULT_HASH_ALGO)
    {
        // Check if the algo is supported
        if (in_array($algo, hash_algos()) !== true) {
            throw new InvalidArgumentException('Hash algorithm not supported by your system');
        }

        return hash($algo, $this->getRaw());
    }

    /**
     * Get a hash representation of the file's name
     *
     * @param string $algo Defaults to self::DEFAULT_HASH_ALGO
     * @throws InvalidArgumentException If the "$algo" isn't a system supported hashing algorithm
     * @access public
     * @return string
     */
    public function getNameHash($algo = self::DEFAULT_HASH_ALGO)
    {
        // Check if the algo is supported
        if (in_array($algo, hash_algos()) !== true) {
            throw new InvalidArgumentException('Hash algorithm not supported by your system');
        }

        $name = $this->getName() ?: $this->getFilename();

        return hash($algo, $name);
    }

    /**
     * Get the extension of the file
     *
     * @param boolean $with_dot Prepend a dot with the extension
     * @access public
     * @return string
     */
    public function getExtension($with_dot = false)
    {
        $extension = null;

        if (null !== $this->getName() && strpos($this->getName(), '.') !== false) {
            $extension = substr(strrchr($this->getName(), '.'), 1);
        } else {
            $extension = parent::getExtension();
        }

        // Is our extension still "empty"?
        if (empty($extension)) {
            // Try to infer our extension based on our MIME-type
            $extension = substr(strrchr($this->getMimeType(), '/'), 1);

            if (strpos($extension, '.') !== false) {
                $extension = substr(strrchr($extension, '.'), 1);
            }
            if (strpos($extension, '-') !== false) {
                $extension = substr(strrchr($extension, '-'), 1);
            }
        }

        if ($with_dot) {
            $extension = '.' . $extension;
        }

        return $extension;
    }

    /**
     * Get an obfuscated name of the file object
     *
     * @param boolean $with_extension Include the extension
     * @access public
     * @return void
     */
    public function getObfuscatedName($with_extension = true)
    {
        if ($with_extension) {
            return $this->getHash() . $this->getExtension(true);
        }

        return $this->getHash();
    }


    /**
     * Sub-class converters
     */

    /**
     * Verify that a class is derived from the current class
     *
     * @param string|object $class
     * @throws UnexpectedValueException If the "$class" isn't a class derived from the current class
     * @access protected
     * @return boolean
     */
    protected function verifyIsDerivedClass($class)
    {
        $derived = true;

        if (is_string($class)) {
            if (!in_array(get_class($this), class_parents($class))) {
                $derived = false;
            }
        } else {
            if (!($class instanceof $this)) {
                $derived = false;
            }
        }

        if (!$derived) {
            // Grab the backtrace
            $backtrace = debug_backtrace();
            $last = $backtrace[1];

            // Emulate a PHP SPL/internal-style exception
            throw new UnexpectedValueException(
                $last['class'] .'::'. $last['function']
                .'() expects parameter to be a class name derived from '
                . get_class($this) .', \''. $last['args'][0] .'\' given'
            );
        }

        return $derived;
    }

    /**
     * Get the image class
     *
     * @return string
     */
    public function getImageClass()
    {
        return $this->image_class;
    }
    
    /**
     * Set the image class
     *
     * This is meant to be compatible with the SplFileInfo implementation
     *
     * @link http://www.php.net/manual/en/splfileinfo.setfileclass.php
     * @see SplFileInfo::setFileClass()
     * @param string $image_class The class name to use
     * @return FileObject
     */
    public function setImageClass($image_class)
    {
        $this->verifyIsDerivedClass($image_class);
        $this->image_class = $image_class;

        return $this;
    }

    /**
     * Get an image file object from this file object
     *
     * @see FileObject::__construct()
     * @see SplFileObject::__construct()
     * @param string $open_mode
     * @param bool $use_include_path
     * @param resource $context
     * @access public
     * @return ImageObject
     */
    public function getImageObject($open_mode = 'r', $use_include_path = false, $context = null)
    {
        if (null !== $this->getImageClass()) {
            $class_name = $this->getImageClass();

            return new $class_name($this->getPathname(), $open_mode, $use_include_path, $context);
        }

        return new ImageObject($this->getPathname(), $open_mode, $use_include_path, $context);
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
