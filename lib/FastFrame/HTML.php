<?php
/** $Id: HTML.php,v 1.3 2003/01/09 22:46:29 jrust Exp $ */
// {{{ includes

require_once dirname(__FILE__) . '/Template.php';

// }}}
// {{{ class FastFrame_HTML

/**
 * The FastFrame_HTML class provides access to functions 
 * which produce HTML code used to make common parts of a FastFrame page
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lesser.html.
 *
 * @author  Horde  http://www.horde.org/
 * @author  Jason Rust <jrust@rustyparts.com>
 * @author  Dan Allen <dan@mojavelinux.com>
 * @version Revision: 2.0 
 * @access  public
 * @package FastFrame
 */

// }}}
class FastFrame_HTML extends FastFrame_Template {
    // {{{ properties

    /**
     * FastFrame_Registry instance
     * @var object $FastFrame_Registry
     */
    var $FastFrame_Registry;

    /**
     * The default number of items displayed on a page if none specified
     * @var int $defaultLimit
     */
    var $defaultLimit = 30;

    /**
     * The default sort order on a page if none is specified 
     * @var string $defaultSortOrder
     */
    var $defaultSortOrder = 'ASC';

    /**
     * @var file Content file is the include file for the core of the page
     */
    var $contentFile = null;

    /**
     * @var type The type of template being made
     */
     var $templateType = '';

    /**
     * Variables which are always used in the list pages that utilize the search box and
     * navigation bar. 
     * @var array $persistentVariables
     */
    var $persistentVariables = array(
        'sortField',
        'sortOrder',
        'sortField',
        'searchString',
        'searchField',
        'advancedQuery',
        'pageOffset',
        'displayLimit',
    );

