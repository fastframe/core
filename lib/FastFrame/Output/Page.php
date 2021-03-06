<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2009 The Codejanitor Group                        |
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
// {{{ class FF_Output_Page

/**
 * The FF_Output_Page:: class provides functions for creating the full
 * HTML page for FastFrame.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @access  public
 * @package Output
 */

// }}}
class FF_Output_Page extends FF_Output {
    // {{{ properties

    /**
     * The overall template instance
     * @var object
     */
    var $o_tpl;

    /**
     * The page name used to make the title more descriptive
     * @var string
     */
    var $pageName;

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function FF_Output_Page()
    {
        parent::FF_Output();
        // Set up master template
        $this->o_tpl = new FF_Smarty('overall');
        $this->setDefaults();
        // Include some common js
        $this->addScriptFile($this->o_registry->getRootFile('prototype.js', 'javascript', FASTFRAME_WEBPATH));
        $this->addScriptFile($this->o_registry->getRootFile('core.lib.js', 'javascript', FASTFRAME_WEBPATH));
        $this->addScriptFile($this->o_registry->getRootFile('scriptaculous/effects.js', 'javascript', FASTFRAME_WEBPATH));
        // Prevents E_ALL errors
        $this->o_tpl->assign(array('content_left' => array(), 'content_middle' => array(),
                    'content_right' => array(), 'status_messages' => array(), 'page_explanation' => array()));
    }

    // }}}
    // {{{ display()

    /**
     * Displays the output accumulated thus far.
     *
     * @access public
     * @return void
     */
    function display()
    {
        $this->completeTpl();
        $this->o_tpl->display();
    }

    // }}}
    // {{{ completeTpl()

    /**
     * Completes the template, rendering any necessary menus,
     * javascript, messages, and variables.  Should be called right
     * before rendering the template.
     *
     * @access public
     * @return void
     */
    function completeTpl()
    {
        $this->_renderMessages();
        // The main style sheet
        $this->renderCSS('widgets', $this->o_registry->getRootFile('widgets', 'themes'));
        if (FF_Request::getParam('printerFriendly', 'gp', false)) {
            $this->renderCSS('widgets', $this->o_registry->getRootFile('widgets', 'themes'), 'print.tpl', 'all');
        }
        else {
            // The theme-specific style sheet
            $this->renderCSS($this->theme, $this->themeDir);
            // The print style sheet
            $this->renderCSS('widgets', $this->o_registry->getRootFile('widgets', 'themes'), 'print.tpl', 'print');
        }

        if ($this->pageType != 'popup') {
            if ($this->o_registry->getConfigParam('help/show_link')) {
                $s_help = $this->link(FastFrame::selfURL(array('actionId' => ACTION_CONTACT)), _('Need Help?'));
            }
            else {
                $s_help = '';
            }

            if (!FF_Auth::isGuest()) {
                $o_perms =& FF_Perms::factory();
                if ($o_perms->hasPerm('has_my_profile', 'profile')) {
                    require_once $this->o_registry->getAppFile('ActionHandler/actions.php', 'profile', 'libs');
                    $s_user = $this->link(FastFrame::selfURL(
                                array('actionId' => ACTION_MYPROFILE, 'app' => 'profile', 'module' => 'Profile')),
                            FF_auth::getCredential('username'));
                }
                else {
                    $s_user = '<b>' . FF_Auth::getCredential('username') . '</b>';
                }

                $s_status = sprintf(_('You are logged in as %s.'), $s_user);
            }
            else {
                $s_status = _('You are not logged in.');
            }

            $this->o_tpl->assign(array('T_login_status' => $s_status, 'T_help_link' => $s_help));
        }

        $this->o_tpl->assign(array(
                    'COPYWRITE' => 'Copywrite &#169; 2002-2006 The CodeJanitor Group',
                    'CONTENT_ENCODING' => $this->o_registry->getConfigParam('language/charset', 'ISO-8859-1'),
                    'U_SHORTCUT_ICON' => $this->o_registry->getConfigParam('general/favicon'),
                    'PAGE_TITLE' => $this->getPageTitle(),
                    'LANG' => getenv('LANG')));
        $this->_renderPageType();
        $this->_renderMenus();
        // Needed for some of the AJAX stuff
        $o_actionHandler =& FF_ActionHandler::singleton();
        $this->o_tpl->append('javascript', '<script>Event.observe(window, "load", function() { FastFrame.userId="' . FF_Auth::getCredential('userId') . '"; FastFrame.app="' . $o_actionHandler->getAppId() . '"; FastFrame.module="' . $o_actionHandler->getModuleId() . '"; });</script>');
    }

    // }}}
    // {{{ renderCSS()

