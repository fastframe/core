<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2005 The Codejanitor Group                        |
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
// {{{ constants

/**
 * Message constants.  These images need to be in the graphics/alerts directory.
 * The theme can mimic the grapics/alerts directory to override the default image.
 */
define('FASTFRAME_NORMAL_MESSAGE', 'info.gif', true);
define('FASTFRAME_ERROR_MESSAGE', 'error.gif', true);
define('FASTFRAME_WARNING_MESSAGE', 'warning.gif', true);
define('FASTFRAME_SUCCESS_MESSAGE', 'success.gif', true);

// }}}
// {{{ class FF_Output

/**
 * The FF_Output:: class provides access to functions 
 * for basic HTML snippets for FastFrame pages.
 *
 * @author  The Horde Team <http://www.horde.org/>
 * @author  Jason Rust <jrust@codejanitor.com>
 * @author  Dan Allen <dan@mojavelinux.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_Output {
    // {{{ properties

    /**
     * o_registry instance
     * @var object
     */
    var $o_registry;

    /**
     * The array of messages
     * @var array
     */
    var $messages = array();

    // }}}
    // {{{ factory()

    /**
     * Attempts to return a concrete Output instance based on $type.
     *
     * @param string $in_type (optional) The type of Output to create.  The code is
     *        dynamically included, and we will look for Output/$type.php
     *        to load the file.
     *
     * @access public
     * @return object The newly created concrete Output instance
     */
    function &factory($in_type = null)
    {
        static $a_instances;
        if (!isset($a_instances)) {
            $a_instances = array();
        }

        if (is_null($in_type)) {
            $in_type = IS_AJAX ? 'AJAX' : 'Page';
        }

        $s_class = 'FF_Output_' . $in_type;
        if (!isset($a_instances[$s_class])) {
            $pth_menu = dirname(__FILE__) . '/Output/' . $in_type . '.php';
            if (file_exists($pth_menu)) { 
                require_once $pth_menu;
                $a_instances[$s_class] = new $s_class($in_type);
            } 
            else {
                trigger_error("Invalid Output type: $in_type", E_USER_ERROR); 
            }
        }

        return $a_instances[$s_class];
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
        // interface
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
     *                            status, title, class, target, onclick, style, confirm, id,
     *                            getAccessKey (attempts to get an accesskey for the link)
     *
     * @access public
     * @return string Entire <a> tag plus attributes
     */
    function link($in_url, $in_text, $in_options = array())
    {
        $a_options = array('title' => '', 'caption' => '', 'status' => '',
                'confirm' => '', 'onclick' => '', 'class' => '', 'id' => '',
                'style' => '', 'target' => '', 'getAccessKey' => false);

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
            // Title is replaced by a tooltip
            $s_title = $a_options['title'];
            $a_options['title'] = '';
            $a_options['class'] .= ' tt';
        }

        // build the tag
        $s_tag = '<a href="' . $in_url . '"';
        $s_tag .= !empty($a_options['onmouseover']) ? ' onmouseover="' . $a_options['onmouseover'] . '"' : '';
        $s_tag .= !empty($a_options['onmousemove']) ? ' onmousemove="' . $a_options['onmousemove'] . '"' : '';
        $s_tag .= !empty($a_options['onclick']) ? ' onclick="' . $a_options['onclick'] . '"' : '';
        $s_tag .= !empty($a_options['title']) ? ' title="' . $a_options['title'] . '"' : '';
        $s_tag .= !empty($a_options['class']) ? ' class="' . $a_options['class'] . '"' : '';
        $s_tag .= !empty($a_options['style']) ? ' style="' . $a_options['style'] . '"' : '';
        $s_tag .= !empty($a_options['id']) ? ' id="' . $a_options['id'] . '"' : '';
        $s_tag .= !empty($a_options['target']) ? ' target="' . $a_options['target'] . '"' : '';
        $s_tag .= '>';
        $s_tag .= $this->highlightAccessKey($in_text, $s_ak);
        if (!empty($a_options['caption'])) {
            $s_tag .= '<div class="tt_wrapper"><div class="tt_caption">' . $a_options['caption'] . '</div><div class="tt_text">' . $s_title . '</div></div>';
        }
            
        $s_tag .= '</a>';
        return $s_tag;
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
        $in_options['caption'] = isset($in_options['caption']) ? $in_options['caption'] : '';
        $in_options['status'] = isset($in_options['status']) ? $in_options['status'] : '';
        $in_options['width'] = isset($in_options['width']) ? $in_options['width'] : 650;
        $in_options['height'] = isset($in_options['height']) ? $in_options['height'] : 500;
 
        $s_url = FastFrame::selfURL($in_urlParams);
        $s_js = $in_name . ' = window.open(this.href,' . 
                '\'' . $in_name . '\',' .
                '\'toolbar=no,location=no,status=yes,menubar=' . $in_options['menubar'] . 
                ',scrollbars=yes,resizable,alwaysRaised,dependent,titlebar=no' . 
                ',width=' . $in_options['width'] . ',height=' . $in_options['height'] . 
                ',left=50,screenX=50,top=50,screenY=50\'); ' .
                $in_name . '.focus(); return false;';

        $link = $this->link($s_url, $in_text, 
                array('onclick' => $s_js, 'title' => $in_options['title'], 
                    'class' => $in_options['class'], 'caption' => $in_options['caption']));
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
        if ($this->o_registry->getConfigParam('help/show_tooltips', false)) {
            $in_title = is_null($in_title) ? _('Help') : $in_title;
            return $this->link('#', $this->imgTag('help.gif', 'actions', array('height' => 16, 'width' => 16)),
                    array('title' => $in_text, 'caption' => $in_title));
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
     *               width, height, type, style, onclick, id,
     *               onlyUrl, fullPath, title, class, , name, app (if the image is
     *               in a specific application), 'theme' (if we should
     *               check the theme dir for the image also)
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
            elseif (!empty($in_options['theme']) && file_exists($this->themeDir . '/graphics/' . $tmp_img)) {
                $s_imgWebPath = $this->o_registry->rootPathToWebPath($this->themeDir) . '/graphics/' . $tmp_img;
            }
            else {
                $s_imgWebPath = "graphics/$tmp_img";
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
            // The height/width needs to be specified for transparent PNGs to show up in IE
            if ($in_type == 'actions' && !isset($in_options['height'])) {
                $in_options['height'] = $in_options['width'] = 18;
            }
            elseif ($in_type == 'alerts' && !isset($in_options['height'])) {
                $in_options['height'] = $in_options['width'] = 16;
            }

            $s_tag = '<';
            $s_tag .= isset($in_options['type']) && $in_options['type'] == 'input' ? 
                'input type="image"' : 'img';
            $s_tag .= " src=\"$s_imgWebPath\"";
            $s_tag .= isset($in_options['onmouseover']) ? ' onmouseover="' . $in_options['onmouseover'] . '"' : '';
            $s_tag .= isset($in_options['onmousemove']) ? ' onmousemove="' . $in_options['onmousemove'] . '"' : '';
            $s_tag .= isset($in_options['onclick']) ? ' onclick="' . $in_options['onclick'] . '"' : '';
            $s_tag .= isset($in_options['id']) ? " id=\"{$in_options['id']}\"" : '';
            $s_tag .= isset($in_options['title']) ? " title=\"{$in_options['title']}\" alt=\"{$in_options['title']}\"" : '';
            $s_tag .= isset($in_options['class']) ? " class=\"{$in_options['class']}\"" : '';
            $s_tag .= isset($in_options['name']) ? " name=\"{$in_options['name']}\"" : '';
            $s_tag .= isset($in_options['width']) ? " width=\"{$in_options['width']}\"" : '';
            $s_tag .= isset($in_options['height']) ? " height=\"{$in_options['height']}\"" : '';
            $s_tag .= isset($in_options['style']) ? " style=\"{$in_options['style']}\"" : '';
            $s_tag .= ' />';

            return $s_tag;
        }
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

            switch ($in_mode) {
                case FASTFRAME_WARNING_MESSAGE:
                    $s_text = _('Warning');
                break;
                case FASTFRAME_ERROR_MESSAGE:
                    $s_text = _('Error');
                break;
                case FASTFRAME_SUCCESS_MESSAGE:
                    $s_text = _('Success');
                break;
                case FASTFRAME_NORMAL_MESSAGE:
                default:
                    $s_text = _('Alert');
                break;
            }

            $a_message = array($s_message, $in_mode, $s_text);
            if ($in_top) {
                array_unshift($this->messages, $a_message);
            }
            else {
                $this->messages[] = $a_message;
            }
        }
    }

    // }}}
}
?>