    /**
     * The page name used to make the title more descriptive
     * @var string $pageName
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
    function FastFrame_HTML()
    {
        $this->FastFrame_Registry =& FastFrame_Registry::singleton();

        $s_directory = $this->FastFrame_Registry->getRootFile($this->FastFrame_Registry->getUserParam('theme'), 'themes');
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
                'CONTENT_ENCODING' => $this->FastFrame_Registry->getConfigParam('general/charset', 'ISO-8559-1'),
                'U_SHORTCUT_ICON' => $this->FastFrame_Registry->getConfigParam('general/favicon'),
                'CANVAS_WIDTH' => '100%',
            ),
            FASTFRAME_TEMPLATE_GLOBAL_BLOCK,
            false
        );

        // include some common js
        $this->assignBlockData(
            array(
                'T_javascript' => '<script language="Javascript" src="' . $this->FastFrame_Registry->getRootFile('domTT.js', 'javascript', FASTFRAME_WEBPATH) . '"></script>',
            ),
            'javascript'
        );

        // Always want this block touched because it is a wrapper for field & content cells.
        // It is in the template so we can do this: field cell | content cell | field cell | content cell...
        $this->makeBlockPersistent('table_cell_group');

        // always turn on main content
        $this->touchBlock('content_middle');
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
     * @return object FastFrame_HTML instance
     */
    function &singleton()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new FastFrame_HTML();
        }
        return $instance;
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
        $s_theme = $this->FastFrame_Registry->getUserParam('theme');
        $s_cssCacheDir = $this->FastFrame_Registry->getRootFile("css/$s_theme", 'cache');
        FastFrame::mkdirp($s_cssCacheDir);
        // make sure the cache directory exists
        if (!is_dir($s_cssCacheDir)) {
            return PEAR::raiseError(null, FASTFRAME_NO_PERMISSIONS, null, E_USER_WARNING, $s_cssCacheDir, 'FastFrame_Error', true);
        }

        $s_cssTemplateFile = $this->FastFrame_Registry->getRootFile("$s_theme/style.tpl", 'themes');
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
                    'THEME_DIR' => $this->FastFrame_Registry->getRootFile($s_theme, 'themes', FASTFRAME_WEBPATH)
                ),
                FASTFRAME_TEMPLATE_GLOBAL_BLOCK,
                false
            );
            $s_data = $o_tpl->render();
            $fp = fopen($s_cssCacheFile, 'w');
            fwrite($fp, $s_data);
            fclose($fp);
        }

        $s_cssURL = $this->FastFrame_Registry->getRootFile("css/$s_theme/$s_cssFileName", 'cache', FASTFRAME_WEBPATH);
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
    // {{{ registerSectionLinks()

    /**
     * Creates the javascript cache file (which is actually a PHP file that returns
     * javascript, so that permissons can be applied). for the menu and then registers the
     * file as a javascript src file.
     *
     * @access public
     * @return void
     */
    function registerSectionLinks()
    {
        $menuVarsCacheFile = $this->FastFrame_Registry->getCompanyFile('menu_data.php', 'cache'); 
        $xbelDataFile = $this->FastFrame_Registry->getCompanyFile('admin.xbel', null, FASTFRAME_DATAPATH);
        if (!is_readable($xbelDataFile)) {
            $xbelDataFile = $this->FastFrame_Registry->getRootFile('admin.xbel', null, FASTFRAME_DATAPATH);
        }

        // create or recreate the cache file if it did not exist or the data file changed
        if (!file_exists($menuVarsCacheFile) || filemtime($xbelDataFile) > filemtime($menuVarsCacheFile)) {
            $xbel = new XML_XPath($xbelDataFile, 'file');
            $xbel->documentElement();
            $menuVarsPHP = array();
            $this->_parse_xbel($xbel, $menuVarsPHP);
            FastFrame::mkdirp(dirname($menuVarsCacheFile));
            File::write($menuVarsCacheFile, '<?php $menuVarsPHP = ' . var_export($menuVarsPHP, true) . '; ?>', FILE_MODE_WRITE);
        }
        else {
            include_once $menuVarsCacheFile;
        }
        
        $menuVarsFile = FastFrame::url($this->FastFrame_Registry->getRootFile('webscripts/menuVars.php', 'libs', FASTFRAME_WEBPATH));

        $this->assignBlockData(
            array(
                'T_javascript' => '<script language="Javascript" src="' . $menuVarsFile . '"></script>',
            ),
            'javascript'
        );
        $this->touchBlock('switch_sections');
    }

    // }}}
    // {{{ quickFormFactory()

    /**
     * Factory a PEAR HTML_QuickForm object
     *
     * We need some special features of PEAR's QuickForm class, so we will create
     * a factory here for generating a form with these preferences and return it.
     *
     * @return object HTML_QuickForm object
     */
    function &quickFormFactory($in_formName, $in_formMethod = 'POST', $in_formAction = null, $in_formTarget = '_self')
    {
        if (is_null($in_formAction)) {
            $in_formAction = FastFrame::selfURL();
        }

        $form =& new HTML_QuickForm($in_formName, $in_formMethod, $in_formAction, $in_formTarget);
        $form->clearAllTemplates();
        return $form;
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
        return FastFrame_HTML::linkStart($in_url, $in_options) . $in_text . FastFrame_HTML::linkEnd();
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
    * @return   string  The HTML to end a link 
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
                    'bigtext'  => ' ' . FastFrame_HTML::imgTag('textarea.gif', 'symbols'),
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
        $js .= FastFrame::url($this->FastFrame_Registry->getAppFile('index.php', $settings['app'][$in_type], '', FASTFRAME_WEBPATH), array('actionID' => $settings['action'][$in_type]), $in_data);
        $js .= '\',\'' . $in_type . 'Window\',';
        $js .= '\'toolbar=no,location=no,status=yes,menubar=' . $settings['menubar'][$in_type] . ',scrollbars=yes,resizable,alwaysRaised,dependent,titlebar=no,width=' . $in_options['width'] . ',height=' . $in_options['height'] . ',left=50,screenX=50,top=50,screenY=50\');';
        $js .= $in_type . 'Window.focus();';
        $js .= 'return false;';

        $link = FastFrame_HTML::link('javascript: void(0);', $settings['text'][$in_type] . $settings['image'][$in_type], array('onclick' => $js, 'title' => $settings['title'][$in_type], 'status' => $in_options['status'], 'help' => $in_options['useHelp'], 'class' => $in_options['class']));
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
     * @param string $in_type (optional) The type of image
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
            $s_missingImage = $this->FastFrame_Registry->getConfigParam('image/missing');
        }

        if (!isset($a_themePaths)) {
            $a_themePaths = array();
            $tmp_theme = $this->FastFrame_Registry->getUserParam('theme');
            $a_themePaths['web'] = $this->FastFrame_Registry->getRootFile($tmp_theme . '/graphics', 'themes', FASTFRAME_WEBPATH);
            $a_themePaths['file'] = $this->FastFrame_Registry->getRootFile($tmp_theme . '/graphics', 'themes');
        }

        if (!isset($a_appPaths)) {
            $a_appPaths = array();
            $a_appPaths['web'] = $this->FastFrame_Registry->getAppFile('', null, 'graphics', FASTFRAME_WEBPATH);
            $a_appPaths['file'] = $this->FastFrame_Registry->getAppFile('', null, 'graphics');
        }

        if (empty($in_img) || is_null($in_img)) {
            $in_img = $s_missingImage;
        }

        $in_options['state'] = isset($in_options['state']) ? $in_options['state'] : false;

        // if image is an absolute path, we assume it is a webpath and check if it exists
        if (strpos($in_img, '/') === 0) {
            if (!file_exists($s_imgFilePath = $this->FastFrame_Registry->getConfigParam('webserver/file_root') . $in_img)) {
                $s_imgWebPath = $this->FastFrame_Registry->getRootFile($s_missingImage, 'graphics', FASTFRAME_WEBPATH);
                $s_imgFilePath = $this->FastFrame_Registry->getRootFile($s_missingImage, 'graphics');
            }
            else {
                $s_imgWebPath = $in_img;
            }
        }
        // see if it's a relative path to the image
        elseif (!preg_match(';^(http://|ftp://);', $in_img)) {
            $tmp_img = "/$in_type/$in_img";
            // see if it's in the theme directory
            if (file_exists(($s_imgFilePath = $a_themePaths['file'] . $tmp_img))) {
                $s_imgWebPath = $a_themePaths['web'] . $tmp_img;
            }
            // see if it's in the app directory
            elseif (file_exists(($s_imgFilePath = $a_appPaths['file'] . $tmp_img))) {
                $s_imgWebPath = $a_appPaths['web'] . $tmp_img;
            }
            // check in root graphics dir
            elseif (file_exists($s_imgFilePath = $this->FastFrame_Registry->getRootFile($tmp_img, 'graphics'))) {
                $s_imgWebPath = $this->FastFrame_Registry->getRootFile($tmp_img, 'graphics', FASTFRAME_WEBPATH);
            }
            // image not found
            else {
                $s_imgWebPath = $this->FastFrame_Registry->getRootFile($s_missingImage, 'graphics', FASTFRAME_WEBPATH);
                $s_imgFilePath = $this->FastFrame_Registry->getRootFile($s_missingImage, 'graphics');
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
            $tmp_newFilePath = $this->FastFrame_Registry->getRootFile("cache/$tmp_newName", 'graphics');
            $s_imgWebPath = $this->FastFrame_Registry->getRootFile("cache/$tmp_newName", 'graphics', FASTFRAME_WEBPATH);
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
            $statusOver = 'window.status=\'$status\';';
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
     * @param string $in_img Name of image, if an absolute path is not given we prepend ffol path 
     * @param array  $in_options A number of options that have to do with the image tag, included 
     *                           what type of image tag this is.  The options are as follows
     *                           width, height, type, align, style
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
    function setPageType($in_type = 'normal')
    {
        // don't do anything if no change and we have been here already
        if ($this->templateType == $in_type) {
            return;
        }
        else {
            $this->templateType = $in_type;
        }

        // do this down here because it requires contentFile to be set
        $this->assignBlockData(
            array(
                'PAGE_TITLE' => $this->getPageTitle(),
            ),
            FASTFRAME_TEMPLATE_GLOBAL_BLOCK,
            false
        );

        switch ($in_type) {
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
                // load menu js
                $this->assignBlockData(
                    array(
                        'T_javascript' => '<script type="text/javascript" src="' . $this->FastFrame_Registry->getRootFile('domMenu.js', 'javascript', FASTFRAME_WEBPATH) . '"></script>' . "\n" .
                                          '<script type="text/javascript" src="' . $this->FastFrame_Registry->getRootFile('domMenu_items.js', 'javascript', FASTFRAME_WEBPATH) . '"></script>',
                    ),
                    'javascript'
                );

                // turn on menu
                $this->touchBlock('switch_menu');

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
    // {{{ registerNavigationBar()

    /**
     * Registers a navigation bar that has arrows for going from page to page in a data set. 
     *
     * @param int   $in_matchedRecords The total number of matched records in the data set 
     * @param array $in_persistentVariables (optional) Additonal info to pass through the query string.
     *              If not passed we get it from getPersistentVariables()
     *
     * @access public
     * @return void 
     */
    function registerNavigationBar($in_matchedRecords, $in_persistentVariables = null)
    {
        // language constructs used in navigation cell
        $lang_firstPage = array('title' => _('First'),    'status' => _('Jump to First Page'));
        $lang_nextPage  = array('title' => _('Next'),     'status' => _('Jump to Next Page'));
        $lang_prevPage  = array('title' => _('Previous'), 'status' => _('Jump to Previous Page'));
        $lang_lastPage  = array('title' => _('Last'),     'status' => _('Jump to Last Page'));
        $lang_atFirst   = array('title' => _('Disabled'), 'status' => _('Already at First Page'));
        $lang_atLast    = array('title' => _('Disabled'), 'status' => _('Already at Last Page'));
      
        if (is_null($in_persistentVariables)) {
            $in_persistentVariables = $this->getPersistentVariables();
        }

        if (!isset($in_persistentVariables['actionID'])) {
            $in_persistentVariables['actionID'] = FastFrame::getCGIParam('actionID', 'gp'); 
        }

        // make sure our display displayLimit is not 0 or empty
        if (abs($in_persistentVariables['displayLimit']) == 0) {
            $in_persistentVariables['displayLimit'] = $this->getDefaultLimit(); 
        }

        if (abs($in_persistentVariables['pageOffset']) == 0) {
            $in_persistentVariables['pageOffset'] = 1;
        }

        $total  = ceil($in_matchedRecords / $in_persistentVariables['displayLimit']);
        $current = $in_persistentVariables['pageOffset'];

        $first = 1;
        $prev = max(1, $current - 1);
        $next = min($total, $current + 1);
        $last = $total;

        // note that we override pageOffset in $in_persistentVariables 
        $this->assignBlockData(
            array(
                'I_navigation_first'    => $current > 1 ? $this->link(FastFrame::selfURL($in_persistentVariables, array('pageOffset' => $first)), $this->imgTag('first.gif', 'arrows'), $lang_firstPage) : $this->imgTag('first-gray.gif', 'arrows', $lang_atFirst),
                'I_navigation_previous' => $current > 1 ? $this->link(FastFrame::selfURL($in_persistentVariables, array('pageOffset' => $prev)), $this->imgTag('prev.gif', 'arrows'), $lang_prevPage) : $this->imgTag('prev-gray.gif', 'arrows', $lang_atFirst),
                'I_navigation_next'     => $current < $last ? $this->link(FastFrame::selfURL($in_persistentVariables, array('pageOffset' => $next)), $this->imgTag('next.gif', 'arrows'), $lang_nextPage) : $this->imgTag('next-gray.gif', 'arrows', $lang_atLast),
                'I_navigation_last'     => $current < $last ? $this->link(FastFrame::selfURL($in_persistentVariables, array('pageOffset' => $last)), $this->imgTag('last.gif', 'arrows'), $lang_lastPage) : $this->imgTag('last-gray.gif', 'arrows', $lang_atLast),
            ),
            FASTFRAME_TEMPLATE_GLOBAL_BLOCK,
            false
        );
    }

    // }}}
    // {{{ registerSearchBox()

    /**
     * Create a search/navigation box at the top of the page
     *
     * This function will return a table which will facilitate searching the fields
     * provides as well as reworking the navigation view.  It requires a dataInfo array
     * which holds the matchedRecords and the totalAvailable records.  The displayInfo array
     * holds the information on the current navigation view and the searchFields will hold
     * all of the displayed fields which allow searching...sorting is inherient from the
     * searching.
     *
     * @param  array   $in_elementTitles singular and plural description of the data 
     * @param  array   $in_dataInfo array('totalRecords' =>, 'matchedRecords' =>, 'listedRecords' =>)
     * @param  array   $in_searchFields array of fields that are searchable
     * @param  array   $in_sortFields (optional) array of fields that are sortable.  If not
     *                 passed then we use the searchFields minus the all option.
     * @param  array   $in_persistentVariables (optional) Array of any persistent data. array('pageOffset' =>, 
     *                 'sortField' =>, 'sortOrder' =>, 'displayLimit' =>, 'actionID' =>, 'advView' =>).  
     *                 All grabbed by getPersistentVariables() if not passed.  You can pass in more if needed.
     *
     * @access public
     * @return string table for the search box
     */
    function registerSearchBox($in_elementTitles, $in_dataInfo, $in_searchFields, $in_sortFields = null, $in_persistentVariables = null)
    {
        // {{{ variable preparation

        // setup the element singular/plural names
        if (is_array($in_elementTitles)) {
            $s_pluralElement = $in_elementTitles['plural'];
            $s_singularElement = $in_elementTitles['singular'];
        }
        else {
            $s_pluralElement = $s_singularElement = $in_elementTitles;
        }

        // handle non-required arguments
        if (is_null($in_persistentVariables)) {
            $in_persistentVariables = $this->getPersistentVariables();
        }

        if (is_null($in_sortFields)) {
            $in_sortFields = $in_searchFields;
            unset($in_sortFields['all']);
        }


        // make sure our display displayLimit is not 0 or empty
        if (abs($in_persistentVariables['displayLimit']) == 0) {
            $in_persistentVariables['displayLimit'] = $this->getDefaultLimit(); 
        }

        if (abs($in_persistentVariables['pageOffset']) == 0) {
            $in_persistentVariables['pageOffset'] = 1;
        }

        $s_totalPages  = ceil($in_dataInfo['matchedRecords'] / $in_persistentVariables['displayLimit']);

        if ($in_dataInfo['totalRecords'] > 0) {
            $matchedRecordsPercent = round(($in_dataInfo['matchedRecords'] / $in_dataInfo['totalRecords']) * 100, 2);
        }
        else {
            $matchedRecordsPercent = 0;
        }

        // }}}
        // {{{ quickform preparation

        $o_form =& $this->quickFormFactory('search_box');
        $a_constants = $in_persistentVariables;
        $a_constants['actionID'] = $GLOBALS['a_persistent']['actionID'];
        $a_constants['oldLimit'] = $a_constants['displayLimit']; 
        $o_form->setConstants($a_constants);
        
        // make sure our display displayLimit is not to big so the page will render
        $s_displayLimitCheck = 'if(document.search_box.displayLimit.value > 100){ document.search_box.displayLimit.value = 100; }';

        // Add all the form elements
        if ($in_persistentVariables['advancedQuery']) {
            $o_form->addElement('text', 'displayLimit', null, array('class' => 'input_content', 'style' => 'vertical-align: middle;', 'size' => 3, 'maxlength' => 3));
            $o_form->addRule('displayLimit', _('Limit must be an integer'), 'nonzero', null, 'client', true); 
            $o_form->addElement('submit', 'displayLimit_submit', _('Update'), array('onclick' => $s_displayLimitCheck, 'style' => 'vertical-align: middle;'));
            $o_form->addElement('select', 'searchField', null, $in_searchFields, array('class' => 'input_content', 'style' => 'vertical-align: text-top;')); 
            $o_form->addElement('text', 'searchString', null, array('size' => 15, 'class' => 'input_content', 'style' => 'vertical-align: text-top;'));

            // need to set page offset to one when we search
            if ($s_totalPages > 1) {
                $tmp_onclick = 'document.search_box.pageOffset.options[0].selected = true;';
            }
            else {
                $tmp_onclick = 'void(0);';
            }

            $o_form->addElement('submit', 'query_submit', _('Search'), array('onclick' => $s_displayLimitCheck . ' ' . $tmp_onclick, 'style' => 'vertical-align: middle;'));
            $o_form->addElement('submit', 'listall_submit', _('List All'), array('onclick' => $s_displayLimitCheck . ' document.search_box.searchString.value = \'\';' . $tmp_onclick, 'style' => 'vertical-align: middle;'));

            $o_form->addElement('select', 'sortOrder', null, array(0 => 'DESC', 1 => 'ASC'), array('class' => 'input_content'));
            $o_form->addElement('select', 'sortField', null, $in_sortFields, array('class' => 'input_content'));
            $o_form->addElement('submit', 'sort_submit', _('Sort'), null, array('onclick' => $s_displayLimitCheck));
        }
        // just make them all hidden if not in advanced view
        else {
            $o_form->addElement('hidden', 'displayLimit');
            $o_form->addElement('hidden', 'searchField');
            $o_form->addElement('hidden', 'searchString');
            $o_form->addElement('hidden', 'sortOrder');
            $o_form->addElement('hidden', 'sortField');
            // need at least one rule so the js form is made
            $o_form->addRule('displayLimit', _('Limit must be an integer'), 'nonzero', null, 'client', true);
        }

        // common elements on all pages
        $o_form->addElement('checkbox', 'advancedQuery', null, _('Advanced Search'), array('onclick' => 'if (this.checked) { this.form.submit(); } else if (validate_search_box()) { document.search_box.searchString.value = \'\'; this.form.submit(); } else { return false; }'));
        $o_form->addElement('hidden','actionID');
        $o_form->addElement('hidden','oldLimit');

        // add as hidden elements any persistent data keys we don't know about
        foreach ($in_persistentVariables as $key => $val) {
            if (!in_array($key, $this->persistentVariables)) {
                $o_form->addElement('hidden', $key);
            }
        }

        // Construct the menu ring for jumping to different blocks of table
        if ($s_totalPages > 1) {
            foreach(range(1, $s_totalPages) as $i) {
                $a_pageOptions[$i] = $i . ' of ' . $s_totalPages;
            }

            // you can't change anything when changing pages...doesn't make sense, so
            // reset the form before going on to the next page
            $o_form->addElement('select', 'pageOffset', null, $a_pageOptions, array('style' => 'vertical-align: middle;', 'onchange' => 'var tmp = this.selectedIndex; this.form.reset(); this.options[tmp].selected = true; if (validate_search_box()) { this.form.submit(); } else { return false; }'));
            $s_pagination = $o_form->renderElement('pageOffset', true);
        }
        else {
            $o_form->addElement('hidden', 'pageOffset');
            $s_pagination = '1 of 1' . $o_form->renderElement('pageOffset');
        }

        // }}}
        // {{{ template preparation

        $this->touchBlock('switch_search_box');
        $s_namespace = $this->branchBlockNS('SEARCH_BOX', 'search_box', 'genericTable.tpl', 'file');
        $this->assignBlockCallback(array(&$o_form, 'toHtml'), array(), 'search_box');

        $this->assignBlockData(
            array(
                'S_TABLE_COLUMNS'        => 3,
                'T_table_header'         => _('Search Results and Options'),
                'S_table_header'         => 'style="text-align: center;"'
            ),
            'search_box'
        );

        $this->touchBlock($s_namespace . 'switch_table_header');


        // Add the data to the second row
        $this->assignBlockData(
            array(
                'S_table_field_cell' => 'style="text-align: center; vertical-align: middle; width: 33%;"',
            ),
            $s_namespace . 'table_row'
        );

        $this->cycleBlock($s_namespace . 'table_content_cell');
        $this->cycleBlock($s_namespace . 'table_field_cell');

        $this->assignBlockData(
            array(
                'T_table_field_cell' => 'Viewing Page ' . $s_pagination,
            ),
            $s_namespace . 'table_field_cell'
        );

        $tmp_text = sprintf(
                        _('%1$d Found (%2$d%%), %3$d Listed out of %4$d Total %5$s'),
                        $in_dataInfo['matchedRecords'], 
                        $matchedRecordsPercent, 
                        min($in_persistentVariables['displayLimit'], $in_dataInfo['listedRecords']),
                        $in_dataInfo['totalRecords'], 
                        $in_dataInfo['totalRecords'] == 1 ? $s_singularElement : $s_pluralElement
                    );

        $this->assignBlockData(
            array(
                'T_table_field_cell' => $tmp_text,
            ),
            $s_namespace . 'table_field_cell'
        );

        $this->assignBlockData(
            array(
                'T_table_field_cell' => $o_form->renderElement('advancedQuery', true),
                'S_table_field_cell' => 'style="white-space: nowrap"',
            ),
            $s_namespace . 'table_field_cell'
        );

        // Add the data to the first row
        if ($in_persistentVariables['advancedQuery']) {
            // assigns attributes to all cells in the row
            $this->assignBlockData(
                array(
                    'S_table_content_cell' => 'style="text-align: center; vertical-align: middle; width: 33%; white-space: nowrap;"',
                ),
                $s_namespace . 'table_row'
            );

            $this->cycleBlock($s_namespace . 'table_content_cell');
            
            $tmp_text = sprintf(
                            _('%1$s rows per page %2$s'), 
                            $o_form->renderElement('displayLimit', true), 
                            $o_form->renderElement('displayLimit_submit', true)
                        );

            $this->assignBlockData(
                array(
                    'T_table_content_cell' => $tmp_text,
                ), 
                $s_namespace . 'table_content_cell'
            );

            $tmp_text = sprintf(
                            _('Find %1$s in %2$s'), 
                            $o_form->renderElement('searchString', true), 
                            $o_form->renderElement('searchField', true) . ' ' .
                            $o_form->renderElement('query_submit', true) . ' ' .
                            $o_form->renderElement('listall_submit', true)
                        );

            $this->assignBlockData(
                array(
                    'T_table_content_cell' => $tmp_text,
                ), 
                $s_namespace . 'table_content_cell'
            );

            $this->assignBlockData(
                array(
                    'T_table_content_cell' => $o_form->renderElement('sortOrder', true) . ' ' .
                                              $o_form->renderElement('sortField', true) . ' ' .
                                              $o_form->renderElement('sort_submit', true),
                ),
                $s_namespace . 'table_content_cell'
            );
        }

        // }}}
    }

    // }}}
    // {{{ registerSortFields()

    /**
     * Generate a set of column headers which are HTML'd to have images and links so they
     * can be clicked on to sort the page.
     *
     * @param array $in_fieldData A multidimensional array with the primary keys in the order
     *              the columns are to be displayed.  That key contains an array with the
     *              name to display in the column cell and the sort column (number or name,
     *              leave blank if that column is not sortable) 
     *              e.g. $colHeader[0] = array('name' => 'Col 1', 'sort' => 'col1');
     * @param string $in_tableNamespace (optional) The namespace for the table to register
     *               the cells to.  If not passed in then we just return the array of sort
     *               fields.  It is up to you to branch the block, and assign
     *               global table variables such as S_TABLE_COLUMNS
     * @param array $in_persistentVariables (optional) Any persistent data to be passed on
     *              through the link.  If not sent in we get it form getPersistentVariables()
     *
     * @requires 'sortField', 'sortOrder', 'pageOffset'. All these must be in persistentData 
     * @return mixed Either nothing (we register the cells) or an array of the sort fields 
     */
    function registerSortFields($in_fieldData, $in_tableNamespace = null, $in_persistentVariables = null)
    {
        $s_defaultSort = ($this->getDefaultSortOrder() == 'ASC') ? 1 : 0;
        $a_fieldCells = array();
        settype($in_fieldData, 'array');
        if (is_null($in_persistentVariables)) {
            $in_persistentVariables = $this->getPersistentVariables();
        }

        foreach ($in_fieldData as $a_fieldData) {
            // check to see if it is sortable, and if so build the link
            if (!empty($a_fieldData['sort'])) {
                // if we previously sorted on this field, reverse the sort direction
                if ($in_persistentVariables['sortField'] == $a_fieldData['sort']) {
                    $tmp_sort = $in_persistentVariables['sortOrder'] ? 0 : 1;
                }
                // this is a new column sort, default sort
                else {
                    $tmp_sort = $s_defaultSort;
                }

                // modify the two get variables for this sort
                $a_persistent = array_merge($in_persistentVariables, array('sortField' => $a_fieldData['sort'], 'sortOrder' => $tmp_sort));

                $tmp_title = sprintf(
                                _('Sort %1$s (%2$s)'), 
                                $a_fieldData['name'], 
                                $a_persistent['sortOrder'] ? _('Ascending') : _('Descending')
                            ); 

                $tmp_href = $this->link(
                    FastFrame::selfURL($a_persistent), 
                    $a_fieldData['name'],
                    array(
                        'title' => $tmp_title, 
                    )
                );

                // see if we should display an arrow 
                if ($in_persistentVariables['sortField'] == $a_fieldData['sort']) {
                    // Determine which image to display
                    $tmp_img = $in_persistentVariables['sortOrder'] ? 
                        $this->imgTag('up.gif', 'arrows', array('align' => 'middle')) : 
                        $this->imgTag('down.gif', 'arrows', array('align' => 'middle'));
                    $tmp_href .= ' ' . $this->link(FastFrame::selfURL($a_persistent), $tmp_img, array('title' => $tmp_title)); 
                }
                
                $a_fieldCells[] = $tmp_href;
            }
            else {
                $a_fieldCells[] = $a_fieldData['name'];
            }
        }

        // now register the sort fields if that was specified
        if (!is_null($in_tableNamespace)) {
            $this->touchBlock($in_tableNamespace . 'table_row');
            $this->cycleBlock($in_tableNamespace . 'table_field_cell');
            $this->cycleBlock($in_tableNamespace . 'table_content_cell');
            foreach ($a_fieldCells as $s_cell) {
                $this->assignBlockData(
                    array(
                        'T_table_field_cell' => $s_cell,
                    ),
                    $in_tableNamespace . 'table_field_cell'
                );
            }
        }
        else {
            return $a_fieldCells;
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
    // {{{ setContentFile()

    /**
     * Sets the main content file for the page
     *
     * @param string $in_file The PHP include file to set as content file
     *
     * @access public
     * @return void
     */
    function setContentFile($in_file) 
    {
        $this->contentFile = $in_file;
    }

    // }}}
    // {{{ getContentFile()

    /**
     * Returns the content file
     *
     * @access public
     * @return string The PHP content file name
     */
    function getContentFile() 
    {
        return $this->contentFile;
    }

    // }}}
    // {{{ getPageTitle()

    /**
     * Constructs the page title
     *
     * @requires $GLOBALS['s_appName'], and the contentFile
     * @access public
     * @return string The page title
     */
    function getPageTitle()
    {
        $s_title = $this->FastFrame_Registry->getAppParam('name', 'NOOP');
        $s_page = $this->getPageName();

        if ($s_title != $s_page) {
            $s_title = "$s_title :: $s_page";
        }

        if (!FastFrame::isempty(($s_siteName = $this->FastFrame_Registry->getConfigParam('general/site_name')))) {
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
    // {{{ getPageName()

    /**
     * Gets the page name
     *
     * @access public
     * @return string The page name
     */
    function getPageName()
    {
        return $this->pageName;
    }

    // }}}
    // {{{ getDefaultSortField()

    /**
     * Get the default sort field from the registry
     *
     * @access public
     * @return string The default sort field or null if it's not set
     */
    function getDefaultSortField()
    {
        return $this->FastFrame_Registry->getConfigParam('data/default_sort');
    }

    // }}}
    // {{{ getDefaultSortOrder()

    /**
     * Get the default sort order(maybe someday this will interface with the config as well
     *
     * @access public
     * @return string The default sort order (either 'ASC' or 'DESC') 
     */
    function getDefaultSortOrder()
    {
        return $this->defaultSortOrder;
    }

    // }}}
    // {{{ getDefaultLimit()

    /**
     * Get the default displayLimit (maybe someday this will interface with the config as well
     *
     * @access public
     * @return int The default displayLimit for list pages
     */
    function getDefaultLimit()
    {
        return $this->defaultLimit;
    }

    // }}}
    // {{{ getOptionsFromXML()

    /**
     * Gets a list of options suitable for generating
     * a select list (using QuickForm) from an XML file
     * in the form of <element value="value">Text</element>
     *
     * @param string $in_file Either a keyword for a file
     *               (currently: states) or the full path the xml file
     * @param string $in_path (optional) The path to the elements
     * @param string $in_attribute (optional) The attribute that has the value 
     *
     * @access public
     * @return array Array of options
     */
    function getOptionsFromXML($in_file, $in_path = '//Elements/Element', $in_attribute = 'value')
    {
        // get the file
        switch ($in_file) {
            case 'states':
                $xmlFile = $this->FastFrame_Registry->getRootFile('states.xml', '', FASTFRAME_DATAPATH);
                $path = '//States/State';
                $attribute = $in_attribute;
                break;
            default:
                $xmlFile = $in_file;
                $path = $in_path;
                $attribute = $in_attribute;
                break;
        }
        
        $xml = new XML_Xpath();
        $xml->load($xmlFile, 'file');
        $result = $xml->evaluate($path);
        if (XML_Xpath::isError($result)) {
            return $result;
        }

        $options = array();
        while($result->next()) {
            $options[$result->getAttribute($attribute)] = $result->substringData();
        }
        asort($options);

        return $options;
    }

    // }}}
    // {{{ getPersistentVariables()
    
    /**
     * Grabs/calculates variabes that are used when we create search boxes and navigation
     * bars.  These are grabbed from GET/POST/SESSION and returned in an associative array.
     *
     * @param array $in_constants (optional) An associative array of keys and values
     *              that allow you to override the default values or add new values.  i.e.
     *              'pageOffset' => 2
     *
     * @see $persistentVariables
     * @access public
     * @return array The array of persistent variables 
     */
    function getPersistentVariables($in_constants = array())
    {
        static $a_persistentVariables;

        // get persistent vars if we haven't yet
        if (!isset($a_persistentVariables)) {
            $a_persistentVariables = array();
            foreach($this->persistentVariables as $s_var) {
                switch ($s_var) {
                    case 'advancedQuery':
                        $a_persistentVariables[$s_var] = FastFrame::getCGIParam($s_var, 'gp', 0);
                    break;
                    case 'displayLimit':
                        $a_persistentVariables[$s_var] = (int) abs(FastFrame::getCGIParam($s_var, 'gps', $this->getDefaultLimit()));
                    break;
                    case 'pageOffset':
                        $a_persistentVariables[$s_var] = (int) abs(FastFrame::getCGIParam($s_var, 'gps', 1));  
                    break;
                    case 'sortOrder':
                        $a_persistentVariables[$s_var] = FastFrame::getCGIParam($s_var, 'gps', $this->getDefaultSortOrder());
                    break;
                    case 'sortField':
                        $a_persistentVariables[$s_var] = FastFrame::getCGIParam($s_var, 'gps', $this->getDefaultSortField());
                    break;
                    case 'searchString':
                    case 'searchField':
                        $a_persistent[$s_var] = FastFrame::getCGIParam($s_var, 'gps', '');
                    break;
                    default:
                        $a_persistent[$s_var] = FastFrame::getCGIParam($s_var, 'gp');
                    break;
                }
            }

            // offset is a special one calculated from others and not passed via gps
            $a_persistentVariables['offset'] = $a_persistentVariables['displayLimit'] ? 
                                               ($a_persistentVariables['pageOffset'] - 1) * $a_persistentVariables['displayLimit'] : 
                                               0;
        }

        // now do any overrides (overrides are not persistent, however)
        if (count((array) $in_constants) > 0) {
            $a_return = $a_persistentVariables;
            foreach ($in_constants as $s_key => $s_value) {
                $a_return[$s_key] = $s_value;
            }

            return $a_return;
        }
        else {
            return $a_persistentVariables;
        }
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
