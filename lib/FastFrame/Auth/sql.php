<?php
/** $Id: sql.php,v 1.6 2003/02/06 18:53:50 jrust Exp $ */
// {{{ requires

require_once 'DB/QueryTool.php';

// }}}
// {{{ class  AuthSource_sql

/**
 * An authentication source for sql servers
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lesser.html.
 *
 * @version Revision: 1.0 
 * @author  Greg Gilbert <ggilbert@brooks.edu
 * @access  public
 * @package FastFrame
 */

// }}}
class AuthSource_sql extends AuthSource {
    // {{{ properties

    /**
     * The basename of the DataObject class
     * @type string
     */
    var $dataBasename;

    /**
     * The type of encoding used for the password. It can be either
     * md5 or plain
     * @type string
     */
    var $passwordType;

    /**
     * The table for the auth class
     * @type string
     */
    var $table;

    /**
     * The field for the username 
     * @type string
     */
    var $userField;

    /**
     * The field for the password 
     * @type string
     */
    var $passField;

    // }}}
    // {{{ constructor

    /**
     * Initialize the AuthSource_imap class
     *
     * Create an instance of the AuthSource class.  
     *
     * @param string $in_name The name of this auth source
     * @param array $in_params Parameters needed for authenticating against the SQL server.
     *              These can include hostname, table
     *
     * @access public
     * @return object AuthSource_sql object
     */
    function AuthSource_sql($in_name, $in_params)
    {
        $this->sourceName = $in_name;
        $this->dataBasename = $in_params['basename'];
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
     * @return boolean True if login was successfull
     */
    function authenticate($in_username, $in_password)
    {
        $o_registry =& FastFrame_Registry::singleton();
        // set up database
        $o_data =& new DB_QueryTool($o_registry->getDataDsn());
        $o_data->setTable($this->table);

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

        $o_data->reset();
        $o_data->addWhere($this->userField . '=' . $o_data->_db->quote($in_username));
        $o_data->addWhere($this->passField . '=' . $o_data->_db->quote($s_encryptPass));
        $s_result = $o_data->getCount();

        if ($s_result) {
            return true;
        }
        else {
            return false;
        }
    }

    // }}}
}
?>
