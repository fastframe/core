<?php
/** $Id: Result.php,v 1.2 2003/04/02 00:11:02 jrust Exp $ */
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
// | Authors: Jason Rust <jrust@codejanitor.com>                          |
// +----------------------------------------------------------------------+

// }}}
// {{{ class FF_Result

/**
 * The FF_Result:: class is used whenever an object (such as a form validation or data
 * access object) needs to return a result with whether or not the method was a success and
 * any messages that occurred if it was not.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package FastFrame 
 */

// }}}
class FF_Result {
    // {{{ properties

    /**
     * If it was a success or not 
     * @type bool
     */
    var $success = true;

    /**
     * An array of messages, errors, and warnings that occur during execution
     * @type array 
     */
    var $messages = array();

    // }}}
    // {{{ isSuccess()
    
    /**
     * Returns whether or not the method was successful
     *
     * @access public
     * @return bool True if it was a success, false otherwise
     */
    function isSuccess()
    {
        return $this->success;
    }

    // }}}
    // {{{ setSuccess()

    /**
     * Sets whether or not the method was successful
     *
     * @param bool $in_success True for success, false otherwise
     *
     * @access public
     * @return void
     */
    function setSuccess($in_success)
    {
        $this->success = $in_success;
    }

    // }}}
    // {{{ addMessage()

    /**
     * Adds an error, warning, or normal message to the list
     *
     * @param string $in_message The message to set
     *
     * @access public
     * @return void
     */
    function addMessage($in_message)
    {
        // add it to the beginning of the stack so the last messages show up first on the screen
        array_unshift($this->messages, $in_message);
    }

    // }}}
    // {{{ getMessages()

    /**
     * Returns an array of messages
     *
     * @access public
     * @return array Any messages that have been set
     */
    function getMessages()
    {
        if (count($this->messages) > 0) {
            return $this->messages;
        }
        else {
            return null;
        }
    }

    // }}}
    // {{{ resetMessageStack()

    /**
     * Resets the message stack
     *
     * @access public
     * @return void
     */
    function resetMessageStack()
    {
        $this->messages = array();
    }

    // }}}
}
?>
