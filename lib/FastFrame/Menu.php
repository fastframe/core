<?php
/** $Id: Menu.php,v 1.1 2003/01/15 01:02:07 jrust Exp $ */
// {{{ includes

require_once dirname(__FILE__) . '/Error.php';
require_once dirname(__FILE__) . '/Registry.php';

// }}}
// {{{ class FastFrame_Menu

/**
 * The FastFrame_Menu:: Menu class gathers the data from the menu.php files in each registered
 * application and parses them into one of several formats.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lesser.html.
 *
 * @version Revision: 1.0 
 * @author  Jason Rust <jason@rustyparts.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FastFrame_Menu {
    // {{{ properties

    /**
     * FastFrame_Registry instance
     * @var object $FastFrame_Registry
     */
    var $FastFrame_Registry;

    /**
     * The array of menu variables
     * @var array $menuVariables
     */
    var $menuVariables = null; 

    /**
     * The placeholder used to specify the current app in the menu.
     * @var string $currentAppPlaceholder
     */
    var $currentAppPlaceholder = '%currentApp%';

    // }}}
    // {{{ constructor()

    /**
     * Sets up the initial variables on class initialization.
     *
     * @access public
     * @return void
     */
    function FastFrame_Menu()
    {
        $this->FastFrame_Registry =& FastFrame_Registry::singleton();
    }

    // }}}
    // {{{ factory()

    /**
     * Attempts to return a concrete Menu instance based on $type.
     *
     * @access public
     *
     * @param string $in_type The type of Menu to create.  The code is dynamically included,
     *                        and we will look for Auth/$type.php to load the file.
     *
     * @return object Menu The newly created concrete Menu instance, or PEAR error on an error.
     */
    function &factory($in_type)
    {
        static $a_instances;

        if (!isset($a_instances)) {
            $a_instances = array();
        }

        $s_type = strtolower($in_type);
        $s_class = 'fastframe_menu_' . strtolower($in_type);
        if (!isset($a_instances[$s_class])) {
            if (@file_exists(dirname(__FILE__) . '/Menu/' . $in_type . '.php')) {
                include_once dirname(__FILE__) . '/Menu/' . $in_type . '.php';
            } 
            else {
                @include_once 'FastFrame/Menu/' . $in_type . '.php';
            }

            if (class_exists($s_class)) {
                $a_instances[$s_class] = new $s_class;
                // call constructor
                $a_instances[$s_class]->$s_class();
            } 
            else {
                return PEAR::raiseError(null, FASTFRAME_ERROR, null, E_USER_ERROR, "Class definition of $s_class not found.", 'FastFrame_Error', true);
            }
        }

        return $a_instances[$s_class];
    }

    // }}}
    // {{{ importMenuVars()

    /**
     * Imports the menu array for each registered application
     *
     * @see config/menu.php
     * @access public
     * @return void
     */
    function importMenuVars()
    {
        if (!$this->isMenuVarsImported()) {
            $this->menuVariables = array();
            // loop through the apps and grab the menu vars for each registered app
            foreach ($this->FastFrame_Registry->getApps() as $s_app) {
                // make sure this app is active
                if ($this->FastFrame_Registry->getAppParam('status', 'active', array('app' => $s_app)) != 'active') {
                    continue;
                }
                    
                $pth_menu = $this->FastFrame_Registry->getAppFile('menu.php', $s_app, 'config');
                if (is_readable($pth_menu)) {
                    $a_appMenu = null; 
                    include_once $pth_menu;
                    if (is_array($a_appMenu) && isset($a_appMenu[0])) {
                        $this->menuVariables[] = array(
                            'app' => $s_app,
                            'vars' => $a_appMenu,
                        );
                    }
                    else {
                        return PEAR::raiseError(null, FASTFRAME_NOT_CONFIGURED, null, E_USER_ERROR, "Menu for $s_app is not configured correctly.", 'FastFrame_Error', true);
                    }
                }
            }
        }
    }

    // }}}
    // {{{ isMenuVarsImported

    /**
     * Tells if the menu vars have been imported yet.
     *
     * @access public
     * @return bool True if they have imported, false otherwise
     */
    function isMenuVarsImported()
    {
        return is_null($this->menuVariables) ? false : true;
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
    // {{{ _get_link_URL()

    /**
     * Generates a URL for use in the menu.  If an array is passed in then we assume we are
     * going to the link page.  The link page takes care of bouncing someone on
     * to the right page so that we don't have to create a full path to the app for every
     * menu item.  If an array is not passed in then we assume it is a good URL or no URL at
     * all.
     *
     * @param mixed $in_queryVars The URL or query vars to tack onto the end of the link URL.
     *
     * @access private
     * @return string The URL to the correct page. 
     */
    function _get_link_URL($in_queryVars)
    {
        static $s_baseURL, $s_argSeparator;
        if (!isset($s_baseURL)) {
            $s_argSeparator = ini_get('arg_separator.output');
            $s_baseURL = FastFrame::url($this->FastFrame_Registry->getRootFile('menuRedirect.php', '', FASTFRAME_WEBPATH));
            // if the URL has no query vars attach one on so we can use the URL easily later
            if (strpos($s_baseURL, '?') === false) {
                $s_baseURL .= '?_fromMenu=1';
            }
            else {
                $s_baseURL .= $s_argSeparator . '_fromMenu=true';
            }
        }

        if (is_array($in_queryVars)) {
            $tmp_arr = array();
            foreach ($in_queryVars as $s_key => $s_val) {
                $tmp_arr[] = $s_key . '=' . $s_val;
            }

            $s_url = $s_baseURL . $s_argSeparator . implode($s_argSeparator, $tmp_arr);
        }
        else {
            $s_url = $in_queryVars;
        }

        return $s_url;
    }

    // }}}
    // {{{ _recurse_menu_vars()

    /**
     * Recursively go through a top level menu array and return the contents for the menu.
     *
     * @see menu.php
     * @access private
     * @return string The menu structure. 
     */
    function _recurse_menu_vars()
    {
        // interface
    }

    // }}}
}
?>