    /**
     * Creates a link to the css file (creating it in cache if
     * necessary), and then registers it in the template.
     *
     * @param string $in_theme The theme to make the CSS for
     * @param string $in_themeDir The theme directory
     * @param string $in_styleFile (optional) The style file
     * @param string $in_media (optional) The media type
     *
     * @access public
     * @return void
     */
    function renderCSS($in_theme, $in_themeDir, $in_styleFile = 'style.tpl', $in_media = 'screen')
    {
        $o_fileCache =& FF_FileCache::singleton();
        $s_cssTemplateFile = $in_themeDir . '/' . $in_styleFile;

        // Determine the css file based on the browser
        $s_cssFileName = str_replace('.tpl', '', $in_styleFile) . '.css';
        $a_cssCacheFile = array('subdir' => 'css', 'id' => $in_theme, 'name' => $s_cssFileName);

        // Make the CSS file if needed
        if (!$o_fileCache->exists($a_cssCacheFile) ||
            filemtime($s_cssTemplateFile) > filemtime($o_fileCache->getPath($a_cssCacheFile))) {
            $o_cssWidget = new FF_Smarty($s_cssTemplateFile);
            // We already know the template is out of date
            $o_cssWidget->force_compile = true;
            // Use delimiters that work in css files
            $o_cssWidget->left_delimiter = '{{';
            $o_cssWidget->right_delimiter = '}}';
            $o_cssWidget->assign(array(
                    'THEME_DIR' => $this->o_registry->rootPathToWebPath($in_themeDir),
                    'ROOT_GRAPHICS_DIR' => $this->o_registry->getRootFile('', 'graphics', FASTFRAME_WEBPATH)));
            $o_result =& $o_fileCache->save($o_cssWidget->fetch(), $a_cssCacheFile);
            if (!$o_result->isSuccess()) {
                foreach ($o_result->getMessages() as $s_message) {
                    trigger_error($s_message, E_USER_ERROR);
                }
            }

            $o_fileCache->makeViewableFromWeb($a_cssCacheFile);
        }

        $s_cssURL = $o_fileCache->getPath($a_cssCacheFile, false, FASTFRAME_WEBPATH);
        // register the CSS file
        $this->o_tpl->append('headers', '<link rel="stylesheet" type="text/css" media="' . $in_media . '" href="' . $s_cssURL . '" />');
    }

    // }}}
    // {{{ addScriptFile()

    /**
     * Adds a javascript file to the template.  Keeps track of those
     * files that have been addes so that they are not sent more than
     * once.
     *
     * @param string $in_file The path to the javascript file
     * @access public
     * @return void
     */
    function addScriptFile($in_file)
    {
        static $a_files;
        if (!isset($a_files)) {
            $a_files = array();
        }

        if (!isset($a_files[$in_file])) {
            $a_files[$in_file] = 1;
            $this->o_tpl->append('javascript',
                    '<script type="text/javascript" src="' . $in_file . '"></script>');
        }
    }

    // }}}
    // {{{ getPageTitle()

    /**
     * Constructs the page title
     *
     * @access public
     * @return string The page title
     */
    function getPageTitle()
    {
        $s_title = $this->o_registry->getAppParam('name', 'NOOP');
        $s_page = $this->pageName;

        if ($s_title != $s_page) {
            $s_title = "$s_title &#187; $s_page";
        }

        return $s_title;
    }

    // }}}
    // {{{ setPageName()

    /**
     * Sets the page name (should be called from a included page)
     *
     * @param string $in_name A descriptive page name
     *
     * @access public
     * @return void
     */
    function setPageName($in_name)
    {
        $this->pageName = $in_name;
    }

    // }}}
    // {{{ toggleRow()

    /**
     * Gets the row class based on the count variable passed in.
     *
     * @param int $in_count The row count variable
     *
     * @access public
     * @return string The class name for the row
     */
    function toggleRow($in_count)
    {
        return $in_count % 2 ? 'secondaryRow' : 'primaryRow';
    }

    // }}}
    // {{{ addCalendarFiles()

    /**
     * Adds the Dynarch JavaScript Calendar files to the header output.
     *
     * @param  bool   $in_useStripped (optional) Whether or not to use the
     *                                stripped version of the DYNARCH JS calendar.
     *                                Default is true.
     * @param  string $in_setupFile   (optional) The setup file for this calendar.
     *                                The default is to use fastframe_setup.js
     *                                which is designed to be used with
     *                                HTML_QuickForm_date. The DYNARCH
     *                                calendar-setup.js is also included. This
     *                                ignores the $in_useStripped parameter.
     *                                NOTE: do not include the trailing .js
     * @access public
     * @return void
     */
    function addCalendarFiles($in_useStripped = true, $in_setupFilename = null)
    {
        $s_stripped      = $in_useStripped ? '_stripped' : '';
        $s_setupFilename = $in_setupFilename ? $in_setupFilename : 'fastframe-setup';

        $this->addScriptFile($this->o_registry->getRootFile(
            "calendar/calendar{$s_stripped}.js", 'javascript', FASTFRAME_WEBPATH));
        $this->addScriptFile($this->o_registry->getRootFile(
            "calendar/$s_setupFilename.js", 'javascript', FASTFRAME_WEBPATH));

        // XXX: Is there a better of checking this and converting it properly?
        $s_lang = FF_Locale::selectLang();
        if (preg_match('/utf-?8/i', $s_lang)) {
            $s_lang = preg_replace('/^([^_]+)_.*$/', '\1', $s_lang) . '-utf8';
        }
        else {
            $s_lang = preg_replace('/^([^_]+)_.*$/', '\1', $s_lang);
        }

        // default to english if there is not language file
        $pth_langFile = $this->o_registry->getRootFile(
                            "calendar/lang/calendar-$s_lang.js", 'javascript');
        if (!file_exists($pth_langFile)) {
            $pth_langFile = $this->o_registry->getRootFile(
                                "calendar/lang/calendar-en.js", 'javascript');
        }
        $this->addScriptFile($this->o_registry->rootPathToWebPath($pth_langFile));
        $this->renderCSS('widgets',
                         $this->o_registry->getRootFile('widgets', 'themes'),
                         'calendar.tpl',
                         'screen');
    }

