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
// {{{ requires 

require_once dirname(__FILE__) . '/Template.php';
require_once 'Net/UserAgent/Detect.php';
require_once 'File.php';

// }}}
// {{{ constants

/**
 * Message constants.  These images need to be in the graphics/alerts directory.
 * The theme can mimic the grapics/alerts directory to override the default image.
 */
define('FASTFRAME_NORMAL_MESSAGE', 'message.gif', true);
define('FASTFRAME_ERROR_MESSAGE', 'error.gif', true);
define('FASTFRAME_WARNING_MESSAGE', 'warning.gif', true);
define('FASTFRAME_SUCCESS_MESSAGE', 'success.gif', true);

// }}}
// {{{ class FF_Output

/**
 * The FF_Output:: class provides access to functions 
 * which produce Output code used to make common parts of a FastFrame page
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lesser.html.
 *
 * @author  The Horde Team <http://www.horde.org/>
 * @author  Jason Rust <jrust@codejanitor.com>
 * @author  Dan Allen <dan@mojavelinux.com>
 * @version Revision: 2.0 
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_Output extends FF_Template {
    // {{{ properties

    /**
     * o_registry instance
     * @var object
     */
    var $o_registry;

    /**
     * The page name used to make the title more descriptive
     * @var string
     */
    var $pageName;

    /**
     * The menu type used. 
     * @var string
     */
    var $menuType;

    /**
     * The page type.  Allows for easy creation of different types of pages
     * @var string
     */
    var $pageType = 'normal';

    /**
     * The theme setting
     * @var string
     */
    var $theme;

    /**
     * The theme directory
     * @var string
     */
    var $themeDir;

    /**
     * The array of messages
     * @var array
     */
    var $messages = array();

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function FF_Output()
    {
        $this->o_registry =& FF_Registry::singleton();
    }

    // }}}
    // {{{ singleton()

    /**
     * To be used in place of the contructor to return any open instance.
     *
     * This function is used in place of the contructor to return any open instances
     * of the html object, and if none are open will create a new instance and cache
     * it using a static variable
     *
     * @access public
     * @return object FF_Output instance
     */
    function &singleton()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new FF_Output();
        }
        return $instance;
    }

    // }}}
    // {{{ load()

    /**
     * Loads in the template file and registers default blocks
     *
     * @param string $in_theme The theme to use
     *
     * @access public
     * @return void
     */
    function load($in_theme)
    {
        $this->theme = FF_Request::getParam('printerFriendly', 'gp', false) && 
            !$this->o_registry->getConfigParam('display/theme_locked', false) ?
            $this->o_registry->getConfigParam('display/print_theme') : $in_theme;

        // See if theme is a full path (if theme is in app directory it can be)
        if ($this->_is_absolute($this->theme)) {
            $this->themeDir = $this->theme;
            $this->theme = basename($this->theme);
        }
        else {
            $this->themeDir = $this->o_registry->getRootFile($this->theme, 'themes');
        }

        // See if theme has its own main template
        $pth_overall = $this->themeDir;
        if (!is_readable($pth_overall . '/overall.tpl')) {
            $pth_overall = $this->o_registry->getRootFile('widgets', 'themes');
        }

        // now that we have the template directory, we can initialize the template engine
        parent::FF_Template($pth_overall);
        parent::load('overall.tpl', 'file');

        // Initialize menu to default type
        $this->setMenuType($this->o_registry->getConfigParam('display/menu_type'));

        // If it's printer friendly we have no menu
        if (FF_Request::getParam('printerFriendly', 'gp', false)) {
            $this->setMenuType('none');
        }

        // Set popup type 
        if (FF_Request::getParam('isPopup', 'gp', false)) {
            $this->setPageType('popup');
        }
        
        // include some common js, needs to be at top before any of their functions are used
        $this->assignBlockData(
            array(
                'T_javascript' => '<script language="Javascript" src="' . $this->o_registry->getRootFile('domLib_strip.js', 'javascript', FASTFRAME_WEBPATH) . '"></script>' . "\n" .
                                  '<script language="Javascript" src="' . $this->o_registry->getRootFile('domTT_strip.js', 'javascript', FASTFRAME_WEBPATH) . '"></script>' . "\n" .
                                  '<script language="Javascript" src="' . $this->o_registry->getRootFile('core.lib.js', 'javascript', FASTFRAME_WEBPATH) . '"></script>',
            ),
            'javascript'
        );
    }

    // }}}
    // {{{ output()

    /**
     * Outputs the template data to the browser.
     *
     * @access public
     * @return void
     */
    function output()
    {
        $this->_renderMessages();
        $this->renderCSS();
        $this->assignBlockData(
            array(
                'COPYWRITE' => 'Copywrite &#169; 2002-2003 The CodeJanitor Group',
                'CONTENT_ENCODING' => $this->o_registry->getConfigParam('general/charset', 'ISO-8559-1'),
                'U_SHORTCUT_ICON' => $this->o_registry->getConfigParam('general/favicon'),
                'CANVAS_WIDTH' => '100%',
                'PAGE_TITLE' => $this->getPageTitle(),
            ),
            $this->getGlobalBlockName()
        );

        $this->_renderMenus();
        $this->_renderPageType();
        return $this->render();
    }

    // }}}
    // {{{ getWidgetObject()

    /**
     * Gets a widget object.  First looks to see if the theme has the widget, if not it
     * loads the widget from the default widgets directory.
     *
     * @param string $in_widget The widget name or the full path to the widget file.
     *
     * @access public
     * @return object The FF_Template object for the specified widget
     */
    function &getWidgetObject($in_widget)
    {
        // See if it's a full path or just a widget name
        if (substr($in_widget, -4) == '.tpl') {
            $s_directory = dirname($in_widget); 
        }
        else {
            $in_widget .= '.tpl';
            $s_directory = $this->themeDir;
            if (!is_readable($s_directory . '/' . $in_widget)) {
                $s_directory = $this->o_registry->getRootFile('widgets', 'themes');
            }
        }

        $o_widget =& new FF_Template($s_directory);
        $o_widget->load($in_widget, 'file');
        return $o_widget;
    }

    // }}}
    // {{{ renderCSS()

    /**
     * Creates a link to the css file (creating it in cache if necessary), and then
     * registers it as the main CSS in the template.
     *
     * @param bool $in_remakeCSS (optional) Remake the CSS file even if it exists in cache?
     *
     * @access public
     * @return void 
     */
    function renderCSS($in_remakeCSS = false)
    {
        $s_cssCacheDir = $this->o_registry->getRootFile('css/' . $this->theme, 'cache');
        if (!is_dir($s_cssCacheDir)) {
            require_once 'System.php';
            if (!@System::mkdir("-p $s_cssCacheDir"))  {
                return PEAR::raiseError(null, FASTFRAME_NO_PERMISSIONS, null, E_USER_WARNING, $s_cssCacheDir, 'FF_Error', true);
            }
        }

        $s_cssTemplateFile = $this->themeDir . '/style.tpl';
        $s_browser = Net_UserAgent_Detect::getBrowser(array('ie', 'gecko'));
        // All other browsers get treated as gecko
        if (is_null($s_browser)) {
            $s_browser = 'gecko';
        }

        // Determine the css file based on the browser
        $s_cssFileName = 'style-' . $s_browser . '.css';
        $s_cssCacheFile = $s_cssCacheDir . '/' . $s_cssFileName;

        // make the CSS file if needed
        if ($in_remakeCSS ||
            !file_exists($s_cssCacheFile) || 
            filemtime($s_cssTemplateFile) > filemtime($s_cssCacheFile)) {
            $o_cssWidget =& $this->getWidgetObject($s_cssTemplateFile); 
            // touch the browser specific block
            $o_cssWidget->touchBlock('switch_is_' . $s_browser);
            // set some variables
            $o_cssWidget->assignBlockData(
                array(
                    'THEME_DIR' => $this->o_registry->rootPathToWebPath($this->themeDir),
                    'ROOT_GRAPHICS_DIR' => $this->o_registry->getRootFile('', 'graphics', FASTFRAME_WEBPATH),
                ),
                $this->getGlobalBlockName()
            );

            File::write($s_cssCacheFile, $o_cssWidget->render(), FILE_MODE_WRITE);
            File::close($s_cssCacheFile, FILE_MODE_WRITE);
        }

        $s_cssURL = $this->o_registry->getRootFile('css/' . $this->theme . '/' . $s_cssFileName, 
                'cache', FASTFRAME_WEBPATH);
        // register the CSS file 
        $this->assignBlockData(
            array(
                // put the filemtime on so that they only grab the new file when it is recreated
                'MAIN_CSS' => '<link rel="stylesheet" type="text/css" href="' . $s_cssURL . '?fresh=' . filemtime($s_cssCacheFile) . '" />',
            ),
            $this->getGlobalBlockName()
        );
    }

    // }}}
    // {{{ link()

    /**
     * Creates an entire link tag using linkStart() and linkEnd()
     *
     * @param  string $in_url The full URL to be linked to
     * @param  string $in_text content for the link tag
     * @param  array  $in_options A number of options that have to do with the a tag.  The 
     *                            options are as follows:
     *                            status, title, class, target, onclick, style, confirm
     *
     * @access public
     * @return string Entire <a> tag plus attributes
     *
     * @see linkStart(), linkEnd()
     */
    function link($in_url, $in_text, $in_options = array())
    {
        // Title should be the text if it's not set and isn't an image tag
        if (!isset($in_options['title']) && strpos($in_text, '<img') === false) {
            $in_options['title'] = $in_text;
        }

        return FF_Output::linkStart($in_url, $in_options) . $in_text . FF_Output::linkEnd();
    }

    // }}}
    // {{{ linkStart()

    /**
     * Return an anchor tag with the relevant parameters 
     *
     * @param  string $in_url The full URL to be linked to
     * @param  array  $in_options A number of options that have to do with the a tag.  The 
     *                            options are as follows:
     *                            status, title, class, target, onclick, style, confirm
     *
     * @return string The start <a> tag plus attributes
     */
    function linkStart($in_url, $in_options = array())
    {
        // cast $in_options to an array
        settype($in_options, 'array');

        $a_defaults = array(
            'title'       => '',
            'caption'     => '',
            'status'      => '',
            'greasy'      => '', // can be blank or event (onmouseover, onmousemove or onclick)
            'sticky'      => '', // can be blank or event (onclick, onmouseover, onmousemove)
            'confirm'     => '',
            'onclick'     => '',
            'class'       => '',
            'style'       => '',
            'target'      => '_self',
        );

        $a_options = array_merge($a_defaults, $in_options);

        // make sure you manually add cslashes where necessary for your onclick
        if (!empty($a_options['onclick'])) {
            $a_options['onclick'] = strtr(htmlspecialchars($a_options['onclick']), "\n\r", '  ');
        }

        if (!empty($a_options['confirm'])) {
            $s_confirm = strtr(htmlspecialchars(addcslashes($a_options['confirm'], '\'')), "\n\r", '  ');
            $a_options['onclick'] .= ' return window.confirm(\'' . $s_confirm . '\');';
        }

        $a_events = $this->_prepare_tooltip(
            array(
                'caption'      => $a_options['caption'],
                'content'      => $a_options['title'],
                'status'       => $a_options['status'],
                'sticky'       => $a_options['sticky'],
                'greasy'       => $a_options['greasy'],
                'onclick'      => $a_options['onclick'],
            )
        );

        // build the tag
        $s_tag = '<a ';
        // in some cases we don't want href, because IE evaluates that before the onclick
        $s_tag .= !empty($in_url) ? 'href="' . $in_url . '"' : '';
        $s_tag .= $a_events['onmouseover'] != '' ? ' onmouseover="' . $a_events['onmouseover'] . '"' : '';
        $s_tag .= $a_events['onmousemove'] != '' ? ' onmousemove="' . $a_events['onmousemove'] . '"' : '';
        $s_tag .= $a_events['onclick'] != '' ? ' onclick="' . $a_events['onclick'] . '"' : '';
        $s_tag .= !empty($a_options['class']) ? ' class="' . $a_options['class'] . '"' : '';
        $s_tag .= !empty($a_options['style']) ? ' style="' . $a_options['style'] . '"' : '';
        $s_tag .= !empty($a_options['target']) ? ' target="' . $a_options['target'] . '"' : '';

        $s_tag .= '>';
        return $s_tag;
    }

    // }}}
    // {{{ linkEnd()

    /**
    * Returns an end link
    * @return   string  The Output to end a link 
    */
    function linkEnd()
    {
        return '</a>';
    }
    
    // }}}
    // {{{ popupLink()

    /**
     * Create a popup link
     *
     * @param string $in_name The identifier of the window (i.e. mycode)
     * @param string $in_text The text for the link
     * @param array $in_urlParams URL parameters to pass to the selfURL() method
     * @param array $in_options (optional) Additional options.  Availabe options are
                    menubar, status, width, height, class, title
     *
     * @access public
     * @return string The link to the popup page
     */
    function popupLink($in_name, $in_text, $in_urlParams, $in_options = array())
    {
        $in_options['menubar'] = (isset($in_options['menubar']) && $in_options['menubar']) ? 'yes' : 'no';
        $in_options['class'] = isset($in_options['class']) ? $in_options['class'] : '';
        $in_options['title'] = isset($in_options['title']) ? $in_options['title'] : $in_text;
        $in_options['status'] = isset($in_options['status']) ? $in_options['status'] : '';
        $in_options['width'] = isset($in_options['width']) ? $in_options['width'] : 650;
        $in_options['height'] = isset($in_options['height']) ? $in_options['height'] : 500;
 
        $s_url = FastFrame::selfURL($in_urlParams);
        $s_js = $in_name . ' = window.open(\'' . $s_url .  '\',' . 
                '\'' . $in_name . '\',' .
                '\'toolbar=no,location=no,status=yes,menubar=' . $in_options['menubar'] . 
                ',scrollbars=yes,resizable,alwaysRaised,dependent,titlebar=no' . 
                ',width=' . $in_options['width'] . ',height=' . $in_options['height'] . 
                ',left=50,screenX=50,top=50,screenY=50\'); ' .
                $in_name . '.focus(); return false;';

        $link = $this->link($s_url, $in_text, 
                array('onclick' => $s_js, 'title' => $in_options['title'], 'class' => $in_options['class']));
        $link .= '<script>var ' . $in_name . ' = null;</script>';
        return $link;
    }

    // }}}
    // {{{ getHelpLink()

    /**
     * Returns a help tool tip with the help icon.
     *
     * @param string $in_text The help text.
     * @param string $in_title (optional) The title.
     *
     * @access public
     * @return string A link with a tooltip wrapped around the help icon.
     */
    function getHelpLink($in_text, $in_title = null)
    {
        $in_title = is_null($in_title) ? _('Help') : $in_title;
        return $this->imgTag('help.gif', 'actions', array('align' => 'top', 'title' => $in_text, 
                    'caption' => $in_title, 'status' => $in_title, 'sticky' => 'onclick', 
                    'greasy' => false, 'style' => 'cursor: pointer;'));
    }

    // }}}
    // {{{ imgTag()

    /**
     * Returns an image tag (either the src or <img>) properly formatted and 
     * with the correct width and height attributes 
     *
     * @param string $in_img Name of image, if an absolute path is not
     *               given we prepend FastFrame path 
     * @param string $in_type (optional) The type of image.  If 'none'
     *               then we assume that the path includes the type in it.
     * @param array  $in_options (optional) A number of options that
     *               have to do with the image tag, included what type
     *               of image tag this is.  The options are as follows
     *               width, height, type, align, style, onclick, id,
     *               onlyUrl, fullPath, title, status, caption, greasy,
     *               sticky, name
     *
     * @access public
     * @return string The image tag
     */
    function imgTag($in_img = null, $in_type = 'icons', $in_options = array()) 
    {
        static $s_missingImage, $a_themePaths, $a_appPaths;
        settype($in_options, 'array');

        // cache the missing image from the registry
        if (!isset($s_missingImage)) {
            $s_missingImage = $this->o_registry->getConfigParam('image/missing');
        }

        if (!isset($a_themePaths)) {
            $a_themePaths = array();
            $tmp_theme = $this->theme;
            $a_themePaths['web'] = $this->o_registry->getRootFile($tmp_theme . '/graphics', 'themes', FASTFRAME_WEBPATH);
            $a_themePaths['file'] = $this->o_registry->getRootFile($tmp_theme . '/graphics', 'themes');
        }

        if (!isset($a_appPaths)) {
            $a_appPaths = array();
            $a_appPaths['web'] = $this->o_registry->getAppFile('', null, 'graphics', FASTFRAME_WEBPATH);
            $a_appPaths['file'] = $this->o_registry->getAppFile('', null, 'graphics');
        }

        if (empty($in_img) || is_null($in_img)) {
            $in_img = $s_missingImage;
        }

        $s_type = $in_type == 'none' ? '/' : "/$in_type/";
        $b_isFullPath = false;

        // if image is an absolute path, we assume it is a webpath and check if it exists
        if (strpos($in_img, '/') === 0) {
            if (!file_exists($s_imgFilePath = $this->o_registry->getConfigParam('webserver/file_root') . $in_img)) {
                $s_imgWebPath = $this->o_registry->getRootFile($s_missingImage, 'graphics', FASTFRAME_WEBPATH);
                $s_imgFilePath = $this->o_registry->getRootFile($s_missingImage, 'graphics');
            }
            else {
                $s_imgWebPath = $in_img;
            }
        }
        // see if it's a relative path to the image
        elseif (!preg_match(';^(http://|ftp://);', $in_img)) {
            $tmp_img = $s_type . $in_img;
            // see if it's in the theme directory
            if (file_exists(($s_imgFilePath = $a_themePaths['file'] . $tmp_img))) {
                $s_imgWebPath = $a_themePaths['web'] . $tmp_img;
            }
            // see if it's in the app directory
            elseif (file_exists(($s_imgFilePath = $a_appPaths['file'] . $tmp_img))) {
                $s_imgWebPath = $a_appPaths['web'] . $tmp_img;
            }
            // check in root graphics dir
            elseif (file_exists($s_imgFilePath = $this->o_registry->getRootFile($tmp_img, 'graphics'))) {
                $s_imgWebPath = $this->o_registry->getRootFile($tmp_img, 'graphics', FASTFRAME_WEBPATH);
            }
            // image not found
            else {
                $s_imgWebPath = $this->o_registry->getRootFile($s_missingImage, 'graphics', FASTFRAME_WEBPATH);
                $s_imgFilePath = $this->o_registry->getRootFile($s_missingImage, 'graphics');
            }
        }
        // else it's a url, then we just use it as is, no check which is costly
        else {
            $b_isFullPath = true;
            $s_imgWebPath = $in_img;
        }

        // Get size [!] (perhaps we can allow scaling here, not done here yet) [!]
        if (!$b_isFullPath && (!isset($in_options['width']) || !isset($in_options['height']))) {
            list($width, $height) = getImageSize($s_imgFilePath);
            $in_options['width']  = isset($in_options['width']) ? $in_options['width'] : $width;
            $in_options['height'] = isset($in_options['height']) ? $in_options['height'] : $height;
        }

        if (!isset($in_options['align'])) {
            $in_options['align'] = 'middle';
        }

        if (isset($in_options['fullPath']) && 
            $in_options['fullPath'] &&
            !preg_match(';^(http://|ftp://);', $s_imgWebPath)) {
            $s_imgWebPath = ($this->o_registry->getConfigParam('server/use_ssl') ? 'https' : 'http') . 
                            '://' . $this->o_registry->getConfigParam('webserver/hostname') . $s_imgWebPath;
        }

        $in_options['type'] = isset($in_options['type']) && $in_options['type'] == 'input' ? 'input type="image"' : 'img';

        if (isset($in_options['onlyUrl']) && $in_options['onlyUrl']) {
            return $s_imgWebPath;
        }
        else {
            $a_events = $this->_prepare_tooltip(array(
                    'caption'      => isset($in_options['caption']) ? $in_options['caption'] : '',
                    'content'      => isset($in_options['title']) ? $in_options['title'] : '',
                    'status'       => isset($in_options['status']) ? $in_options['status'] : '',
                    'sticky'       => isset($in_options['sticky']) ? $in_options['sticky'] : '',
                    'greasy'       => isset($in_options['greasy']) ? $in_options['greasy'] : '',
                    'onclick'       => isset($in_options['onclick']) ? $in_options['onclick'] : '',
                )
            );
            $s_tag = "<{$in_options['type']}";
            $s_tag .= " src=\"$s_imgWebPath\"";
            $s_tag .= $a_events['onmouseover'] != '' ? ' onmouseover="' . $a_events['onmouseover'] . '"' : '';
            $s_tag .= $a_events['onmousemove'] != '' ? ' onmousemove="' . $a_events['onmousemove'] . '"' : '';
            $s_tag .= $a_events['onclick'] != '' ? ' onclick="' . $a_events['onclick'] . '"' : '';
            $s_tag .= isset($in_options['id']) ? " id=\"{$in_options['id']}\"" : '';
            $s_tag .= isset($in_options['name']) ? " name=\"{$in_options['name']}\"" : '';
            $s_tag .= isset($in_options['align']) ? " align=\"{$in_options['align']}\"" : '';
            $s_tag .= isset($in_options['hspace']) ? " hspace=\"{$in_options['hspace']}\"" : '';
            $s_tag .= isset($in_options['vspace']) ? " vspace=\"{$in_options['vspace']}\"" : '';
            $s_tag .= isset($in_options['width']) ? " width=\"{$in_options['width']}\"" : '';
            $s_tag .= isset($in_options['height']) ? " height=\"{$in_options['height']}\"" : '';
            $s_tag .= isset($in_options['style']) ? " style=\"{$in_options['style']}\"" : '';
            $s_tag .= isset($in_options['onclick']) ? " onclick=\"{$in_options['onclick']}\"" : '';
            $s_tag .= isset($in_options['border']) ? " border=\"{$in_options['border']}\"" : 'border="0"';
            $s_tag .= ' />';

            return $s_tag;
        }
    }

    // }}}
    // {{{ processCellData()

    /**
     * Takes cell data, and if it is empty returns &nbsp;, otherwise just returns the cell
     * data.  Fixes bug where some browsers (such as IE) will not render borders in cells
     * with no data.
     *
     * @param string $in_data The cell data
     *
     * @access public
     * @return string Either &nbsp; if it is empty, otherwise the data
     */
    function processCellData($in_data)
    {
        return ($in_data == '' || $in_data === false) ? '&nbsp;' : $in_data;
    }

    // }}}
    // {{{ setPageType()

    /**
     * Sets the page type which changes the look of the change by touching certain blocks,
     * changing styles, etc.
     *
     * @param string $in_type The page type.  Currently: normal, popup, smallTable
     *
     * @access public
     * @return void
     */
    function setPageType($in_type)
    {
        $this->pageType = $in_type;
    }

    // }}}
    // {{{ setMessage()

    /**
     * Set the message and the mode associated with it
     *
     * @param mixed $in_message The message to inform the user what has just taken place.
     *                          If it is an array then we will register all of them.
     * @param string $in_mode (optional) The mode, which is then translated into an image
     * @param bool $in_top (optional) Put the message at the top of the stack instead of
     *             bottom?
     *
     * @access public
     * @return void
     */
    function setMessage($in_message, $in_mode = FASTFRAME_NORMAL_MESSAGE, $in_top = false)
    {
        foreach ((array) $in_message as $s_message) {
            if (empty($s_message)) {
                continue;
            }

            $a_message = array($s_message, $in_mode);
            if ($in_top) {
                array_unshift($this->messages, $a_message);
            }
            else {
                $this->messages[] = $a_message;
            }
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
            $s_title = "$s_title :: $s_page";
        }

        if (!FastFrame::isEmpty(($s_siteName = $this->o_registry->getConfigParam('display/site_name')))) {
            $s_title = "$s_siteName :: $s_title";
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
    // {{{ setMenuType()

    /**
     * Sets the menu type, which can be any of the valid extensions of the Menu class
     *
     * @param string $in_type The menu type
     *
     * @access public
     * @return void 
     */
    function setMenuType($in_type)
    {
        $this->menuType = $in_type;
    }

    // }}}
    // {{{ getTheme()

    /**
     * Gets currently loaded theme.
     *
     * @access public
     * @return string The current theme.
     */
    function getTheme()
    {
        return $this->theme;
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
            $this->assignBlockData(
                array(
                    'S_status_message_count' => $s_key,
                    'T_status_message' => $a_message[0],
                    'I_status_message' => $this->imgTag($a_message[1], 'alerts'),
                    'I_status_message_close' => $this->imgTag('close.gif', 'actions', array('onclick' => 'document.getElementById(\'message_' . $s_key . '\').style.display = \'none\';', 'style' => 'cursor: pointer;')),
                ),
                'status_message'
            );
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
                $this->setMenuType('none');
            break;
            case 'smallTable':
                // override canvas width
                $this->assignBlockData(array('CANVAS_WIDTH' => '450px'), $this->getGlobalBlockName());

                // move it down slightly
                $this->assignBlockData(array('T_css' => '<style type="text/css">table.canvas { margin-top: 100px; } </style>'), 'css');

                if ($s_header = $this->_getHeaderText()) {
                    $this->assignBlockData(array('T_banner_top' => $s_header), 'switch_banner_top');
                }

                if ($s_footer = $this->_getFooterText()) {
                    $this->assignBlockData(array('T_banner_bottom' => $s_footer), 'switch_banner_bottom');
                }
            break;
            case 'normal':
                if ($s_header = $this->_getHeaderText()) {
                    $this->assignBlockData(array('T_banner_top' => $s_header), 'switch_banner_top');
                }

                if ($s_footer = $this->_getFooterText()) {
                    $this->assignBlockData(array('T_banner_bottom' => $s_footer), 'switch_banner_bottom');
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
            // Non-javascript enabled browsers must use StaticList
            if (!Net_UserAgent_Detect::hasFeature('javascript')) {
                $this->setMenuType('StaticList');
            }

            require_once dirname(__FILE__) . '/Menu.php';
            // Render the main menu
            $o_menu =& FF_Menu::factory($this->menuType);
            if (FF_Error::isError($o_menu)) {
                FastFrame::fatal($o_menu, __FILE__, __LINE__); 
            }
            
            $o_menu->renderMenu();

            // Render the QuickLinks menu
            $o_menu =& FF_Menu::factory('QuickLinks');
            if (FF_Error::isError($o_menu)) {
                FastFrame::fatal($o_menu, __FILE__, __LINE__); 
            }
            
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
        if (strpos($s_footer, '%userInfo%') !== false) {
            if (FF_Auth::checkAuth()) {
                $s_footer = str_replace('%userInfo%', sprintf(_('You are logged in as %s.'), 
                            FF_Auth::getCredential('username')), $s_footer);
            }
            else {
                $s_footer = str_replace('%userInfo%', _('You are not logged in.'), $s_footer);
            }
        }

        if (FastFrame::isEmpty($s_footer)) {
            $s_footer = false;
        }
        else {
            $s_footer = str_replace('%version%', $this->o_registry->getConfigParam('version/primary', 
                        null, array('app' => FASTFRAME_DEFAULT_APP)), $s_footer);
            $s_footer = str_replace('%build%', $this->o_registry->getConfigParam('version/build', 
                        null, array('app' => FASTFRAME_DEFAULT_APP)), $s_footer);
            $a_microtime = explode(' ', microtime());
            define('FASTFRAME_END_TIME', $a_microtime[1] . substr($a_microtime[0], 1));
            $s_footer = str_replace('%renderTime%', number_format(FASTFRAME_END_TIME - FASTFRAME_START_TIME, 2), $s_footer);
        }

        return $s_footer;
    }

    // }}}
    // {{{ _prepare_tooltip()

    /**
     * Prepares the tooltip caption, content, and the window status for the dom tooltip
     *
     * @param array $in_options caption, content, status, greasy, sticky, onclick
     * greasy -> 'mousemove'
     * sticky -> 'click'
     * 
     * @access private
     * @return string events
     */
    function _prepare_tooltip($in_options = array())
    {
        settype($in_options, 'array');
        settype($in_options['content'], 'string');
        $a_events = array(
            'onmouseover' => '',
            'onmousemove' => '',
            'onclick'     => '',
        );

        if ($in_options['content'] == '' || Net_UserAgent_Detect::isBrowser('ns4')) {
            if (!empty($in_options['onclick'])) {
                $a_events['onclick'] = $in_options['onclick'];
            }

            return $a_events;
        }

        settype($in_options['caption'], 'string');
        settype($in_options['status'], 'string');
        $a_events['onmouseover'] = 'greasy';
        
        $s_content = strtr(htmlentities(addcslashes($in_options['content'], '\'')), "\n\r", '  ');

        $s_caption = $in_options['caption'] != '' ?
            strtr(addcslashes(strip_tags($in_options['caption']), '\''), "\n\r", '  ') :
            false;
        
        $s_status = $in_options['status'] != '' ?
            strtr(addcslashes(strip_tags($in_options['status']), '\''), "\n\r", '  ') :
            strip_tags($s_content);

        // The way this will work, we default to use onmouseover as greasy...unless the user
        // sticky option is onmouseover, onmouseover will be used to ensure the status bar is
        // written so as to hide the link url
        if (isset($in_options['greasy'])) {
            // If greasy was set to false then turn it off for onmouseover
            if ($in_options['greasy'] === false) {
                $a_events['onmouseover'] = '';
            }
            elseif (!empty($in_options['greasy'])) {
                $a_events[$in_options['greasy']] = 'greasy';
            }
        }

        if (!empty($in_options['sticky'])) {
            $a_events[$in_options['sticky']] = 'sticky';
        }

        foreach ($a_events as $s_event => $s_type) {
            if (!$s_type) {
                continue;
            }

            $a_events[$s_event] = ($s_event == 'onmouseover' ? 'return makeTrue(' : '') . 
                                  'domTT_activate(this, event, \'caption\', ' . 
                                  ($s_caption === false ? 'false' : "'$s_caption'") . 
                                  ", 'content', '$s_content', 'status', '$s_status', 'type', '" . 
                                  ($s_type == 'sticky' ? 'sticky' : 'greasy') . 
                                  '\'' .
                                  ($s_event == 'onmouseover' ? ')' : '') . 
                                  ');';
        }
        
        if (!empty($in_options['onclick'])) {
            $a_events['onclick'] .= ' ' . $in_options['onclick'];
        }

        return $a_events;
    }

    // }}}
}
?>
