<?php
/** $Id$ */
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
// {{{ requires

require_once 'DB.php'; 

// }}}
// {{{ class  FF_AuthSource_sql

/**
 * An authentication source for a simple SQL database that has a username and password field
 * to authenticate against.
 *
 * @version Revision: 1.0 
 * @author  Greg Gilbert <greg@treke.net>
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_AuthSource_sql extends FF_AuthSource {
    // {{{ properties

    /**
     * The type of encoding used for the password. It can be either
     * md5 or plain
     * @var string
     */
    var $passwordType;

    /**
     * The table for the auth class
     * @var string
     */
    var $table;

    /**
     * The field for the username 
     * @var string
     */
    var $userField;

    /**
     * The field for the password 
     * @var string
     */
    var $passField;

    // }}}
    // {{{ constructor

    /**
     * Initialize the FF_AuthSource_sql class
     *
     * @param string $in_name The name of this auth source
     * @param array $in_params Parameters needed for authenticating against the SQL server.
     *              These should include encryption, table, userField, and passField
     *
     * @access public
     * @return object FF_AuthSource_sql object
     */
    function FF_AuthSource_sql($in_name, $in_params)
    {
        FF_AuthSource::FF_AuthSource($in_name, $in_params);
        $this->encryptionType = $in_params['encryption'];
        $this->table = $in_params['table'];
        $this->userField = $in_params['userField'];
        $this->passField = $in_params['passField'];
    }

    // }}}
    // {{{ authenticate()

    /**
     * Perform the login procedure.
     *
     * Authenticates the user name and password against an sql server
     *
     * @param string $in_username The username to be authenticated.
     * @param string $in_password The corresponding password.
     *
     * @access public
     * @return boolean True if login was successful
     */
    function authenticate($in_username, $in_password)
    {
        $o_registry =& FF_Registry::singleton();
        $this->serverName = $o_registry->getConfigParam('data/host');
        $s_dsn = $o_registry->getConfigParam('data/type') . '://' .
                 $o_registry->getConfigParam('data/username') . ':' .
                 $o_registry->getConfigParam('data/password') . '@' .
                 $o_registry->getConfigParam('data/host') . '/' .
                 $o_registry->getConfigParam('data/database');
        // set up database
        $o_data =& DB::connect($s_dsn);
        if (DB::isError($o_data)) {
            FastFrame::fatal($result, __FILE__, __LINE__);
        }

        // hash/encrypt the password if needed
        switch ($this->encryptionType) {
            case 'md5':
                $s_encryptPass = md5($in_password);
            break;
            case 'plain':
            default:
                $s_encryptPass = $in_password;
            break;
        }

        $s_query = sprintf('SELECT COUNT(*) FROM %s WHERE %s=%s AND %s=%s',
                              $this->table,
                              $this->userField,
                              $o_data->quote($in_username),
                              $this->passField,
                              $o_data->quote($s_encryptPass)
                          );
        $s_result = $o_data->getOne($s_query);
        if ($s_result == 1) {
            return true;
        }
        else {
            return false;
        }
    }

    // }}}
}
?>