    // }}}
    // {{{ _renderMessages()

    /**
     * Renders any messages that have been set
     *
     * @access private
     * @return void
     */
    function _renderMessages()
    {
        foreach ($this->messages as $s_key => $a_message) {
            $this->o_tpl->append('status_messages', array(
                        'T_status_message' => $a_message[0],
                        'I_status_message' => $this->imgTag($a_message[1], 'mini', array('title' => $a_message[2]))));
        }
    }

    // }}}
    // {{{ _renderPageType()

    /**
     * Renders the page type.  Performs any necessary touches or assignments of data based
     * on the page type.
     *
     * @access private
     * @return void
     */
    function _renderPageType()
    {
        switch ($this->pageType) {
            case 'popup':
                // a minimal page
                $this->menuType = 'none';
                $this->o_tpl->append('css', 'div#leftCol { display: none; } div#middleCol { margin: 0; padding: 0; } td.messageWrap { padding-left: 0; }');
            break;
            case 'normal':
                if ($s_header = $this->_getHeaderText()) {
                    $this->o_tpl->assign('header', $s_header);
                }

                if ($s_footer = $this->_getFooterText()) {
                    $this->o_tpl->assign('footer', $s_footer);
                }
            break;
            default:
            break;
        }
    }

    // }}}
    // {{{ _renderMenus()

    /**
     * Renders the main menu and the quicklinks menu
     *
     * @access private
     * @return void
     */
    function _renderMenus()
    {
        if ($this->menuType != 'none') {
            require_once dirname(__FILE__) . '/../Menu.php';
            // Render the main menu
            $o_menu =& FF_Menu::factory($this->menuType);
            $o_menu->renderMenu();
            // Render the QuickLinks menu
            $o_menu =& FF_Menu::factory('QuickLinks');
            $o_menu->renderMenu();
        }
    }

    // }}}
    // {{{ _getHeaderText()

    /**
     * Gets the header text for the page
     *
     * @access private
     * @return mixed False if ther is no header or header text
     */
    function _getHeaderText()
    {
        $s_header = $this->o_registry->getConfigParam('display/header_text');
        if (FastFrame::isEmpty($s_header)) {
            $s_header = false;
        }
        else {
            $s_header = str_replace('%title%', $this->getPageTitle(), $s_header);
        }

        return $s_header;
    }

    // }}}
    // {{{ _getFooterText()

    /**
     * Gets the footer text for the page
     *
     * @access private
     * @return mixed False if ther is no footer or footer text
     */
    function _getFooterText()
    {
        $s_footer = $this->o_registry->getConfigParam('display/footer_text');
        if (FastFrame::isEmpty($s_footer)) {
            $s_footer = false;
        }
        else {
            $s_footer = str_replace('%version%', $this->o_registry->getConfigParam('version/primary',
                        null, FASTFRAME_DEFAULT_APP), $s_footer);
            $a_microtime = explode(' ', microtime());
            define('FASTFRAME_END_TIME', $a_microtime[1] . substr($a_microtime[0], 1));
            $s_footer = str_replace('%renderTime%', number_format(FASTFRAME_END_TIME - FASTFRAME_START_TIME, 2), $s_footer);
        }

        return $s_footer;
    }

    // }}}
    // {{{ _isAbsolute()

    /**
     * Tells if a path is abolute.  Allows for ../ in the path.
     *
     * @param $in_path The path
     *
     * @access private
     * @return bool True if it is absolute.
     */
    function _isAbsolute($in_path)
    {
        if ((DIRECTORY_SEPARATOR == '/' && (substr($in_path, 0, 1) == '/' || substr($in_path, 0, 1) == '~')) ||
            (DIRECTORY_SEPARATOR == '\\' && preg_match('/^[a-z]:\\\/i', $in_path))) {
            return true;
        }
        else {
            return false;
        }
    }

    // }}}
}
?>
