<?php
/** $Id: AuthSource.php,v 1.4 2003/02/08 00:10:55 jrust Exp $ */
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
// | Authors: Greg Gilbert <greg@treke.net>                               |
// +----------------------------------------------------------------------+

// }}}
// {{{ class  AuthSource

/**
 * Abstract class describing a source to authenticate against
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lesser.html.
 *
 * @version Revision: 1.0 
 * @author  Greg Gilbert <greg@treke.net>
 * @access  public
 * @package FastFrame
 */

// }}}
class AuthSource {
    // {{{ properties

    /**
     * A unique name to identify an authenticated source
     * @type string
     */
    var $_sourceName;

    // }}}
    // {{{ constructor
    
    /**
     * Initialize the AuthSource class
     *
     * Create an instance of the AuthSource class.  
     *
     * @param string $in_name The name of this auth source
     * @param array $in_params Additional parameters needed for authenticating
     *
     * @access public
     * @return AuthSource object
     */
    function AuthSource($in_name, $in_params)
    {
        $this->_sourceName = $in_name;
    }

    // }}}
    // {{{ create()

    /**
     * Creates an instance of a specific source
     *
     * When requesting an authentication source, we do not need to know what type of 
     * object we want, just a source that can authenticate against a preset target. 
     * If we don't have a source that can authenticate, a source that always fails is 
     * returned
     *
     * @access public
     * @return object Subclass of AuthSource for the specified type, or AuthSource if none is found 
     */
    function &create($in_type, $in_name, $in_params)
    {
        $s_authFile = dirname(__FILE__) . '/' . $in_type . '.php';
        if (empty($in_type)) {
            return FastFrame::fatal('No authentication type defined.', __FILE__, __LINE__);
        }
        elseif (!@file_exists($s_authFile)) {
            return FastFrame::fatal("$in_type is an invalid authentication type.", __FILE__, __LINE__);
        }
        else {
            include_once $s_authFile;
        }

        $s_authClass = 'AuthSource_' . $in_type;
				
        if (class_exists($s_authClass)) {
            $o_auth =& new $s_authClass($in_name, $in_params);
        }
        else {
            $o_auth =& new AuthSource($in_name, $in_params);
        }

        return $o_auth; 
    }

    // }}}
    // {{{ authenticate()

    /**
     * Perform the login procedure.
     *
     * Authenticates the user name and password. This implementation will always
     * fail. 
     *
     * @access public
     * @return boolean determines if login was successfull
     */
    function authenticate($username, $password)
    {
        return false;
    }

    // }}}
    // {{{ getName()

    /**
     * Return the name of this object
     *
     * Returns the name given to this object.
     *
     * @access public
     * @return string name given to this object by the programmer
     */
    function getName()
    {
        return $this->_sourceName;
    }

    // }}}
}
?>
