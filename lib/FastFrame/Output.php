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
// {{{ requires 

require_once dirname(__FILE__) . '/Smarty.php';
require_once dirname(__FILE__) . '/FileCache.php';
require_once 'Net/UserAgent/Detect.php';

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
class FF_Output {
    // {{{ properties

    /**
     * The overall template instance
     * @var object
     */
    var $o_tpl;

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
        // Set up master template
        $this->o_tpl =& new FF_Smarty('overall');
        $this->setDefaults();
        // Include some common js
        $this->addScriptFile($this->o_registry->getRootFile('core.lib.js', 'javascript', FASTFRAME_WEBPATH));
        // Prevents E_ALL errors
        $this->o_tpl->assign(array('content_left' => array(), 'content_middle' => array(),
                    'content_right' => array(), 'status_messages' => array(), 'page_explanation' => array()));
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
        // The theme-specific style sheet
        $this->renderCSS($this->theme, $this->themeDir);
        $this->o_tpl->assign(array(
                    'COPYWRITE' => 'Copywrite &#169; 2002-2003 The CodeJanitor Group',
                    'CONTENT_ENCODING' => $this->o_registry->getConfigParam('general/charset', 'ISO-8559-1'),
                    'U_SHORTCUT_ICON' => $this->o_registry->getConfigParam('general/favicon'),
                    'PAGE_TITLE' => $this->getPageTitle()));
        $this->_renderPageType();
        $this->_renderMenus();
    }

    // }}}
    // {{{ renderCSS()

    /**
     * Creates a link to the css file (creating it in cache if
     * necessary), and then registers it in the template.
     *
     * @param string $in_theme The theme to make the CSS for
     * @param string $in_themeDir The theme directory 
     *
     * @access public
     * @return void 
     */
    function renderCSS($in_theme, $in_themeDir)
    {
        require_once dirname(__FILE__) . '/FileCache.php';
        $o_fileCache =& FF_FileCache::singleton();
        $s_cssTemplateFile = $in_themeDir . '/style.tpl';
        $s_browser = Net_UserAgent_Detect::getBrowser(array('ie', 'gecko'));
        // All other browsers get treated as gecko
        if (is_null($s_browser)) {
            $s_browser = 'gecko';
        }

        // Determine the css file based on the browser
        $s_cssFileName = 'style-' . $s_browser . '.css';
        $s_cssCacheFile = 'css/' . $in_theme . '/' . $s_cssFileName;

        // Make the CSS file if needed
        if (!$o_fileCache->exists($s_cssCacheFile) || 
            filemtime($s_cssTemplateFile) > filemtime($o_fileCache->getPath($s_cssCacheFile))) {
            $o_cssWidget =& new FF_Smarty($s_cssTemplateFile); 
            // We already know the template is out of date
            $o_cssWidget->force_compile = true;
            // Use delimiters that work in css files
            $o_cssWidget->left_delimiter = '{{';
            $o_cssWidget->right_delimiter = '}}';
            $o_cssWidget->assign(array('is_' . $s_browser => true,
                    'THEME_DIR' => $this->o_registry->rootPathToWebPath($in_themeDir),
                    'ROOT_GRAPHICS_DIR' => $this->o_registry->getRootFile('', 'graphics', FASTFRAME_WEBPATH)));
            $o_result =& $o_fileCache->save($o_cssWidget->fetch(), $s_cssCacheFile);
            if (!$o_result->isSuccess()) {
                foreach ($o_result->getMessages() as $s_message) {
                    trigger_error($s_message, E_USER_ERROR);
                }
            }

            $o_fileCache->makeViewableFromWeb($s_cssCacheFile);
        }

        $s_cssURL = $o_fileCache->getPath($s_cssCacheFile, false, FASTFRAME_WEBPATH);
        // register the CSS file 
        $this->o_tpl->append('css',
                // put the filemtime on so that they only grab the new file when it is recreated
                '<link rel="stylesheet" type="text/css" href="' . $s_cssURL . '?fresh=' . filemtime($s_cssTemplateFile) . '" />');
    }

    // }}}
    // {{{ link()

    /**
     * Creates a link tag
     *
     * @param  string $in_url The URL to be linked to
     * @param  string $in_text content for the link tag
     * @param  array  $in_options A number of options that have to do with the a tag.  The 
     *                            options are as follows:
     *                            status, title, class, target, onclick, style, confirm, 
     *                            getAccessKey (attempts to get an accesskey for the link)
     *
     * @access public
     * @return string Entire <a> tag plus attributes
     */
    function link($in_url, $in_text, $in_options = array())
    {
        $a_options = array('title' => '', 'caption' => '', 'status' => '',
                // these can be blank or event (onmouseover, onmousemove or onclick)
                'greasy' => '', 'sticky' => '',
                'confirm' => '', 'onclick' => '', 'class' => '',
                'style' => '', 'target' => '_self', 'getAccessKey' => false);

        $a_options = array_merge($a_options, $in_options);

        if (!empty($a_options['onclick'])) {
            $a_options['onclick'] = strtr(htmlspecialchars($a_options['onclick']), "\n\r", '  ');
        }

        if (!empty($a_options['confirm'])) {
            $s_confirm = strtr(htmlspecialchars(addcslashes($a_options['confirm'], '\'')), "\n\r", '  ');
            $a_options['onclick'] .= ' return window.confirm(\'' . $s_confirm . '\');';
        }

        $s_ak = '';
        if ($a_options['getAccessKey']) {
            $s_ak = $this->getAccessKey($in_text);
        }

        // We use the basic title tag when there is no caption.
        if (empty($a_options['caption'])) {
            $a_events = array('onclick' => $a_options['onclick'], 'onmouseover' => '', 'onmousemove' => '');
            // Make the text the title if no title was passed in
            if (empty($in_options['title']) && strpos($in_text, '<img') === false) {
                $a_options['title'] = $in_text;
                if (!empty($s_ak)) {
                    $a_options['title'] .= ' ' . sprintf(_('(Accesskey: %s)'), $s_ak);
                }
            }
        }
        else {
            $a_events = $this->_prepareTooltip(array('caption' => $a_options['caption'], 
                        'content' => $a_options['title'], 'status' => $a_options['status'],
                        'sticky' => $a_options['sticky'], 'greasy' => $a_options['greasy'],
                        'onclick' => $a_options['onclick']));
            // Title is replaced by a tooltip
            $a_options['title'] = '';
        }

        // build the tag
        $s_tag = '<a href="' . $in_url . '"';
        $s_tag .= !empty($a_events['onmouseover']) ? ' onmouseover="' . $a_events['onmouseover'] . '"' : '';
        $s_tag .= !empty($a_events['onmousemove']) ? ' onmousemove="' . $a_events['onmousemove'] . '"' : '';
        $s_tag .= !empty($a_events['onclick']) ? ' onclick="' . $a_events['onclick'] . '"' : '';
        $s_tag .= !empty($a_options['title']) ? ' title="' . $a_options['title'] . '"' : '';
        $s_tag .= !empty($a_options['class']) ? ' class="' . $a_options['class'] . '"' : '';
        $s_tag .= !empty($a_options['style']) ? ' style="' . $a_options['style'] . '"' : '';
        $s_tag .= !empty($a_options['target']) ? ' target="' . $a_options['target'] . '"' : '';
        $s_tag .= '>';
 
        return $s_tag . $this->highlightAccessKey($in_text, $s_ak) . '</a>';
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
        if ($this->o_registry->getConfigParam('user/use_help', false)) {
            $in_title = is_null($in_title) ? _('Help') : $in_title;
            return $this->imgTag('help.gif', 'actions', array('align' => 'top', 'title' => $in_text, 
                        'caption' => $in_title, 'status' => $in_title, 'sticky' => 'onclick', 
                        'greasy' => false, 'style' => 'cursor: pointer;'));
        }
        else {
            return '';
        }
    }

    // }}}
    // {{{ getAccessKey()

    /**
     * Returns an un-used access key from the label given.
     *
     * @param string $in_label The label to choose an access key from.
     *
     * @author Originally from Horde <http://horde.org>
     * @access public
     *
     * @return string  A single lower case character access key or empty
     *                 string if none can be found
     */
    function getAccessKey($in_label)
    {
        // The access keys already used in this page
        static $_used = array();
        // The labels already used
        static $_labels = array();
        $a_letters = array();
        $in_label = strip_tags($in_label);
        $in_label = preg_replace('/&[^;]+;/', '', $in_label);
        if (isset($_labels[$in_label])) {
            return $_labels[$in_label];
        }

        // Try the upper case characters first
        $iMax = strlen($in_label);
        for ($i = 0; $i < $iMax; $i++) {
            $c = substr($in_label, $i, 1);
            if ($c >= 'A' && $c <= 'Z') {
                $s_lower = strtolower($c);
                if (!isset($_used[$s_lower])) {
                    $_used[$s_lower] = 1;
                    $_labels[$in_label] = $c;
                    return $c;
                }
            } 
            else {
                $a_letters[] = $c;
            }
        }

        // Try the lower case characters next
        foreach ($a_letters as $c) {
            if (!isset($_used[$c]) && $c >= 'a' && $c <= 'z') {
                $_labels[$in_label] = $c;
                $_used[$c] = 1;
                return $c;
            }
        }

        // No key found
        return '';
    }

    // }}}
    // {{{ highlightAccessKey()

    /**
     * Highlight an access key in a label.
     *
     * @param string $in_label The label to to highlight the access key in.
     * @param string $in_accessKey (optional) The access key to
     *        highlight.  If not specified we get one.
     *
     * @author Originally from Horde <http://horde.org>
     * @access public
     * @return string  The HTML version of the label with the access key
     *                 highlighted.
     */
    function highlightAccessKey($in_label, $in_accessKey = null)
    {
        $s_html = '';
        if (is_null($in_accessKey)) {
            $in_accessKey = $this->getAccessKey($in_label);
        }
        
        if (empty($in_accessKey)) {
            return $in_label;
        }

        $intag = false;
        $s_len = strlen($in_label);
        for ($pos = 0; $pos < $s_len; $pos++) {
            $c = substr($in_label, $pos, 1);
            // Are we still inside a tag or entity? Skipping.
            if ($intag && $c != $intag) {
                continue;
            }
            $intag = false;
            // Are we at the beginning of a tag? Skipping.
            if ($c == '<') {
                $intag = '>';
                continue;
            }
            // Are we at the beginning of an entity? Skipping.
            if ($c == '&') {
                $intag = ';';
                continue;
            }
            // Access key found?
            if ($c == $in_accessKey) {
                break;
            }
        }
            
        // Access key not found.
        if ($pos == $s_len) {
           return $in_label;
        }

        if ($pos > 0) {
            $s_html .= substr($in_label, 0, $pos);
        }

        $s_html .= '<span class="accessKey">' . $c . '</span>';
        if ($pos < strlen($in_label) - 1) {
            $s_html .= substr($in_label, $pos + 1);
        }

        return $s_html;
    }

    // }}}
    // {{{ imgTag()

    /**
     * Returns an image tag (either the src or <img>) properly
     * formatted.
     *
     * @param string $in_img Name of image, if an absolute path is not
     *               given we prepend FastFrame path 
     * @param string $in_type (optional) The type of image.  If 'none'
     *               then we assume that the path includes the type in it.
     * @param array  $in_options (optional) A number of options that
     *               have to do with the image, included what type
     *               of image tag this is.  The options are as follows
     *               width, height, type, align, style, onclick, id,
     *               onlyUrl, fullPath, title, status, caption, greasy,
     *               sticky, name, app (if the image is in a specific
     *               application)
     *
     * @access public
     * @return string The image tag
     */
    function imgTag($in_img, $in_type = 'icons', $in_options = array()) 
    {
        $b_isFullPath = false;
        // If image is an absolute path, we assume it is the full webpath
        if (strpos($in_img, '/') === 0) {
            $s_imgWebPath = $in_img;
        }
        // See if it's a url, then we just use it as is
        elseif (strpos($in_img, 'http://') === 0) {
            $b_isFullPath = true;
            $s_imgWebPath = $in_img;
        }
        // Else it's a relative path and we have to search for the image
        else {
            $s_type = $in_type == 'none' || is_null($in_type) ? '/' : "$in_type/";
            $tmp_img = $s_type . $in_img;
            // See if an app was specified
            if (isset($in_options['app'])) {
                $s_imgWebPath = $this->o_registry->getAppFile($tmp_img, $in_options['app'], 'graphics', FASTFRAME_WEBPATH);
            }
            // See if it's in the theme directory
            elseif (file_exists($this->themeDir . '/graphics' . $tmp_img)) {
                $s_imgWebPath = $this->o_registry->rootPathToWebPath($this->themeDir) . '/graphics' . $tmp_img;
            }
            else {
                $s_imgWebPath = $this->o_registry->getRootFile($tmp_img, 'graphics', FASTFRAME_WEBPATH);
            }
        }

        // See if we need to add on hostname
        if (!empty($in_options['fullPath']) && strpos($s_imgWebPath, 'http://') !== 0) {
            $s_imgWebPath = ($this->o_registry->getConfigParam('server/use_ssl') ? 'https' : 'http') . 
                            '://' . $this->o_registry->getConfigParam('webserver/hostname') . 
                            '/' . $s_imgWebPath;
        }

        if (isset($in_options['onlyUrl']) && $in_options['onlyUrl']) {
            return $s_imgWebPath;
        }
        else {
            $a_events = $this->_prepareTooltip(array(
                    'caption' => isset($in_options['caption']) ? $in_options['caption'] : '',
                    'content' => isset($in_options['title']) ? $in_options['title'] : '',
                    'status'  => isset($in_options['status']) ? $in_options['status'] : '',
                    'sticky'  => isset($in_options['sticky']) ? $in_options['sticky'] : '',
                    'greasy'  => isset($in_options['greasy']) ? $in_options['greasy'] : '',
                    'onclick' => isset($in_options['onclick']) ? $in_options['onclick'] : ''));
            $s_tag = '<';
            $s_tag .= isset($in_options['type']) && $in_options['type'] == 'input' ? 
                'input type="image"' : 'img';
            $s_tag .= " src=\"$s_imgWebPath\"";
            $s_tag .= $a_events['onmouseover'] != '' ? ' onmouseover="' . $a_events['onmouseover'] . '"' : '';
            $s_tag .= $a_events['onmousemove'] != '' ? ' onmousemove="' . $a_events['onmousemove'] . '"' : '';
            $s_tag .= $a_events['onclick'] != '' ? ' onclick="' . $a_events['onclick'] . '"' : '';
            $s_tag .= isset($in_options['id']) ? " id=\"{$in_options['id']}\"" : '';
            $s_tag .= isset($in_options['name']) ? " name=\"{$in_options['name']}\"" : '';
            $s_tag .= isset($in_options['hspace']) ? " hspace=\"{$in_options['hspace']}\"" : '';
            $s_tag .= isset($in_options['vspace']) ? " vspace=\"{$in_options['vspace']}\"" : '';
            $s_tag .= isset($in_options['width']) ? " width=\"{$in_options['width']}\"" : '';
            $s_tag .= isset($in_options['height']) ? " height=\"{$in_options['height']}\"" : '';
            $s_tag .= isset($in_options['style']) ? " style=\"{$in_options['style']}\"" : '';
            $s_tag .= isset($in_options['align']) ? " align=\"{$in_options['align']}\"" : ' align="middle"';
            $s_tag .= isset($in_options['border']) ? " border=\"{$in_options['border']}\"" : ' border="0"';
            $s_tag .= ' />';

            return $s_tag;
        }
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
                    '<script language="JavaScript" type="text/javascript" src="' . $in_file . '"></script>');
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
    // {{{ setDefaults()

    /**
     * Determines the default theme and menu.
     *
     * @access public
     * @return void
     */
    function setDefaults()
    {
        // If it's printer friendly we have no menu
        if (FF_Request::getParam('printerFriendly', 'gp', false)) {
            $this->setMenuType('none');
        }
        // Initialize menu to default type
        else {
            $this->setMenuType($this->o_registry->getConfigParam('display/menu_type'));
        }

        // Set popup type 
        if (FF_Request::getParam('isPopup', 'gp', false)) {
            $this->setPageType('popup');
        }

        // Set the theme
        if (FF_Request::getParam('printerFriendly', 'gp', false)) {
            $this->setTheme($this->o_registry->getConfigParam('display/print_theme'));
        }
        else {
            $s_theme = FF_Auth::getCredential('theme');
            $s_theme = empty($s_theme) ? $this->o_registry->getConfigParam('display/default_theme') : $s_theme;
            $this->setTheme($s_theme);
        }
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
    // {{{ setTheme()

    /**
     * Sets the theme, including it's directory.
     *
     * @param string $in_theme The theme
     *
     * @access public
     * @return void
     */
    function setTheme($in_theme)
    {
        // See if theme is a full path (if theme is in app directory it can be)
        if ($this->_isAbsolute($in_theme)) {
            $this->themeDir = $in_theme;
            $this->theme = basename($in_theme);
        }
        else {
            $this->theme = $in_theme;
            $this->themeDir = $this->o_registry->getRootFile($this->theme, 'themes');
        }
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
                        'I_status_message' => $this->imgTag($a_message[1], 'alerts'),
                        'I_status_message_close' => $this->imgTag('close.gif', 'actions', array('onclick' => 'document.getElementById(\'message_' . $s_key . '\').style.display = \'none\';', 'style' => 'cursor: pointer;'))));
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
            // Non-javascript enabled browsers must use StaticList
            if (!Net_UserAgent_Detect::hasFeature('javascript')) {
                $this->setMenuType('StaticList');
            }

            require_once dirname(__FILE__) . '/Menu.php';
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
                        null, FASTFRAME_DEFAULT_APP), $s_footer);
            $a_microtime = explode(' ', microtime());
            define('FASTFRAME_END_TIME', $a_microtime[1] . substr($a_microtime[0], 1));
            $s_footer = str_replace('%renderTime%', number_format(FASTFRAME_END_TIME - FASTFRAME_START_TIME, 2), $s_footer);
        }

        return $s_footer;
    }

    // }}}
    // {{{ _prepareTooltip()

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
    function _prepareTooltip($in_options = array())
    {
        $a_events = array(
            'onmouseover' => '',
            'onmousemove' => '',
            'onclick'     => '',
        );

        if ($in_options['content'] == '') {
            if (!empty($in_options['onclick'])) {
                $a_events['onclick'] = $in_options['onclick'];
            }

            return $a_events;
        }

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

        // Require the needed javascript since a tooltip will be used
        $this->addScriptFile($this->o_registry->getRootFile('domLib_strip.js', 'javascript', FASTFRAME_WEBPATH));
        $this->addScriptFile($this->o_registry->getRootFile('domTT_strip.js', 'javascript', FASTFRAME_WEBPATH));
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
