<?php
/** $Id: Error.php,v 1.3 2003/03/19 00:36:01 jrust Exp $ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2003 The Codejanitor Group                        |
// +----------------------------------------------------------------------+
// | This source file is subject to the GNU Lesser Public License (LGPL), |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.fsf.org/copyleft/lesser.html                              |
// | If you did not receive a copy of the LGPL and are unable to          |
// | obtain it through the world-wide-web, you can get it by writing the  |
// | Free Software Foundation, Inc., 59 Temple Place - Suite 330, Boston, |
// | MA 02111-1307, USA.                                                  |
// +----------------------------------------------------------------------+
// | Authors: Dan Allen <dan@mojavelinux.com>                             |
// +----------------------------------------------------------------------+

// }}}
// {{{ error codes

define('FASTFRAME_OK',                 0);
define('FASTFRAME_ERROR',             -1);
define('FASTFRAME_NO_PERMISSIONS',    -2);
define('FASTFRAME_NOT_CONFIGURED',    -3);

// }}}
// {{{ class FF_Error

/**
 * FF_Error Class for Error Handling (using PEAR) of FastFrame
 * 
 * @version Revision: 2.0 
 * @author  Dan Allen <dan@mojavelinux.com>
 * @access public
 */

// }}}
class FF_Error extends PEAR_Error {
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
    function FF_Error($code = FASTFRAME_ERROR, $mode = PEAR_ERROR_RETURN, 
                             $level = E_USER_NOTICE, $debuginfo = null) 
    {
        if (is_int($code)) {
            $this->PEAR_Error(FF_Error::errorMessage($code), $code, $mode, $level, $debuginfo);
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
        return is_a($in_value, 'ff_error');
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
        if (FF_Error::isError($in_value)) {
            $in_value = $in_value->getCode();
        }
        
        // return the textual error message corresponding to the code
        return isset($errorMessages[$in_value]) ? $errorMessages[$in_value] : $errorMessages[FASTFRAME_ERROR];
    }

    // }}}
}
?>
