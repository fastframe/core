<?php
/** $Id: Output.php,v 1.3 2003/02/08 00:10:54 jrust Exp $ */
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

// }}}
// {{{ class FastFrame_Output

/**
 * The FastFrame_Output:: class provides access to functions 
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
class FastFrame_Output extends FastFrame_Template {
    // {{{ properties

    /**
     * o_registry instance
     * @type object
     */
    var $o_registry;

    /**
     * The page name used to make the title more descriptive
     * @type string
     */
    var $pageName;

    /**
     * The menu type used. 
     * @type string
     */
    var $menuType;

    /**
     * The page type.  Allows for easy creation of different types of pages
     * @type string
     */
    var $pageType = 'normal';

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function FastFrame_Output()
    {
        $this->o_registry =& FastFrame_Registry::singleton();

        $s_directory = $this->o_registry->getRootFile($this->o_registry->getUserParam('theme'), 'themes');
        // make sure the main template exists
        if (PEAR::isError($s_directory) || !is_readable($s_directory . '/overall.tpl')) {
            $tmp_error = PEAR::raiseError(null, FASTFRAME_NO_PERMISSIONS, null, E_USER_ERROR, "The main template file $s_directory/overall.tpl is not readable", 'FastFrame_Error', true);
            FastFrame::fatal($tmp_error, __FILE__, __LINE__); 
        }

        // now that we have the template directory, we can initialize the template engine
        parent::FastFrame_Template($s_directory);
        $this->load('overall.tpl', 'file');

        // create CSS
        $this->renderCSS();

        // some things are on for all page types
        $this->assignBlockData(
            array(
                'COPYWRITE' => 'Copywrite &#169; 2002 CodeJanitor.com',
                'CONTENT_ENCODING' => $this->o_registry->getConfigParam('general/charset', 'ISO-8559-1'),
                'U_SHORTCUT_ICON' => $this->o_registry->getConfigParam('general/favicon'),
                'CANVAS_WIDTH' => '100%',
            ),
            FASTFRAME_TEMPLATE_GLOBAL_BLOCK,
            false
        );

        // include some common js
        $this->assignBlockData(
            array(
                'T_javascript' => '<script language="Javascript" src="' . $this->o_registry->getRootFile('domTT.js', 'javascript', FASTFRAME_WEBPATH) . '"></script>' . "\n" .
                                  '<script language="Javascript" src="' . $this->o_registry->getRootFile('core.lib.js', 'javascript', FASTFRAME_WEBPATH) . '"></script>',
            ),
            'javascript'
        );

        // Always want this block touched because it is a wrapper for field & content cells.
        // It is in the template so we can do this: field cell | content cell | field cell | content cell...
        $this->makeBlockPersistent('table_cell_group');

        // always turn on main content
        $this->touchBlock('content_middle');

        // initialize menu to default type
        $this->setMenuType($this->o_registry->getConfigParam('menu/type'));
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
     * @return object FastFrame_Output instance
     */
    function &singleton()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new FastFrame_Output();
        }
        return $instance;
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
        $this->renderPageType();
        $this->prender();
    }

    // }}}
    // {{{ renderPageType()

    /**
     * Renders the page type.  Performs any necessary touches or assignments of data based
     * on the page type. 
     *
     * @access public
     * @return void 
     */
    function renderPageType()
    {
        $this->assignBlockData(
            array(
                'PAGE_TITLE' => $this->getPageTitle(),
            ),
            FASTFRAME_TEMPLATE_GLOBAL_BLOCK,
            false
        );
        
        // set up menu
        if ($this->menuType != 'none') {
            require_once dirname(__FILE__) . '/Menu.php';
            $o_menu =& FastFrame_Menu::factory($this->menuType);
            if (FastFrame_Error::isError($o_menu)) {
                FastFrame::fatal($o_menu, __FILE__, __LINE__); 
            }
            
            $o_menu->renderMenu();
        }

        switch ($this->pageType) {
            case 'popup':
                // a minimal page
            break;
            case 'smallTable':
                // override canvas width
                $this->assignBlockData(
                    array(
                        'CANVAS_WIDTH' => '450px',
                    ),
                    FASTFRAME_TEMPLATE_GLOBAL_BLOCK,
                    false
                );

                // move it down slightly
                $this->assignBlockData(
                    array(
                        'T_css' => '
                        <style type="text/css">
                        table.canvas {
                          margin-top: 100px;
                        }
                        </style>',
                    ),
                    'css'
                );

                $this->assignBlockData(
                    array(
                        'T_banner_bottom' => _('Run by FastFrame.  Licensed under the GPL.'),
                    ),
                    'switch_banner_bottom'
                );
            break;
            case 'normal':
                $this->assignBlockData(
                    array(
                        'T_banner_bottom' => _('Run by FastFrame.  Licensed under the GPL.'),
                    ),
                    'switch_banner_bottom'
                );
            break;
            default:
            break;
        }
    }

    // }}}
    // {{{  renderCSS()

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
        require_once 'System.php';
        $s_theme = $this->o_registry->getUserParam('theme');
        $s_cssCacheDir = $this->o_registry->getRootFile("css/$s_theme", 'cache');
        if (!@System::mkdir("-p $s_cssCacheDir"))  {
            return PEAR::raiseError(null, FASTFRAME_NO_PERMISSIONS, null, E_USER_WARNING, $s_cssCacheDir, 'FastFrame_Error', true);
        }

        $s_cssTemplateFile = $this->o_registry->getRootFile("$s_theme/style.tpl", 'themes');
        $s_browser = Net_UserAgent_Detect::getBrowser(array('ie', 'gecko'));
        // Determine the css file based on the browser
        $s_cssFileName = 'style-' . $s_browser . '.css';
        $s_cssCacheFile = File::buildPath(array($s_cssCacheDir, $s_cssFileName));

        // make the CSS file if needed
        if ($in_remakeCSS ||
            !file_exists($s_cssCacheFile) || 
            filemtime($s_cssTemplateFile) > filemtime($s_cssCacheFile)) {
            $o_tpl = new FastFrame_Template();
            $o_tpl->load($s_cssTemplateFile, 'file');
            // touch the browser specific block
            $o_tpl->touchBlock('switch_is_' . $s_browser);
            // set some variables
            $o_tpl->assignBlockData(
                array(
                    'THEME_DIR' => $this->o_registry->getRootFile($s_theme, 'themes', FASTFRAME_WEBPATH)
                ),
                FASTFRAME_TEMPLATE_GLOBAL_BLOCK,
                false
            );
            $s_data = $o_tpl->render();
            $fp = fopen($s_cssCacheFile, 'w');
            fwrite($fp, $s_data);
            fclose($fp);
        }

        $s_cssURL = $this->o_registry->getRootFile("css/$s_theme/$s_cssFileName", 'cache', FASTFRAME_WEBPATH);
        // register the CSS file 
        $this->assignBlockData(
            array(
                // put the filemtime on so that they only grab the new file when it is recreated
                'MAIN_CSS' => '<link rel="stylesheet" type="text/css" href="' . $s_cssURL . '?fresh=' . filemtime($s_cssCacheFile) . '" />',
            ),
            FASTFRAME_TEMPLATE_GLOBAL_BLOCK,
            false
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
     *                            status, title, tooltipType, class, target, onclick, style, help, confirm
     *
     * @access public
     * @return string Entire <a> tag plus attributes
     *
     * @see linkStart(), linkEnd()
     */
    function link($in_url, $in_text, $in_options = array())
    {
        return FastFrame_Output::linkStart($in_url, $in_options) . $in_text . FastFrame_Output::linkEnd();
    }

    // }}}
    // {{{ linkStart()

    /**
     * Return an anchor tag with the relevant parameters 
     *
     * @param  string $in_url The full URL to be linked to
     * @param  array  $in_options A number of options that have to do with the a tag.  The 
     *                            options are as follows:
     *                            status, title, tooltipType, class, target, onclick, style, help, confirm
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
            $a_options['onclick'] .= ' if (window.confirm(\'' . $s_confirm . '\')) { return true; } else { return false; }';
        }

        $events = $this->_prepare_tooltip(
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
        $s_tag .= $events['onmouseover'] != '' ? ' onmouseover="' . $events['onmouseover'] . '"' : '';
        $s_tag .= $events['onmousemove'] != '' ? ' onmousemove="' . $events['onmousemove'] . '"' : '';
        $s_tag .= $events['onclick'] != '' ? ' onclick="' . $events['onclick'] . '"' : '';
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
     * @param string $in_type The identifier of the window (i.e. mycode)
     * @param optional array $in_data Additional data to be passed through the link.
     * @param optional array $in_options Additional options.  Availabe options are
                              action, app, image, text, title, width, height, class
     * @param optional boolean $in_custom Specifies that this is a custom popup link
     *                        and thus does not use any of the predefined settings.
     *
     * @access public
     * @return string The link to the popup page
     */
    function popupLink($in_type, $in_data = array(), $in_options = array(), $in_custom = false)
    {
        if (!$in_custom) {
            $settings = array(
                'action' => array(
                    'bigtext'  => STANDARDFORM_BIGTEXT,
                ),
                'app' => array(
                    'bigtext'  => 'standardForm',
                ),
                'image' => array(
                    'bigtext'  => ' ' . FastFrame_Output::imgTag('textarea.gif', 'symbols'),
                ),
                'text' => array(
                    'bigtext'  => '',
                ),
                'title' => array(
                    'bigtext'  => _('Expand your text here'),
                ),
                'menubar' => array(
                    'bigtext'  => false, 
                ),
            );
        }
        else {
            $settings['action'][$in_type] = null;
            $settings['app'][$in_type] = null;
            $settings['image'][$in_type] = null;
            $settings['text'][$in_type] = null;
            $settings['title'][$in_type] = null;
            $settings['menubar'][$in_type] = null;
        }

        if (isset($in_options['action'])) {
            $settings['action'][$in_type] = $in_options['action'];
        }
        if (isset($in_options['app'])) {
            $settings['app'][$in_type] = $in_options['app'];
        }
        if (isset($in_options['image'])) {
            $settings['image'][$in_type] = $in_options['image'];
        }
        if (isset($in_options['text'])) {
            $settings['text'][$in_type] = $in_options['text'];
        }
        if (isset($in_options['title'])) {
            $settings['title'][$in_type] = $in_options['title'];
        }
        if (isset($in_options['menubar'])) {
            $settings['menubar'][$in_type] = $in_options['menubar'];
        }

        $settings['menubar'][$in_type] = (isset($settings['menubar'][$in_type]) && $settings['menubar'][$in_type]) ? 'yes' : 'no';
        $in_options['class'] = isset($in_options['class']) ? $in_options['class'] : '';
        $in_options['status'] = isset($in_options['status']) ? $in_options['status'] : '';
        $in_options['width'] = isset($in_options['width']) ? $in_options['width'] : 650;
        $in_options['height'] = isset($in_options['height']) ? $in_options['height'] : 500;
        $in_options['useHelp'] = isset($in_options['useHelp']) ? $in_options['useHelp'] : false;
 
        $js = $in_type . 'Window = window.open(\'';
        $js .= FastFrame::url($this->o_registry->getAppFile('index.php', $settings['app'][$in_type], '', FASTFRAME_WEBPATH), array('actionId' => $settings['action'][$in_type]), $in_data);
        $js .= '\',\'' . $in_type . 'Window\',';
        $js .= '\'toolbar=no,location=no,status=yes,menubar=' . $settings['menubar'][$in_type] . ',scrollbars=yes,resizable,alwaysRaised,dependent,titlebar=no,width=' . $in_options['width'] . ',height=' . $in_options['height'] . ',left=50,screenX=50,top=50,screenY=50\');';
        $js .= $in_type . 'Window.focus();';
        $js .= 'return false;';

        $link = $this->link('javascript: void(0);', $settings['text'][$in_type] . $settings['image'][$in_type], array('onclick' => $js, 'title' => $settings['title'][$in_type], 'status' => $in_options['status'], 'help' => $in_options['useHelp'], 'class' => $in_options['class']));
        $link .= '<script>var ' . $in_type . 'Window = null;</script>';
        return $link;
    }

    // }}}
    // {{{ imgTag()

    /**
     * Returns an image tag (either <input type="image"> or <img>) properly formatted and 
     * with the correct width and height attributes 
     *
     * @param string $in_img Name of image, if an absolute path is not given we prepend ffol path 
     * @param string $in_type (optional) The type of image.  If 'none' then we assume that
     *                        the path includes the type in it.
     * @param array  $in_options (optional) A number of options that have to do with the image tag, included 
     *                           what type of image tag this is.  The options are as follows
     *                           width, height, type, align, style, onclick, state, onlyURL
     *
     * @access public
     * @return string image tag
     */
    function imgTag($in_img = null, $in_type = 'icons', $in_options = array()) 
    {
        static $s_missingImage, $a_themePaths, $a_appPaths;

        // cache the missing image from the registry
        if (!isset($s_missingImage)) {
            $s_missingImage = $this->o_registry->getConfigParam('image/missing');
        }

        if (!isset($a_themePaths)) {
            $a_themePaths = array();
            $tmp_theme = $this->o_registry->getUserParam('theme');
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

        $in_options['state'] = isset($in_options['state']) ? $in_options['state'] : false;
        $s_type = $in_type == 'none' ? '/' : "/$in_type/";

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
            // can't change state of web image
            $in_options['state'] = false;
            $s_imgWebPath = $s_imgFilePath = $in_img;
        }

        // change the state of the image (i.e. active, disabled, etc.)
        if ($in_options['state'] && $in_options['state'] != 'normal') {
            if ($in_options['state'] == 'active') {
                // give it a red hue
                $tmp_settings = array('brightness' => 100, 'saturation' => 150, 'hue' => 10);
            }
            elseif ($in_options['state'] == 'disabled') {
                // gray it out
                $tmp_settings = array('brightness' => 150, 'saturation' => 0, 'hue' => 100);
            }
            else {
                $tmp_settings = array('brightness' => 100, 'saturation' => 0, 'hue' => 100);
            }

            // usually these are icons, so save the theme name in the cached image name
            $parts = pathinfo($s_imgFilePath);
            $parts = array_reverse(explode('/', $parts['dirname']));
            $theme = $parts[0];
            $tmp_newName = $in_options['state'].'-'.$theme.'-'.basename($s_imgFilePath); 
            $tmp_newFilePath = $this->o_registry->getRootFile("cache/$tmp_newName", 'graphics');
            $s_imgWebPath = $this->o_registry->getRootFile("cache/$tmp_newName", 'graphics', FASTFRAME_WEBPATH);
            FastFrame_Image::_modulate_image($s_imgFilePath, $tmp_newFilePath, $tmp_settings);
            $s_imgFilePath = $tmp_newFilePath;
            // add a special cursor style
            $in_options['style'] = isset($in_options['style']) ? $in_options['style'] : '';
            $in_options['style'] .= ' cursor: default;';
        }

        // cast $in_options to an array
        settype($in_options, 'array');

        // get size [!] (perhaps we can allow scaling here, not done here yet) [!]
        if (!isset($in_options['width']) || !isset($in_options['height'])) {
            list($width, $height) = getImageSize($s_imgFilePath);
            $in_options['width']  = isset($in_options['width']) ? $in_options['width'] : $width;
            $in_options['height'] = isset($in_options['height']) ? $in_options['height'] : $height;
        }

        if (!isset($in_options['align'])) {
            $in_options['align'] = 'middle';
        }

        // Prepare the options from the options array
        $in_options['align']   = isset($in_options['align']) ? " align=\"{$in_options['align']}\"" : '';
        $in_options['hspace']  = isset($in_options['hspace']) ? " hspace=\"{$in_options['hspace']}\"" : '';
        $in_options['vspace']  = isset($in_options['vspace']) ? " vspace=\"{$in_options['vspace']}\"" : '';
        $in_options['title']   = isset($in_options['title']) ? htmlspecialchars($in_options['title']) : '';
        if (!empty($in_options['title'])) {
            $title = 'title="'.$in_options['title'].'" alt="'.$in_options['title'].'"';
        }
        else {
            $title = '';
        }
        // set status to title if status does not exist
        $useStatus = isset($in_options['useStatus']) ? $in_options['useStatus'] : true;
        if ($useStatus) {
            $status = isset($in_options['status']) ? 
                addcslashes($in_options['status'], '\'') : 
                addcslashes($in_options['title'], '\'');
            $statusOver = 'window.status=\'' . $status . '\';';
            $statusOut = 'window.status=\'\';';
        }

        $in_options['border'] = isset($in_options['border']) ? $in_options['border'] : '0';
        $in_options['type'] = isset($in_options['type']) && $in_options['type'] == 'input' ? 'input type="img"' : 'img';
        // only add the style if it is not ns4
        $in_options['style'] = isset($in_options['style']) && !Net_UserAgent_Detect::isBrowser('ns4') ? " style=\"{$in_options['style']}\"" : '';
        $in_options['onclick'] = isset($in_options['onclick']) ? " onclick=\"{$in_options['onclick']}\"" : '';

        if (isset($in_options['onlyURL']) && $in_options['onlyURL']) {
            return $s_imgWebPath;
        }
        else {
            $tag = "<{$in_options['type']}";
            $tag .= " src=\"$s_imgWebPath\"";
            $tag .= " border=\"{$in_options['border']}\"";
            $tag .= " width=\"{$in_options['width']}\"";
            $tag .= " height=\"{$in_options['height']}\"";
            if ($useStatus) {
                $tag .= " onmouseover=\"$statusOver\"";
                $tag .= " onmouseout=\"$statusOut\"";
            }
            $tag .= "{$in_options['style']}";
            $tag .= "{$in_options['align']}";
            $tag .= "{$in_options['vspace']}";
            $tag .= "{$in_options['hspace']}";
            $tag .= "{$in_options['onclick']}";
            $tag .= " $title";
            $tag .= " />";

            return $tag;
        }
    }

    // }}}
    // {{{ pimgTag()

    /**
     * Prints an image tag (either input type="image" or img) properly formatted and 
     * with the correct width and height attributes 
     *
     * @see imgTag
     * @access public
     */
    function pimgTag($in_img = null, $in_type = 'icons', $in_options = array())
    {
        echo FastFrame_Image::imgTag($in_img, $in_type, $in_options);
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
    // {{{ processCellData()

    /**
     * Takes cell data, and if it is empty returns &nbsp;, otherwise
     * just returns the cell data
     *
     * @param string $in_data The cell data
     *
     * @access public
     * @return string Either &nbsp; if it is empty, otherwise the data
     */
    function processCellData($in_data)
    {
        if ($in_data == '' || $in_data === false) {
            return '&nbsp;';
        }
        else {
            return $in_data;
        }
    }

    // }}}
    // {{{ setMessage()

    /**
     * Set the message and the mode associated with it
     *
     * @param string $in_message The message to inform the user what has just taken place.
     * @param string $in_mode The mode, which is then translated into an image
     *
     * @access public
     * @return void
     */
    function setMessage($in_message, $in_mode = FASTFRAME_NORMAL_MESSAGE)
    {
        // keep track of count so we can have multiple messages on one page
        static $s_count;
        settype($s_count, 'int');
        $s_count++;

        $this->assignBlockData(
            array(
                'S_status_message_count' => $s_count,
                'T_status_message' => $in_message,
                'I_status_message' => $this->imgTag($in_mode, 'alerts'),
                'I_status_message_close' => $this->imgTag('close.gif', 'actions', array('onclick' => 'document.getElementById(\'message_' . $s_count . '\').style.display = \'none\';', 'style' => 'cursor: pointer;')),
            ),
            'status_message'
        );
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

        if (!FastFrame::isempty(($s_siteName = $this->o_registry->getConfigParam('general/site_name')))) {
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
        if (!empty($in_options['greasy'])) {
            $a_events[$in_options['greasy']] = 'greasy';
        }

        if (!empty($in_options['sticky'])) {
            $a_events[$in_options['sticky']] = 'sticky';
        }

        foreach ($a_events as $s_event => $s_type) {
            if (!$s_type) {
                continue;
            }

            $a_events[$s_event] = ($s_event == 'onmouseover' ? 'return domTT_true(' : '') . 
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
