<?php
// {{{ constants
// }}}
// {{{ includes

require_once("DB.php");

// }}}
// {{{ class  AuthSource_sql

/**
 * An authentication source for sql servers
 *
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
     * The hostname of the server to connect to
     * @var string $databaseHost
     */
    var $databaseHost;

    /**
     * The type of database we will connect to
     * @var string $databaseType
     */
    var $databaseType;


    /**
     * The name of database we will connect to
     * @var string $databaseName
     */
    var $databaseName;

    /**
     * Username to connect to the database
     * @var string $databaseUser
     */
    var $databaseUser;

    /**
     * Password to connect to the database
     * @var string $databasePassword
     */
    var $databasePassword;

    /**
     * The table we are storing usernames and passwords in
     * @var string $databaseTable
     */
    var $databaseTable;

    /**
     * The field usernames are stored in
     * @var string $fieldUser
     */
    var $fieldUser;

    /**
     * The field passwords are stored in
     * @var string $fieldPassword
     */
    var $fieldPassword;

    /**
     * The type of encoding used for the password. It can be either
     * md5 or plain
     * @var string $passwordType
     */
    var $passwordType;


    // }}}
    // {{{ constructor

    /**
     * Initialize the AuthSource_imap class
     *
     * Create an instance of the AuthSource class.  
     *
     * @access public
     * @return AuthSource_imap object
     */
    function AuthSource_sql($name, $params)
    {
        $this->sourceName=$name;
        $this->databaseHost=$params['hostname'];
        $this->databaseType=$params['serverType'];
        $this->databaseName=$params['database'];
        $this->databaseUser=$params['username'];
        $this->databasePassword=$params['password'];
        $this->databaseTable=$params['table'];
        $this->fieldUser=$params['userField'];
        $this->fieldPassword=$params['passField'];
        $this->passwordType=$params['passType'];

    }

    // }}}
    // {{{ authenticate()

    /**
     * Perform the login procedure.
     *
     * Authenticates the user name and password against an sql server
     *
     * @access public
     * @return boolean determines if login was successfull
     */
    function authenticate($username, $password)
    {
        // Connect to the db
        $dsn = "{$this->databaseType}://{$this->databaseUser}:{$this->databasePassword}@{$this->databaseHost}/{$this->databaseName}";
        $db = DB::connect($dsn);

        // hash/encrypt the password if needed
        switch ($this->passwordType) {
            case "md5":
                $authPass=md5($password);
            break;
            case "plain":
            default:
                $authPass=$password;
                break;
        }

        // Lookup the user
        $sql="SELECT COUNT(*) from " . $db->quote($this->databaseTable) . " WHERE " . $this->fieldUser . "=" . $db->quote($username) . " AND " .
            $this->fieldPassword . "=" . $db->quote($authPassword);
        $count=$db->getOne($sql);

        $db->disconnect();

        if ($count)
            return true;
        else
            return false;
    }

    // }}}
}
?>
