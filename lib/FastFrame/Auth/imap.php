<?php

// {{{ class  AuthSource_imap

/**
 * An authentication source for imap servers
 *
 *
 * @version Revision: 1.0 
 * @author  Greg Gilbert <ggilbert@brooks.edu
 * @access  public
 * @package FastFrame
 */

// }}}
class AuthSource_imap extends AuthSource {

    // {{{ properties
    /**
     * The hostname of the server to connect to
     * @var string $serverName
     */
    var $serverName;

    /**
     * The port on server to connect to
     * @var string $serverPort
     */
    var $serverPort;

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
    function AuthSource_imap($name, $params)
    {
        $this->sourceName=$name;
        $this->serverName=$params['hostname'];
        $this->serverPort= isset($params['port']) ? $params['port'] : 143;

    }

    // }}}
    // {{{ authenticate()

    /**
     * Perform the login procedure.
     *
     * Authenticates the user name and password against an imap server
     *
     * @access public
     * @return boolean determines if login was successfull
     */
    function authenticate($username, $password)
    {
        $s_imapString='{' . $this->serverName . ':' . $this->serverPort .'}';
        if ( $result = @imap_open($s_imapString, $username, $password))	{
            @imap_close($result);
            return true;
        }
        else
            return false;
    }

    // }}}
}
?>
