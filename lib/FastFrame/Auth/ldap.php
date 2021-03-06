<?php
/** $Id: ldap.php 41 2009-12-18 03:55:43Z dlundgren $ */
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
// | Authors: The Horde Team <http://www.horde.org                        |
// |          Jason Rust <jrust@codejanitor.co>                           |
// +----------------------------------------------------------------------+

// }}}
// {{{ class  FF_AuthSource_ldap

/**
 * An authentication source for ldap servers
 *
 * @version Revision: 1.0 
 * @author  Jon Parise <jon@horde.org>
 * @author  Jason Rust <jrust@codejanitor.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_AuthSource_ldap extends FF_AuthSource {
    // {{{ properties

    /**
     * The ldap handle
     * @var resource
     */
    var $ldap;

    /**
     * The base DN (i.e. "dc=example,dc=com")
     * @var string
     */
    var $basedn;

    /**
     * The username search key (i.e. sAMAccountName for Active Directory)
     * @var string
     */
    var $uid;

    /**
     * The DN used to bind to the ldap server
     * @var string
     */
    var $binddn;

    /**
     * The password used to bind to the LDAP server
     * @var string
     */
    var $password;

    /**
     * The groups that the user may be a part of to login with this resource.
     * @var array
     */
    var $groups;

    /**
     * The capabilities of the auth source so we know what it can do.
     * @var array
     */
    var $capabilities = array(
            'resetpassword' => false,
            'updateusername' => false,
            'transparent' => false);

    // }}}
    // {{{ constructor

    /**
     * Initialize the FF_AuthSource_ldap class
     *
     * Create an instance of the FF_AuthSource class.  
     *
     * @param string $in_name The name of this auth source
     * @param array $in_params Parameters needed for authenticating
     *      against an ldap server.  Required: hostname, basedn, uid
     *      Optional: binddn, password
     *
     * @access public
     * @return object FF_AuthSource_ldap object
     */
    function FF_AuthSource_ldap($in_name, $in_params)
    {
        FF_AuthSource::FF_AuthSource($in_name, $in_params);
        $this->serverName = $in_params['hostname'];
        $this->basedn = $in_params['basedn'];
        $this->uid = $in_params['uid'];
        $this->binddn = isset($in_params['binddn']) ? $in_params['binddn'] : '';
        $this->password = isset($in_params['password']) ? $in_params['password'] : '';
        $this->groups = isset($in_params['groups']) ? $in_params['groups'] : array();
    }

    // }}}
    // {{{ authenticate()

    /**
     * Perform the login procedure.
     *
     * Authenticates the user name and password against an ldap server
     *
     * @param string $in_username The username
     * @param string $in_password The password 
     *
     * @access public
     * @return object A result object
     */
    function authenticate($in_username, $in_password)
    {
        if (!$this->_connect()) {
            return $this->o_result;
        }

        if (($s_dn = $this->_getUserDn($in_username)) === false) {
            return $this->o_result;
        }

        // Attempt to bind to the LDAP server as the user.
        $bind = @ldap_bind($this->ldap, $s_dn, $in_password);
        if ($bind != false) {
            $this->o_result->setSuccess(true);
        }

        // Check the users groups if set        
        if (count($this->groups) && $this->o_result->isSuccess()) {
            $group = $this->_checkUserGroups($in_username, $this->groups);
            $this->o_result->setSuccess($group);
        }

        @ldap_close($this->ldap);
        return $this->o_result;
    }

    // }}}
    // {{{ getUserField()

    /**
     * Gets an ldap field for the specified user.
     *
     * @param string $in_field The ldap field to get
     * @param string $in_username The username for which to get a field
     *
     * @access public
     * @return string The ldap field
     */
    function getUserField($in_field, $in_username)
    {
        $this->_connect();
        $search = ldap_search($this->ldap, $this->basedn,
                              $this->uid . '=' . $in_username,
                               array($in_field));
        if (!$search) {
            return false;
        }

        $a_result = @ldap_get_entries($this->ldap, $search);
        if (is_array($a_result) && (count($a_result) > 1) && isset($a_result[0][$in_field])) {
            return $a_result[0][$in_field][0];
        }
        else {
            return false;
        }
    }

    // }}}
    // {{{ _connect()

    /**
     * Attempts to connect to the ldap server.
     *
     * @access private
     * @return bool True on connect, false otherwise
     */
    function _connect()
    {
        $this->ldap = @ldap_connect($this->serverName);
        if (!$this->ldap) {
            $this->o_result->addMessage(_('Failed to connect to LDAP server.  Please try again later.'));
            return false;
        }

        if (!empty($this->binddn)) {
            $bind = @ldap_bind($this->ldap, $this->binddn, $this->password);
            if (!$bind) {
                $this->o_result->addMessage(_('Could not bind to LDAP server.'));
                return false;
            }
        }

        return true;
    }

    // }}}
    // {{{ _checkUserGroupsByDN
    
    function _checkUserGroups($in_username, $in_groups)
    {
        if (!$this->ldap) {
            $this->_connect();
        }
        $search = @ldap_search($this->ldap, $this->basedn,
                               $this->uid . '=' . $in_username,
                               array('memberOf'));
        
        if ($search === false) {
            return false;
        }
        
        $entry = ldap_first_entry($this->ldap, $search);
        $attrs = ldap_get_attributes($this->ldap, $entry);
        if (!isset($attrs['memberOf']) && $attrs['memberOf']['count'] < 0) {
            return false;
        }
        
        foreach($this->groups as $s_group) {
            if (in_array($s_group, $attrs['memberOf'])) {
                return true;
            }
        }

        return false;
    }

    // }}}
    // {{{ _getUserDn()

    /**
     * Gets the user's dn
     *
     * @param string $in_username The username
     *
     * @access private
     * @return mixed The dn or false on error
     */
    function _getUserDn($in_username)
    {
        // Search for the user's full DN
        $search = @ldap_search($this->ldap, $this->basedn,
                               $this->uid . '=' . $in_username,
                               array($this->uid));
        if ($search === false) {
            // Search failed
            return false;
        }

        $entry = ldap_first_entry($this->ldap, $search);
        if ($entry === false) {
            // Couldn't find username
            return false;
        }

        return ldap_get_dn($this->ldap, $entry);
    }

    // }}}
}
?>
