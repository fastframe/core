<?php
/** $Id: Output.php 931 2003-11-19 18:51:54Z jrust $ */
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

require_once dirname(__FILE__) . '/../Smarty/Smarty.class.php';

// }}}
// {{{ class FF_Smarty

/**
 * This is a simple wrapper class for smarty that sets up the FastFrame
 * default variables upon instantiation.
 *
 * @access public
 * @package FastFrame
 */

// }}}
class FF_Smarty extends Smarty {
    // {{{ properties
    
    /**
     * The widget
     * @var string
     */
    var $widget;

    /**
     * The theme
     * @var string
     */
    var $theme;

    // }}}
    // {{{ constructor

    /**
     * Initialize class variables for the specified widget.
     *
     * @param string $in_widget The widget name or template file name
     * @param string $in_theme (optional) What theme to use
     *
     * @access public
     * @return void
     */
    function FF_Smarty($in_widget, $in_theme = null)
    {
        $this->Smarty();
        $this->widget = $in_widget;
        $this->theme = $in_theme;
    }

    // }}}
    // {{{ display()

    /**
     * Overrides the display method so that we can determine the
     * template_dir and compile_dir based on the theme and the widget
     * file. 
     *
     * @access public
     * @return void
     */
    function display()
    {
        $this->fetch(true);
    }

    // }}}
    // {{{ fetch()

    /**
     * Overrides the fetch method so that we can determine the
     * template_dir and compile_dir based on the theme and the widget
     * file. 
     *
     * @param bool $in_display (optional) Display the template instead
     *        of returning?
     *
     * @access public
     * @return string The rendered widget
     */
    function fetch($in_display = false)
    {
        $this->_determinePaths();
        if ($in_display) {
            parent::fetch($this->widget, null, null, true);
        }
        else {
            return parent::fetch($this->widget);
        }
    }

    // }}}
    // {{{ is_cached()

    /**
     * Overrides is_cached so we can pass the widget file name.
     *
     * @param string $cache_id
     * @param string $compile_id
     *
     * @return bool Whether or not the file is cached.
     */
    function is_cached($cache_id = null, $compile_id = null)
    {
        $this->_determinePaths();
        return parent::is_cached($this->widget, $cache_id, $compile_id);
    }

    // }}}
    // {{{ clear_cache()

    /**
     * Overrides clear_cache so we can pass the widget file name.
     *
     * @param string $cache_id name of cache_id
     * @param string $compile_id name of compile_id
     * @param string $exp_time expiration time
     *
     * @return bool
     */
    function clear_cache($cache_id = null, $compile_id = null, $exp_time = null)
    {
        $this->_determinePaths();
        return parent::clear_cache($this->widget, $cache_id, $compile_id, $exp_time);
    }

    // }}}
    // {{{ _determinePaths()

    /**
     * Determines the compile_dir and template_dir paths based on the
     * widget and the theme.
     * 
     * @access private
     * @return void
     */
    function _determinePaths()
    {
        if (!isset($b_done)) {
            $b_done = true;
        }
        else {
            return;
        }

        $o_output =& FF_Output::singleton();
        $this->theme = is_null($this->theme) ? $o_output->theme : $this->theme;
        if (substr($this->widget, -4) == '.tpl') {
            $pth_tpl = dirname($this->widget); 
            $pth_tplCompile = $o_output->o_registry->getRootFile('templates/' . $this->theme, 'cache');
        }
        else {
            $this->widget .= '.tpl';
            $pth_tpl = $o_output->themeDir;
            // If it doesn't exist in the theme directory, then move up to
            // the widgets directory
            if (!file_exists($pth_tpl . '/' . $this->widget)) {
                $pth_tpl = $o_output->o_registry->getRootFile('widgets', 'themes');
                $this->theme = 'widgets';
            }

            $pth_tplCompile = $o_output->o_registry->getRootFile('templates/' . $this->theme, 'cache');
        }

        if (!is_dir($pth_tplCompile)) {
            require_once 'System.php';
            if (!@System::mkdir("-p $pth_tplCompile"))  {
                trigger_error('Could not write to the FastFrame cache directory.', E_USER_ERROR);
            }
        }

        $this->template_dir = $pth_tpl; 
        $this->compile_dir = $pth_tplCompile;
        $this->cache_dir = $pth_tplCompile;
    }

    // }}}
}
?>
