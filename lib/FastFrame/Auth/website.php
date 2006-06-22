<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006 The Codejanitor Group                        |
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
// | Authors: Jason Rust <jrust@codejanitor.co>                           |
// +----------------------------------------------------------------------+

// }}}
// {{{ requires

require_once 'HTTP/Request.php';

// }}}
// {{{ class  FF_AuthSource_website

/**
 * An authentication source for testing against an external website
 *
 * @version Revision: 1.0 
 * @author  Jason Rust <jrust@codejanitor.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_AuthSource_website extends FF_AuthSource {
    // {{{ properties

    /**
     * The http request object
     * @var resource
     */
    var $http;

    /**
     * The url. %username% and %password% are replaced with the variables
     * @var string
     */
    var $url = 'http://example.com/?u=%username%&p=%password%';

    /**
     * The string to check for in the case of a successful login
     * @var string
     */
    var $matchString;

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
     * Initialize the FF_AuthSource_website class
     *
     * Create an instance of the FF_AuthSource class.  
     *
     * @param string $in_name The name of this auth source
     * @param array $in_params Parameters needed for authenticating
     *      against an ldap server.  Required: url, matchString
     *
     * @access public
     * @return object FF_AuthSource_website object
     */
    function FF_AuthSource_website($in_name, $in_params)
    {
        FF_AuthSource::FF_AuthSource($in_name, $in_params);
        $this->url = $in_params['url'];
        $this->matchString = $in_params['matchString'];
    }

    // }}}
    // {{{ authenticate()

    /**
     * Perform the login procedure.
     *
     * Opens up a session with the website and checks to see if it
     * returns a page with the matchString
     *
     * @param string $in_username The username
     * @param string $in_password The password
     *
     * @access public
     * @return object A result object
     */
    function authenticate($in_username, $in_password)
    {
        $a_options['method'] = HTTP_REQUEST_METHOD_GET;
        $a_options['timeout'] = 5;
        $a_options['allowRedirects'] = true;
        $this->url = str_replace('%username%', urlencode($in_username), $this->url);
        $this->url = str_replace('%password%', urlencode($in_password), $this->url);
        $this->http = &new HTTP_Request($this->url, $a_options);

        $result = $this->http->sendRequest();
        if (is_a($result, 'PEAR_Error')) {
            $this->o_result->addMessage(_('Error loading authentication website.  Please try again later.'));
            return $this->o_result;
        } 

        $result = $this->http->getResponseBody();
        if (strpos($result, $this->matchString) !== false) {
            $this->o_result->setSuccess(true);
        }

        return $this->o_result;
    }

    // }}}
}
?>
