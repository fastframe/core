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
// | Authors: Jason Rust <jrust@codejanitor.com>                          |
// +----------------------------------------------------------------------+

// }}}
// {{{ requires 

require_once dirname(__FILE__) . '/Output.php';

// }}}
// {{{ class FF_Menu

/**
 * The FF_Menu:: class generates a menu using data from each app 
 *
 * The menu class searches each app for a menu.php file.  This file describes any menu
 * entries and the format is as follows:
 * // create one top level menu (can make as many as needed)
 * $a_appMenu[0] = array(
 *    // The description of the element (required).
 *    'contents' => _('Top Level Description'),
 *    // Array of url parameters (optional).  %currentApp% is a
 *    // placeholder that will be replaced with the app name, and can be
 *    // used in any of the settings.  You must have at least 'actionId'.
 *    // Optional parameters are 'module' and 'app' which can specify a
 *    // specific module or application different than the current one.
 *    // If you don't want the element to be a link, leave this element
 *    // out.  If you want to make a straight link to somewhere else make
 *    // the argument a string instead of an array.
 *    'urlParams' => array('app' => '%currentApp%', 'actionId' => LOGOUT_SUBMIT),
 *    // Optional window target.  Defaults to _self
 *    'target' => '_self',
 *    // Optional status text.  Defaults to the text in 'contents' 
 *    'statusText' => _('Status Text'),
 *    // Optional path to an icon. 
 *    'icon' => 'actions/logout.png',
 *    // Optional permissions to apply to this node.  If a string then
 *    // it is a single perm for this app.  If an array then it is treated
 *    // as a list of perm(s) to check.  If an element has 'app' as the key
 *    // then that app name is passed to the hasPerm() method instead of
 *    // the current app name.
 *    'perms' => 'has_groups_app',
 *    // Optional key that affects when this menu item is shown.
 *    // Possible values: 'guest_only' (only non-logged in users will see it),
 *    // 'always' (all users will see it), 'logged_in' (only logged_in users see
 *    // it).  Default is 'logged_in'
 *    'visibility' => 'logged_in',
 *    // If you only want a link to show up when the user in the
 *    // application the link refers to, then you can set:
 *    'app_private' => true,
 *    0 => array(
 *         // first element in menu, same structure as above.
 *         0 => array(
 *             // first sub-element in this menu, same structure as above.
 *         ),
 *    ),
 *    1 => array(
 *         // second element in menu, same structure as above.
 *    ),
 *);
 *
 * The order of each application's items is based on the order it is
 * listed in the root apps.php file in relation to the other registered
 * apps.
 *
 * @version Revision: 1.0 
 * @author  Jason Rust <jason@codejanitor.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_Menu {
    // {{{ properties

    /**
     * Registry instance
     * @var object
     */
    var $o_registry;

    /**
     * The Output object
     * @var object
     */
    var $o_output;

    /**
     * The FileCache object
     * @var object
     */
    var $o_fileCache;

    /**
     * The path and name of the cache file
     * @var string
     */
    var $cacheFile;

    /**
     * The array of menu variables
     * @var array
     */
    var $menuVariables; 

    /**
     * The menu type
     * @var string
     */
    var $menuType;

    /**
     * The placeholder used to specify the current app in the menu.
     * @var string
     */
    var $currentAppPlaceholder = '%currentApp%';

    /**
     * If we are in debug mode we output the menu code formatted nicely. 
     * Otherwise it is condensed with no padding or newlines.
     * @var bool
     */
    var $debug = false;

    // }}}
    // {{{ constructor()

    /**
     * Sets up the initial variables on class initialization.
     *
     * @param string $in_type The type of menu we are creating
     *
     * @access public
     * @return void
     */
    function FF_Menu($in_type)
    {
        $this->o_registry =& FF_Registry::singleton();
        $this->o_output =& FF_Output::singleton();
        $this->o_fileCache =& FF_FileCache::singleton();
        $this->menuType = $in_type;
        $this->_setCacheFile();
    }

    // }}}
    // {{{ factory()

    /**
     * Attempts to return a concrete Menu instance based on $type.
     *
     * @param string $in_type The type of Menu to create.  The code is
     *        dynamically included, and we will look for Menu/$type.php
     *        to load the file.
     *
     * @access public
     * @return object The newly created concrete Menu instance
     */
    function &factory($in_type)
    {
        static $a_instances;
        if (!isset($a_instances)) {
            $a_instances = array();
        }

        $s_class = 'FF_Menu_' . $in_type;
        if (!isset($a_instances[$s_class])) {
            $pth_menu = dirname(__FILE__) . '/Menu/' . $in_type . '.php';
            if (file_exists($pth_menu)) { 
                require_once $pth_menu;
                $a_instances[$s_class] = new $s_class($in_type);
            } 
            else {
                trigger_error("Invalid menu type: $in_type", E_USER_ERROR); 
            }
        }

        return $a_instances[$s_class];
    }

    // }}}
    // {{{ renderMenu()

    /**
     * Renders the menu.
     *
     * @access public
     * @return void
     */
    function renderMenu()
    {
        // interface
    }

    // }}}
    // {{{ _isMenuCached()

    /**
     * Determines if the menu is cached or needs to be updated because other menu variables
     * have been updated.
     *
     * @access private
     * @return bool True if the menu is cached, false if it needs to be generated
     */
    function _isMenuCached()
    {
        if (!file_exists(($pth_file = $this->o_fileCache->getPath($this->cacheFile)))) {
            return false;
        }
        else {
            $s_cacheMTime = filemtime($pth_file);
        }

        // Changing an app setting, such as whether it is enabled can change the menu
        if (filemtime($this->o_registry->getRootFile('apps.php', 'config')) > $s_cacheMTime) {
            return false;
        }

        // See if any of the menu files have been updated
        foreach ($this->o_registry->getApps() as $s_app) {
            // Make sure this app is enabled 
            if ($this->o_registry->getAppParam('status', 'disabled', $s_app) != 'enabled') {
                continue;
            }
                
            $pth_menu = $this->o_registry->getAppFile($this->_getMenuFilename($s_app), $s_app, 'config');
            if (file_exists($pth_menu) && (filemtime($pth_menu) > $s_cacheMTime)) {
                return false;
            }
        }

        return true;
    }

    // }}}
    // {{{ _getCachedMenu()

    /**
     * Fetches the cached menu to a variable.  Makes sure that the php variables the menu
     * relies on (i.e. $o_perms) are set. 
     *
     * @access private
     * @return string The menu data
     */
    function _getCachedMenu()
    {
        require_once dirname(__FILE__) . '/Perms.php';
        $o_perms =& FF_Perms::factory();
        $o_registry =& FF_Registry::singleton();
        $o_output =& FF_Output::singleton();
        ob_start();
        require_once $this->o_fileCache->getPath($this->cacheFile);
        $s_data = ob_get_contents();
        ob_end_clean();
        return $s_data;
    }

    // }}}
    // {{{ _getMenuFilename()

    /**
     * Gets the menu filename for the specified app based on if the specified app has a
     * profile or not.
     *
     * @param string $in_app The app to get the menu filename for
     *
     * @access private
     * @return string The menu filename
     */
    function _getMenuFilename($in_app)
    {
        // Get the profile specific application menu
        if ($this->o_registry->getAppParam('profile', false, $in_app)) {
            return sprintf('%s/menu.php', 
                    $this->o_registry->getAppParam('profile', null, $in_app));
        }
        else {
            return 'menu.php';
        }
    }

    // }}}
    // {{{ _importMenuVars()

    /**
     * Imports the menu array for each registered application
     *
     * @see config/menu.php
     * @access private 
     * @return void
     */
    function _importMenuVars()
    {
        // First just loop and include all actions so all apps can link to other apps
        foreach ($this->o_registry->getApps() as $s_app) {
            // Include any additional action IDs this app has
            $pth_actions = $this->o_registry->getAppFile('ActionHandler/actions.php', $s_app, 'libs');
            if (file_exists($pth_actions)) {
                require_once $pth_actions;
            }
        }

        if ($this->o_registry->getConfigParam('display/home_link')) {
            // Add the home link which takes the user to their default page
            $a_appMenu = array();
            $a_appMenu[] = array(
                'contents' => _('Home'),
                'icon' => 'mini/home.gif',
                'urlParams' => array('actionId' => ACTION_HOME, 'module' => ''));
            $this->menuVariables = array(array('app' => '', 'vars' => $a_appMenu));
        }
        else {
            $this->menuVariables = array();
        }

        // Now loop through enabled apps and get the menu variables
        foreach ($this->o_registry->getApps() as $s_app) {
            // Make sure this app is enabled 
            if ($this->o_registry->getAppParam('status', 'disabled', $s_app) != 'enabled') {
                continue;
            }

            $pth_menu = $this->o_registry->getAppFile($this->_getMenuFilename($s_app), $s_app, 'config');
            if (file_exists($pth_menu)) {
                $a_appMenu = null; 
                require $pth_menu;
                if (is_array($a_appMenu) && isset($a_appMenu[0])) {
                    $this->menuVariables[] = array(
                        'app' => $s_app,
                        'vars' => $a_appMenu,
                    );
                }
                else {
                    return trigger_error('The menu file ' . basename($pth_menu) . ' for the ' . $s_app . ' application is not configured correctly.', E_USER_WARNING);
                }
            }
        }
    }

    // }}}
    // {{{ _processPerms()

    /**
     * Processes any permissions the node may have by wrapping a
     * permissions call around the node if permissions have been
     * specified.  Also processes the visibility key since that is a
     * basic form of permissions.
     *
     * @param array $in_data The data for the node
     * @param string $in_node The node to wrap the perms around
     *
     * @access private
     * @return string The node with any permissions wrapped around it
     */
    function _processPerms($in_data, $in_node)
    {
        $a_ifStatements = array();
        // Possible options for visibility are guest_only, logged_in, and always
        $in_data['visibility'] = isset($in_data['visibility']) ? $in_data['visibility'] : 'logged_in';
        if ($in_data['visibility'] == 'guest_only') {
            $a_ifStatements[] = 'FF_Auth::isGuest()';
        }
        elseif ($in_data['visibility'] == 'logged_in') {
            $a_ifStatements[] = '!FF_Auth::isGuest()';
        }

        if (isset($in_data['perms'])) {
            $s_permsCall = '$o_perms->hasPerm(';
            if (is_array($in_data['perms'])) {
                $s_app = isset($in_data['perms']['app']) ? $in_data['perms']['app'] : $this->currentAppPlaceholder;
                unset($in_data['perms']['app']);
                $s_permsList = '\'' . implode($in_data['perms'], '\',\'') . '\'';
                // first argument is list of perms to check, second is the app
                $s_permsCall .= 'array(' . $s_permsList . '),\'' . $s_app . '\'';
            }
            else {
                // If a string then that is the perm to check
                $s_permsCall .= '\'' . $in_data['perms'] . '\',\'' . $this->currentAppPlaceholder . '\'';
            }

            $s_permsCall .= ')';
            $a_ifStatements[] = $s_permsCall;
        }

        if (count($a_ifStatements) != 0) {
            $tmp_nl = $this->debug ? "\n" : ' '; 
            $in_node = "$tmp_nl<?php if (" . implode(' && ', $a_ifStatements) . ") { ?>$tmp_nl$in_node$tmp_nl<?php } ?>$tmp_nl";
        }

        return $in_node;
    }

    // }}}
    // {{{ _processApps()

    /**
     * Wraps menu entries that are marked as private to an application with check for
     * the currently running application
     *
     * @param array $in_data The data for the node
     * @param string $in_node The node to wrap the application restrictions  around
     *
     * @access private
     * @return string The node with any application restrictions wrapped around it
     */
    function _processApps($in_data, $in_node)
    {

        if (isset($in_data['app_private']) && $in_data['app_private'] == true) {
            $tmp_nl = $this->debug ? "\n" : ' '; 
            $s_appCheck = '$o_registry->getCurrentApp() == \'' . $this->currentAppPlaceholder .  '\'';
            $in_node = "$tmp_nl<?php if ($s_appCheck) { ?>$tmp_nl$in_node$tmp_nl<?php } ?>$tmp_nl";
        }

        return $in_node;
    }

    // }}}
    // {{{ _replaceAppPlaceholder()

    /**
     * Replaces the current app placeholder with the real app name
     *
     * @param string $in_app The app name
     * @param string $in_data The data being replaced
     *
     * @return string The data with the app placeholder replaced
     */
    function _replaceAppPlaceholder($in_app, $in_data)
    {
        // Since the placeholder is used in the url, the placeholder can be urlencoded 
        static $s_holder;
        if (!isset($s_holder)) {
            $s_holder = urlencode($this->currentAppPlaceholder);
        }

        $in_data = str_replace($s_holder, $in_app, $in_data);
        $in_data = str_replace($this->currentAppPlaceholder, $in_app, $in_data);
        return $in_data;
    }

    // }}}
    // {{{ _getLinkUrl()

    /**
     * Generates a url for use in the menu.  If an array is passed in then we assume we are
     * staying in FastFrame.  If an array is not passed in then we assume it is a good URL
     * or an empty string. 
     *
     * @param mixed $in_url The url or query vars
     * @param bool $in_sessionInTags Should the session_id be put in php tags?
     *
     * @access private
     * @return string A url to this app or an outside page 
     */
    function _getLinkUrl($in_url, $in_sessionInTags)
    {
        if (is_array($in_url)) {
            if (!isset($in_url['app'])) {
                $in_url['app'] = $this->currentAppPlaceholder;
            }

            $s_url = FastFrame::url('index.php', $in_url);
            // Can't have the current session id in the url
            if ($in_sessionInTags) {
                $tmp_repl = '<?php echo SID; ?>';
            }
            else {
                $tmp_repl = '\' . SID . \'';
            }

            // Always want the session in the url in case the user doesn't support cookies
            if (strpos($s_url, session_name()) === false) {
                $s_url .= '&' . $tmp_repl;
            }
            else {
                $s_url = preg_replace('/' . session_name() . '=.*?(&|$)/S', "$tmp_repl\\1", $s_url);
            }

            return $s_url;
        }
        else {
            return $in_url;
        }
    }

    // }}}
    // {{{ _formatMenuNodeData()

    /**
     * Takes an array of menu data for an individual node and formats the parameters and
     * then returns the array with the formatted data. 
     *
     * @param array $in_data The node data
     * @param bool $in_sessionInTags Should the session_id be put in php tags?
     *
     * @access private 
     * @return array An array of the data properly formatted.
     */
    function _formatMenuNodeData($in_data, $in_sessionInTags)
    {
        $in_data['contents'] = isset($in_data['contents']) ? 
            addcslashes($in_data['contents'], '\'') : '';
        $in_data['statusText'] = isset($in_data['statusText']) ? 
            $in_data['statusText'] : $in_data['contents'];
        $in_data['icon'] = isset($in_data['icon']) ? 
            addcslashes($this->o_output->imgTag($in_data['icon'], 'none', array('align' => 'bottom')), '\'') . ' ' : '';
        $in_data['target'] = isset($in_data['target']) ? $in_data['target'] : '_self';
        $in_data['urlParams'] = isset($in_data['urlParams']) ? 
            $this->_getLinkUrl($in_data['urlParams'], $in_sessionInTags) : '';

        return $in_data;
    }

    // }}}
    // {{{ _setCacheFile()

    /**
     * Sets the path and name of the cache file.
     *
     * @access private
     * @return void
     */
    function _setCacheFile()
    {
        $this->cacheFile = 'menu/' . $this->menuType . '.php';
    }

    // }}}
}
?>
