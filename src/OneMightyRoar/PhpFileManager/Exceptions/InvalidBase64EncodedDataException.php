<?php
/**
 * One Mighty Roar
 *
 * @copyright   2013 One Mighty Roar
 * @link        http://onemightyroar.com
 */

namespace OneMightyRoar\PhpFileManager\Exceptions;

use UnexpectedValueException;

/**
 * InvalidBase64EncodedDataException 
 *
 * @uses UnexpectedValueException
 * @package 
 */
class InvalidBase64EncodedDataException extends UnexpectedValueException
{

    /**
     * The exception's default message
     *
     * @var string
     * @access protected
     */
    protected $message = 'Invalid base64 encoded data';
}
