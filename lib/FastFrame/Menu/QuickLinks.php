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

require_once dirname(__FILE__) . '/StaticList.php';

// }}}
// {{{ class FF_Menu_QuickLinks

/**
 * The FF_Menu_QuickLinks:: class generates the quick links menu.
 *
 * This class will search the current app for a quicklinks.php file in the config/ directory
 * and if it finds it will generate a menu using the StaticList menu type.  This is to allow
 * an application to have a set of links which are only shown when the user is in that app.
 *
 * @version Revision: 1.0 
 * @author  Jason Rust <jason@rustyparts.com>
 * @access  public
 * @package Menu 
 */

// }}}
class FF_Menu_QuickLinks extends FF_Menu_StaticList {
    // {{{ properties

    /**
     * The css class we use for the each node
     * @var string
     */
    var $cssClass = 'quickLink';

    /**
     * The template variable to which we assign the menu
     * @var string
     */
    var $tmplVar = 'quick_links';

    // }}}
    // {{{ renderMenu()

    /**
     * Makes sure that the quicklinks file exists before calling the parent::renderMenu()
     *
     * @access public
     * @return void
     */
    function renderMenu()
    {
        if (!file_exists($this->o_registry->getAppFile($this->_getMenuFilename($this->o_registry->getCurrentApp()), null, 'config'))) {
            return;
        }

        parent::renderMenu();
    }

    // }}}
    // {{{ _generateStaticMenu()

    /**
     * Generates the HTML for the static menu. 
     *
     * @access private
     * @return string The HTML for the menu.
     */
    function _generateStaticMenu()
    {
        $s_html = parent::_generateStaticMenu();
        $s_html = preg_replace('/(<\/li>.+?<li)/s', ' || \\1', $s_html);
        return $s_html;
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

        $pth_menu = $this->o_registry->getAppFile($this->_getMenuFilename($this->o_registry->getCurrentApp()), null, 'config');
        if (file_exists($pth_menu) && (filemtime($pth_menu) > $s_cacheMTime)) {
            return false;
        }

        return true;
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
        // Get the profile specific application menu
        if ($this->o_registry->getAppParam('profile', false)) {
            $s_menuFile = sprintf('%s.%s.%s.php', $this->menuType, $this->o_registry->getCurrentApp(),
                    $this->o_registry->getAppParam('profile'));
        }
        else {
            $s_menuFile = sprintf('%s.%s.php', $this->menuType, $this->o_registry->getCurrentApp());
        }

        $this->cacheFile = "menu/$s_menuFile";
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
        $this->menuVariables = array();
        // Include any additional action IDs this app has
        $pth_actions = $this->o_registry->getAppFile('ActionHandler/actions.php', null, 'libs');
        if (file_exists($pth_actions)) {
            require_once $pth_actions;
        }

        $pth_menu = $this->o_registry->getAppFile($this->_getMenuFilename($this->o_registry->getCurrentApp()), null, 'config');
        if (file_exists($pth_menu)) {
            $a_appMenu = null; 
            require $pth_menu;
            if (is_array($a_appMenu) && isset($a_appMenu[0])) {
                $this->menuVariables[] = array(
                    'app' => $this->o_registry->getCurrentApp(),
                    'vars' => $a_appMenu,
                );
            }
            else {
                return trigger_error('The menu file ' . basename($pth_menu) . ' for the ' . $this->o_registry->getCurrentApp() . ' application is not configured correctly.', E_USER_WARNING);
            }
        }
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
            return sprintf('%s/quicklinks.php', 
                    $this->o_registry->getAppParam('profile', null, $in_app));
        }
        else {
            return 'quicklinks.php';
        }
    }

    // }}}
}
?>
