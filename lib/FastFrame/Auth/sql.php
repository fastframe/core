<?php
/** $Id: sql.php,v 1.3 2003/01/22 02:03:28 jrust Exp $ */
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
        // set up database
        $o_registry =& FastFrame_Registry::singleton();
        require_once $o_registry->getConfigParam('data/class_location', null, array('app' => FASTFRAME_DEFAULT_APP)) . '/' . $this->dataBasename . '.php';
        $o_registry->initDataObject($o_registry->getDataObjectOptions(array(), array('app' => FASTFRAME_DEFAULT_APP)));
        $s_className = DB_DataObject::staticAutoloadTable($this->dataBasename);
        if (!$s_className) {
            return FastFrame::fatal("No $this->dataBasename table exists.", __FILE__, __LINE__);
        }

        $o_data = new $s_className;
        $o_db =& $o_registry->getDataConnection($o_data);

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

        $o_data->whereAdd($this->userField . '=' . $o_db->quote($in_username));
        $o_data->whereAdd($this->passField . '=' . $o_db->quote($s_encryptPass));
        $s_result = $o_data->count();

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
