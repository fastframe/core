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
// {{{ class  FF_AuthSource_guest

/**
 * An authentication source that transparently logs users into FastFrame
 * using a defined username (default: 'guest') to a set of apps
 * (default: all).
 *
 * @version Revision: 1.0 
 * @author  Jason Rust <jrust@codejanitor.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_AuthSource_guest extends FF_AuthSource {
    // {{{ properties

    /**
     * The array of apps that are valid for this auth source
     * @var array
     */
    var $allowedApps = array('*');

    /**
     * The username we register as being logged in
     * @var string
     */
    var $username = 'guest';

    /**
     * The capabilities of the auth source so we know what it can do.
     * @var array
     */
    var $capabilities = array(
            'resetpassword' => false,
            'updateusername' => false,
            'transparent' => true);

    // }}}
    // {{{ constructor

    /**
     * Initialize the FF_AuthSource_guest class
     *
     * @param string $in_name The name of this auth source
     * @param array $in_params Parameters include 'username', 'apps' (an
     *              array of valid applications to auto-login to or '*'
     *              for all.
     *
     * @access public
     * @return object FF_AuthSource_guest object
     */
    function FF_AuthSource_guest($in_name, $in_params)
    {
        FF_AuthSource::FF_AuthSource($in_name, $in_params);
        $this->allowedApps = (array) @$in_params['apps'];
        $this->username = isset($in_params['username']) ? $in_params['username'] : $this->username;
    }

    // }}}
    // {{{ transparent()

    /**
     * Perform a transparent login procedure.
     *
     * @access public
     * @return bool determines if login was successfull
     */
    function transparent()
    {
        $o_registry =& FF_Registry::singleton();
        if (in_array('*', $this->allowedApps) || in_array($o_registry->getCurrentApp(), $this->allowedApps)) {
            FF_Auth::setAuth($this->username, array('transparent' => 1, 'apps' => $this->allowedApps));
            FF_Auth::setCredential('userId', 0);
            return true;
        }
        else {
            return false;
        }
    }

    // }}}
}
?>
