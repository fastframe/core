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
// | Authors: Jason Rust <jrust@codejanitor.com>                          |
// |          The Horde Team http://horde.org                             |
// +----------------------------------------------------------------------+

// }}}
// {{{ class  FF_AuthSource_ipbasic

/**
 * An authentication source that transparently logs users into FastFrame
 * using a defined username (default: 'guest') to a set of apps
 * (default: all) based on whether or not they are in a block of IPs.
 *
 * @version Revision: 1.0 
 * @author  Jason Rust <jrust@codejanitor.com>
 * @author  The Horde Team http://horde.org
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_AuthSource_ipbasic extends FF_AuthSource {
    // {{{ properties

    /**
     * The array of CIDR maskes which are allowed access
     * @var array
     */
    var $blocks = array();

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
     * Initialize the FF_AuthSource_ipbasic class
     *
     * @param string $in_name The name of this auth source
     * @param array $in_params Parameters include 'blocks' (an array of
     *              CIDR masks. e.g. 192.168.0.0/16) 'username', 'apps'
     *              (an array of valid applications to auto-login to or
     *              '*' for all.
     *
     * @access public
     * @return object FF_AuthSource_ipbasic object
     */
    function FF_AuthSource_ipbasic($in_name, $in_params)
    {
        FF_AuthSource::FF_AuthSource($in_name, $in_params);
        $this->blocks = (array) $in_params['blocks'];
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
        if (!in_array('*', $this->allowedApps) && !in_array($o_registry->getCurrentApp(), $this->allowedApps)) {
            return false;
        }

        if (!isset($_SERVER['REMOTE_ADDR'])) {
            trigger_error(_('IP Address not available.'), E_USER_ERROR);
            return false;
        }

        foreach ($this->blocks as $s_cidr) {
            if ($this->_addressWithinCIDR($_SERVER['REMOTE_ADDR'], $s_cidr)) {
                FF_Auth::setAuth($this->username, array('transparent' => 1, 'apps' => $this->allowedApps));
                return true;
            }
        }

        return false;
    }

    // }}}
    // {{{ _addressWithinCIDR()

    /**
     * Determine if an IP address is within a CIDR block.
     *
     * @param string $in_address The IP address to check.
     * @param string $in_cidr    The block (e.g. 192.168.0.0/16) to test against.
     *
     * @access private
     * @return bool Whether or not the address matches the mask.
     */
    function _addressWithinCIDR($in_address, $in_cidr)
    {
        $in_address = ip2long($in_address);
        list($quad, $bits) = explode('/', $in_cidr);
        $bits = intval($bits);
        $quad = ip2long($quad);

        return (($in_address >> (32 - $bits)) == ($quad >> (32 - $bits)));
    }

    // }}}
}
?>
