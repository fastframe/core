<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2004 The Codejanitor Group                        |
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
