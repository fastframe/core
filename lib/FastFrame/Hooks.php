<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006 The Codejanitor Group                        |
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
// {{{ class FF_Hooks

/**
 * The FF_Hooks:: class runs the hooks (functions which are defined in
 * config/hooks.php).
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_Hooks {
    // {{{ properties

    /**
     * The array of hooks and whether or not they are enabled
     * @var array
     */
    var $hooks = array();

    // }}}
    // {{{ constructor

    function FF_Hooks()
    {
        if (file_exists(FASTFRAME_ROOT . 'config/hooks.php')) {
            include_once FASTFRAME_ROOT . 'config/hooks.php';
            $this->hooks =& $hooks;
        }
    }

    // }}}
    // {{{ singleton()

    /**
     * To be used in place of the contructor to return any open
     * instance.
     *
     * @access public
     * @return object FF_Hooks instance
     */
    function &singleton()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new FF_Hooks();
        }

        return $instance;
    }

    // }}}
    // {{{ run()

    /**
     * Runs all active hooks
     *
     * @param string $in_type The type of hook (i.e. login)
     * @param array $in_args (optional) An array of arguments to pass
     *        the function
     *
     * @access public
     * @return void
     */
    function run($in_type, $in_args = array())
    {
        foreach ($this->hooks as $s_func => $s_enabled) {
            if ($s_enabled && strpos($s_func, '_' . $in_type) === 0 && function_exists($s_func)) {
                call_user_func_array($s_func, $in_args);
            }
        }
    }

    // }}}
}
?>
