<?php
/** $Id: Error.php,v 1.1 2003/01/03 22:42:44 jrust Exp $ */
// {{{ error codes

define('FASTFRAME_OK',                 0);
define('FASTFRAME_ERROR',             -1);
define('FASTFRAME_NO_PERMISSIONS',    -2);
define('FASTFRAME_NOT_CONFIGURED',    -3);

// }}}
// {{{ class FastFrame_Error

/**
 * FastFrame_Error Class for Error Handling (using PEAR) of FastFrame
 * 
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lesser.html.
 *
 * @version Revision: 2.0 
 * @author  Dan Allen <dan@mojavelinux.com>
 * @access public
 */

// }}}
class FastFrame_Error extends PEAR_Error {
    // {{{ properties

    /**
     * Message in front of the error message
     * @var string $error_message_prefix
     */
    var $error_message_prefix = 'FastFrame Error: ';
    
    // }}}
    // {{{ constructor

    /**
     * Creates a FastFame error object, extending the PEAR_Error class
     *
     * @param int   $code the xpath error code
     * @param int   $mode (optional) the reaction either return, die or trigger/callback
     * @param int   $level (optional) intensity of the error (PHP error code)
     * @param mixed $debuginfo (optional) information that can inform user as to nature of error
     *
     * @access private
     */
    function FastFrame_Error($code = FASTFRAME_ERROR, $mode = PEAR_ERROR_RETURN, 
                             $level = E_USER_NOTICE, $debuginfo = null) 
    {
        if (is_int($code)) {
            $this->PEAR_Error(FastFrame_Error::errorMessage($code), $code, $mode, $level, $debuginfo);
        } 
        else {
            $this->PEAR_Error("Invalid error code: $code", FASTFRAME_ERROR, $mode, $level, $debuginfo);
        }
    }
 
    // }}}
    // {{{ isError()

    /**
     * Tell whether a result code from a FastFrame method is an error.
     *
     * @param  object  $in_value object in question
     *
     * @access public
     * @return boolean whether object is an error object
     */
    function isError($in_value)
    {
        return is_a($in_value, 'fastframe_error');
    }

    // }}}
    // {{{ errorMessage()

    /**
     * Return a textual error message for an FastFrame error code.
     *
     * @param  int $in_value error code
     *
     * @access public
     * @return string error message, or false if not error code
     */
    function errorMessage($in_value) 
    {
        // make the variable static so that it only has to do the defining on the first call
        static $errorMessages;

        // define the varies error messages
        if (!isset($errorMessages)) {
            $errorMessages = array(
                FASTFRAME_OK                    => 'no error',
                FASTFRAME_ERROR                 => 'unknown error',
                FASTFRAME_NO_PERMISSIONS        => 'improper permissions to perform operation',
                FASTFRAME_NOT_CONFIGURED        => 'fastframe is not properly configured',
            );  
        }

        // If this is an error object, then grab the corresponding error code
        if (FastFrame_Error::isError($in_value)) {
            $in_value = $in_value->getCode();
        }
        
        // return the textual error message corresponding to the code
        return isset($errorMessages[$in_value]) ? $errorMessages[$in_value] : $errorMessages[FASTFRAME_ERROR];
    }

    // }}}
}
?>
