<?php
/** $Id$ */
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
// | Authors: The Horde Team <http://www.horde.org>                       |
// |          Jason Rust <jrust@codejanitor.com>                          |
// |          Dan Allen <dan@mojavelinux.com>                             |
// +----------------------------------------------------------------------+

// }}}
// {{{ includes

require_once dirname(__FILE__) . '/FastFrame/Registry.php';
require_once dirname(__FILE__) . '/FastFrame/Error.php';

// }}}
// {{{ class FastFrame

/**
 * The FastFrame class provides the functionality shared by all FastFrame 
 * applications, such as creating URL's, getting a temporary file, etc.
 * This is a completely static class, all methods can be called directly.
 *
 * @version Revision: 2.0 
 * @author  The Horde Team <http://www.horde.org/>
 * @author  Jason Rust <jrust@codejanitor.com>
 * @author  Dan Allen <dan@mojavelinux.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FastFrame {
    // {{{ string  url()

    /**
     * Return a session-id-ified version of $uri.
     *
     * @param  string $in_url The URL to be modified
     * @param  array  $in_vars (optional) Set of variable = value pairs
     * @param  bool   $in_full (optional) generate a full url?
     *
     * @return string The url with the session id appended
     */
    function url($in_url, $in_vars = array(), $in_full = false)
    {
        if (strpos($in_url, 'javascript:') === 0) {
            return $in_url;
        }

        $o_registry =& FF_Registry::singleton();
        // If the link does not have the webpath, then add it.
        if (strpos($in_url, '/') !== 0 && !preg_match(':^https?\://:', $in_url)) {
            $in_url = $o_registry->getConfigParam('webserver/web_root') . '/' . $in_url;
        }

        // see if we need to make this a full query string
        if ($in_full && !preg_match(':^https?\://:', $in_url)) {
            $in_url = ($o_registry->getConfigParam('webserver/use_ssl') ? 'https' : 'http') . 
                   '://' . $o_registry->getConfigParam('webserver/hostname') . $in_url;
        }

        // Add the session to the array list if it is configured to do so
        if ($o_registry->getConfigParam('session/append')) {
            $sessName = session_name(); 
            // make sure it was not already set (like set to nothing)
            if (!isset($in_vars[$sessName])) {
                // don't lock the user out with an empty session id!
                if (!FastFrame::isEmpty(session_id())) {
                    $in_vars[$sessName] = session_id();
                }
                elseif (!empty($_REQUEST[$sessName])) {
                    $in_vars[$sessName] = $_REQUEST[$sessName]; 
                }
            }
            // if it was set to false then remove it
            elseif (!$in_vars[$sessName]) {
                unset($in_vars[$sessName]);
                $GLOBALS['o_error']->debug($in_vars, 'in_vars', __LINE__, __FILE__);
            }
        }

        foreach($in_vars as $k => $v) {
            $in_vars[$k] = urlencode($k) . '=' . urlencode($v);
        }

        $queryString = implode(ini_get('arg_separator.output'), $in_vars);
        if (!empty($queryString)) {
            $in_url .= (strpos($in_url, '?') === false) ? '?' : ini_get('arg_separator.output');
            $in_url .= $queryString;
        }

        return $in_url;
    }
    
    // }}}
    // {{{ string  selfURL()

    /**
     * Return a session-id-ified version of $PHP_SELF.
     *
     * @param array $in_vars (optional) Set of variable = value pairs
     * @param bool  $in_full (optional) Whether to generate a full or partial link
     *
     * @access public
     * @return string The URL
     */
    function selfURL($in_vars = array(), $in_full = false)
    {
        // Add on the module, app, and actionId to the beginning
        $o_actionHandler =& FF_ActionHandler::singleton();
        $in_vars['app'] = isset($in_vars['app']) ? $in_vars['app'] : $o_actionHandler->getAppId();
        $in_vars['module'] = isset($in_vars['module']) ? $in_vars['module'] : $o_actionHandler->getModuleId();
        $in_vars['actionId'] = isset($in_vars['actionId']) ? $in_vars['actionId'] : $o_actionHandler->getActionId();
        // Add on isPopup if it is set
        if (FF_Request::getParam('isPopup', 'gp', false) && !isset($in_vars['isPopup'])) {
            $in_vars['isPopup'] = 1;
        }

        // Add on the url to the beginning
        return FastFrame::url($_SERVER['PHP_SELF'], $in_vars, $in_full);
    }

    // }}}
    // {{{ void    redirect()
    
    /**
     * Given a url, send the headers to redirect to that site.
     *
     * Perform the necessary action to cause the page to reload with the
     * new url as the location.
     *
     * @param  string $in_url new location
     *
     * @access public
     * @return void
     */
    function redirect($in_url)
    {
        header('Location: ' . $in_url, true);
        exit;
    }

    // }}}
    // {{{ void    fatal

    /**
     * Abort with a fatal error, displaying debug information to the
     * user.
     *
     * @param mixed     $in_message Either a string or a PEAR_Error object.
     * @param int       $in_file The file in which the error occured.
     * @param int       $in_line The line on which the error occured.
     * @param bool      $in_log (optional) Log this message via Horde::logMesage()?
     *
     * @access public
     * @return void
     */
    function fatal($in_message, $in_file, $in_line, $in_log = true)
    {
        $s_errortext = _('<b>A fatal error has occurred:</b>') . "<br /><br />\n";
        if (PEAR::isError($in_message)) {
            $s_errortext .= $in_message->getMessage() . "<br /><br />\n";
            $s_errortext .= $in_message->getDebugInfo() . "<br /><br />\n";
        }
        else {
            $s_errortext .= $in_message . "<br /><br />\n";
        }

        $s_errortext .= sprintf(_('[line %s of %s]'), $in_line, $in_file);

        if ($in_log) {
            $s_errortext .= "<br /><br />\n";
            $s_errortext .= _('Details have been logged for the administrator.');
        }

        // Log the fatal error via FastFrame::logMessage() if requested.
        if ($in_log) {
            FastFrame::logMessage($in_message, $in_file, $in_line, LOG_EMERG);
        }

        // Hardcode a small stylesheet so that this doesn't depend on
        // anything else.
        echo <<< HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>FastFrame :: Fatal Error</title>
<style type="text/css">
  body { font-family: Geneva,Arial,Helvetica,sans-serif; font-size: 12px; background-color: #222244; color: #ffffff; }
  .header { color: #ccccee; background-color: #444466; font-family: Verdana,Helvetica,sans-serif; font-size: 12px; }
</style>
</head>
<body>
<table border="0" align="center" width="500" cellpadding="2" cellspacing="0">
<tr><td class="header" align="center">$s_errortext</td></tr>
</table>
</body>
</html>
HTML;

        exit;
    }

    // }}}
    // {{{ void    logMessage()

    /**
     * Log a message to the global FastFrame log backend.
     *
     * @access public
     *
     * @param mixed     $in_message Either a string or a PEAR_Error object.
     * @param string    $in_file What file was the log function called from (e.g. __FILE__) ?
     * @param int       $in_line What line was the log function called from (e.g. __LINE__) ?
     * @param int       $in_priority  (optional) The priority of the message. One of: LOG_EMERG,
     *                                LOG_ALERT, LOG_CRIT, LOG_ERR, LOG_WARNING, LOG_NOTICE,
     *                                LOG_INFO, LOG_DEBUG
     *
     * @access public
     * @return bool True if logging succeeded, false otherwise
     */
    function logMessage($in_message, $in_file, $in_line, $in_priority = LOG_INFO)
    {
        static $o_logger;
        $o_registry =& FF_Registry::singleton();
        
        if (!$o_registry->getConfigParam('log/enabled') || 
            $in_priority > $o_registry->getConfigParam('log/priority') ||
            !is_readable('Log/Log.php')) {
            return false;
        }

        if (!isset($o_logger)) {
            include_once 'Log/Log.php';

            if (is_null($s_type = $o_registry->getConfigParam('log/type')) ||
                is_null($s_name = $o_registry->getConfigParam('log/name')) ||
                is_null($s_ident = $o_registry->getConfigParam('log/ident')) ||
                is_null($a_params = $o_registry->getConfigParam('log/params'))) {
                PEAR::raiseError(null, FASTFRAME_NOT_CONFIGURED, E_USER_WARNING, 'FastFrame is not correctly configured to log error messages.  You must configure at least a text file in the conf.php file', 'FF_Error', true);
            }

            $o_logger = Log::singleton($s_type, $s_name, $s_ident, $a_params);
        }

        if (PEAR::isError($in_message)) {
            $s_userinfo = $in_message->getUserInfo();
            $s_message = $in_message->getMessage();
            if (!empty($s_userinfo)) {
                $s_message .= ': ' . $s_userinfo;
            }
        }

        $s_message = '[' . $o_registry->getCurrentApp() . '] ' . $s_message . ' [on line ' . $in_line .
' of "' . $in_file . '"]';

        $o_logger->log($s_message, $in_priority);  

        return true;
    }

    // }}}
    // {{{ bool    isEmpty()

    /**
     * Determine if the value of the variable is literally empty, contains no value.
     *
     * This function represents empty() in its true form.  If the variable contains absolutely
     * no data, or is just an empty array, then the boolean value 'true' is returned.  If it
     * has a value of 0 or contains only spaces or endlines (and the strict is not-set) then it
     * returns false.  This is unlike the php-function empty() which just returns if the variable
     * would evaluate as true or false.
     *
     * @param  mixed $in_var variable to be evaluated
     * @param  bool  $in_countWhiteSpace (optional) count whitespace as valid contents
     *
     * @access public
     * @return bool whether the variable has contents or not
     */
    function isEmpty($in_var, $in_countWhiteSpace = true)
    {
        $var = $in_countWhiteSpace || is_array($in_var) ? $in_var : trim($in_var);
        return (!isset($var) || (is_array($var) && empty($var)) || $var === '') ? true : false;
    }

    // }}}
}
?>
