<?php
/** $Id: imap.php,v 1.3 2003/02/08 00:10:55 jrust Exp $ */
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
// {{{ class  AuthSource_imap

/**
 * An authentication source for imap servers
 *
 * @version Revision: 1.0 
 * @author  Greg Gilbert <greg@treke.net>
 * @access  public
 * @package FastFrame
 */

// }}}
class AuthSource_imap extends AuthSource {
    // {{{ properties

    /**
     * The hostname of the server to connect to
     * @type string
     */
    var $serverName;

    /**
     * The port on server to connect to
     * @type string
     */
    var $serverPort;

    // }}}
    // {{{ constructor

    /**
     * Initialize the AuthSource_imap class
     *
     * Create an instance of the AuthSource class.  
     *
     * @param string $in_name The name of this auth source
     * @param array $in_params Parameters needed for authenticating against an imap server.
     *              These include hostname, port
     *
     * @access public
     * @return object AuthSource_imap object
     */
    function AuthSource_imap($in_name, $in_params)
    {
        $this->_sourceName = $in_name;
        $this->serverName = $in_params['hostname'];
        $this->serverPort = isset($in_params['port']) ? $in_params['port'] : 143;

    }

    // }}}
    // {{{ authenticate()

    /**
     * Perform the login procedure.
     *
     * Authenticates the user name and password against an imap server
     *
     * @param string $in_username The username
     * @param string $in_password The password 
     *
     * @access public
     * @return boolean determines if login was successfull
     */
    function authenticate($in_username, $in_password)
    {
        $s_imapString = '{' . $this->serverName . ':' . $this->serverPort .'}';
        if ( $result = @imap_open($s_imapString, $in_username, $in_password))	{
            @imap_close($result);
            return true;
        }
        else {
            return false;
        }
    }

    // }}}
}
?>
