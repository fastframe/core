<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2004 The Codejanitor Group                        |
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
// +----------------------------------------------------------------------+

// }}}
// {{{ class FF_SystemTest

/**
 * The FF_SystemTest:: class allows for easy testing of configuration
 * variables, system settings, and installed libraries to aid in
 * determining if FastFrame is installed correctly. 
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_SystemTest {
    // {{{ properties

    /**
     * The array of available tests.  The key is the name used to load
     * the test.  Format of each test array is:
     *  'method' => Name of method to call to perform test
     *  'name' => The display name of the test
     *  'errorMsg' => The message to give on error
     *  'successMsg' => The message to give on success
     *
     * @var array
     */
    var $tests = array();

    /**
     * The current test
     * @type string 
     */
    var $currentTest = 'phpversion';

    // }}}
    // {{{ constructor()

    /**
     * Initialized the class
     *
     * @access public
     * @return void
     */
    function FF_SystemTest()
    {
        error_reporting(E_ALL);
        $this->_loadTests();
    }

    // }}}
    // {{{ load()

    /**
     * Loads in a new test array.
     *
     * @param string $in_test The name of the test
     *
     * @access public
     * @return bool True if the test exists and is loaded successfully,
     *         false otherwise
     */
    function load($in_test)
    {
        if (isset($this->tests[$in_test])) {
            $this->currentTest = $in_test;
            return true;
        }
        else {
            return false;
        }
    }

    // }}}
    // {{{ run()

    /**
     * Runs the test and returns appropriate output.
     *
     * @access public
     * @return string The html describing the result of the test
     */
    function run()
    {
        $s_return = '<div class="test"><span class="testName">--> ';
        $s_return .= $this->tests[$this->currentTest]['name'];
        $s_return .= ':</span> ';
        $s_method = $this->tests[$this->currentTest]['method'];
        if ($this->$s_method()) {
            $s_return .= '<span class="success">Success</span> ';
            $s_return .= '<span class="message">(';
            $s_return .= $this->tests[$this->currentTest]['successMsg'];
            $s_return .= ')</span>';
        }
        else {
            $s_return .= '<span class="failed">Failed</span> ';
            $s_return .= '<span class="message">(';
            $s_return .= $this->tests[$this->currentTest]['errorMsg'];
            $s_return .= ')</span>';
        }

        $s_return .= '</div>';
        return $s_return;
    }

    // }}}
    // {{{ _loadTests()

    /**
     * Loads in the available tests
     *
     * @access private
     * @return void
     */
    function _loadTests()
    {
        $this->tests = array(
            'phpversion' => array(
                'method' => '_checkPHPVersion',
                'name' => 'PHP Version',
                'errorMsg' => sprintf(_('Your PHP Version is %s which is unsupported.'), phpversion()),
                'successMsg' => sprintf(_('You are running PHP %s which is supported.'), phpversion())),
            'gettext' => array(
                'method' => '_checkGettext',
                'name' => 'Gettext Support',
                'errorMsg' => _('You do not have gettext support compiled into PHP.'),
                'successMsg' => _('You have gettext support.')),
            'pear' => array(
                'method' => '_checkPEAR',
                'name' => 'PEAR',
                'errorMsg' => sprintf(_('You do not have PEAR installed.  Include path that PEAR searches: %s'), ini_get('include_path')),
                'successMsg' => sprintf(_('You have PEAR.  Include path that PEAR searches: %s'), ini_get('include_path'))),
            'pear_db' => array(
                'method' => '_checkPEARDB',
                'name' => 'PEAR DB Module',
                'errorMsg' => _('You do not have PEAR DB 1.6.0RC5 or greater installed.'),
                'successMsg' => _('You have the PEAR DB module.')),
            'pear_db_ldap' => array(
                'method' => '_checkPEARDBLdap',
                'name' => 'PEAR DB_ldap Module',
                'errorMsg' => _('You do not have PEAR DB_ldap installed.  It is required if you want to have an LDAP backend to the phonelist application.'),
                'successMsg' => _('You have the PEAR DB_ldap module.')),
            'pear_file' => array(
                'method' => '_checkPEARFile',
                'name' => 'PEAR File Module',
                'errorMsg' => _('You do not have the PEAR File module installed.'),
                'successMsg' => _('You have the PEAR File module.')),
            'pear_html_quickform' => array(
                'method' => '_checkPEARHTMLQuickForm',
                'name' => 'PEAR HTML_QuickForm',
                'errorMsg' => _('You do not have HTML_QuickForm 3.1.1 or greater.'),
                'successMsg' => _('You have the PEAR HTML_QuickForm module.')),
            'pear_net_useragent_detect' => array(
                'method' => '_checkPEARNetUserAgentDetect',
                'name' => 'PEAR Net_UserAgent_Detect',
                'errorMsg' => _('You do not have the PEAR Net_UserAgent_Detect module installed.'),
                'successMsg' => _('You have the PEAR Net_UserAgent_Detect module.')),
            'pear_validate' => array(
                'method' => '_checkPEARValidate',
                'name' => 'PEAR Validate',
                'errorMsg' => _('You do not have the PEAR Validate module installed.  It is required for the profile, alum_website, and checkout applications.'),
                'successMsg' => _('You have the PEAR Validate module.')),
            'pear_db_nestedset' => array(
                'method' => '_checkPEARDBNestedSet',
                'name' => 'PEAR DB_NestedSet',
                'errorMsg' => _('You do not have the PEAR DB_NestedSet module installed.  It is required for the checkout application.'),
                'successMsg' => _('You have the PEAR DB_NestedSet module.')),
            'pear_mail' => array(
                'method' => '_checkPEARMail',
                'name' => 'PEAR Mail',
                'errorMsg' => _('You do not have the PEAR Mail module installed.'),
                'successMsg' => _('You have the PEAR Mail module.')),
            'pear_mail_mime' => array(
                'method' => '_checkPEARMailMime',
                'name' => 'PEAR Mail_mime',
                'errorMsg' => _('You do not have the PEAR Mail_mime module installed.  It is required for the massmail and mayday applications.'),
                'successMsg' => _('You have the PEAR Mail_mime module.')),
            'pear_html_bbcodeparser' => array(
                'method' => '_checkPEARHTMLBBCodeParser',
                'name' => 'PEAR HTML_BBCodeParser',
                'errorMsg' => _('You do not have the PEAR HTML_BBCodeParser module installed.  It is required for the portal and alum_website application.'),
                'successMsg' => _('You have the PEAR HTML_BBCodeParser module.')),
            'pear_http_request' => array(
                'method' => '_checkPEARHTTPRequest',
                'name' => 'PEAR HTTP_Request',
                'errorMsg' => _('You do not have the PEAR HTTP_Request module installed.  It is required for using the website auth mechanism.'),
                'successMsg' => _('You have the PEAR HTTP_Request module.')),
            'cachedir' => array(
                'method' => '_checkCacheDir',
                'name' => 'Writable Cache Dir',
                'errorMsg' => _('The FastFrame/cache directory is not writable.'),
                'successMsg' => _('The cache directory is writable.')),
            'conffile' => array(
                'method' => '_checkConfFile',
                'name' => 'Conf File',
                'errorMsg' => _('The config/conf.php file is not present.'),
                'successMsg' => _('The conf.php file is present.')),
            'appsfile' => array(
                'method' => '_checkAppsFile',
                'name' => 'Apps File',
                'errorMsg' => _('The config/apps.php file is not present.'),
                'successMsg' => _('The apps.php file is present.')));
    }

    // }}}
    // {{{ _checkPEAR()

    /**
     * Tests to see if PEAR is loaded.
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkPEAR()
    {
        @include_once 'PEAR.php';
        return class_exists('PEAR');
    }

    // }}}
    // {{{ _checkPEARDB()

    /**
     * Tests to see if the PEAR DB module is loaded.
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkPEARDB()
    {
        @include_once 'DB/common.php';
        return is_callable(array('DB_common', 'quoteIdentifier'));
    }

    // }}}
    // {{{ _checkPEARDBLdap()

    /**
     * Tests to see if the PEAR DB_ldap module is loaded.
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkPEARDBLdap()
    {
        @include_once 'DB.php';
        @include_once 'DB/ldap.php';
        return class_exists('DB_ldap');
    }

    // }}}
    // {{{ _checkPEARFile()

    /**
     * Tests to see if the PEAR File module is loaded.
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkPEARFile()
    {
        @include_once 'File.php';
        return class_exists('File');
    }

    // }}}
    // {{{ _checkPEARHTMLQuickForm()

    /**
     * Tests to see if the PEAR HTML_QuickForm module is loaded.
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkPEARHTMLQuickForm()
    {
        @include_once 'HTML/QuickForm/Renderer/QuickHtml.php';
        return class_exists('HTML_QuickForm_Renderer_QuickHtml');
    }

    // }}}
    // {{{ _checkPEARNetUserAgentDetect()

    /**
     * Tests to see if the PEAR Net_UserAgent_Detect module is loaded.
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkPEARNetUserAgentDetect()
    {
        @include_once 'Net/UserAgent/Detect.php';
        return class_exists('Net_UserAgent_Detect');
    }

    // }}}
    // {{{ _checkPEARValidate()

    /**
     * Tests to see if the PEAR Validate module is loaded.
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkPEARValidate()
    {
        @include_once 'Validate.php';
        return class_exists('Validate');
    }

    // }}}
    // {{{ _checkPEARDBNestedSet()

    /**
     * Tests to see if the PEAR DB_NestedSet module is loaded.
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkPEARDBNestedSet()
    {
        @include_once 'DB/NestedSet.php';
        return class_exists('DB_NestedSet');
    }

    // }}}
    // {{{ _checkPEARMail()

    /**
     * Tests to see if the PEAR Mail module is loaded.
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkPEARMail()
    {
        @include_once 'Mail.php';
        return class_exists('Mail');
    }

    // }}}
    // {{{ _checkPEARMailMime()

    /**
     * Tests to see if the PEAR Mail_mime module is loaded.
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkPEARMailMime()
    {
        @include_once 'Mail/mime.php';
        return class_exists('Mail_mime');
    }

    // }}}
    // {{{ _checkPEARHTMLBBCodeParser()

    /**
     * Tests to see if the PEAR HTML_BBCodeParser module is loaded.
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkPEARHTMLBBCodeParser()
    {
        @include_once 'HTML/BBCodeParser.php';
        return class_exists('HTML_BBCodeParser');
    }

    // }}}
    // {{{ _checkPEARHTTPRequest()

    /**
     * Tests to see if the PEAR HTTP_Request module is loaded.
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkPEARHTTPRequest()
    {
        @include_once 'HTTP/Request.php';
        return class_exists('HTTP_Request');
    }

    // }}}
    // {{{ _checkPEARLog()

    /**
     * Tests to see if the PEAR Log module is loaded.
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkPEARLog()
    {
        @include_once 'Log.php';
        return class_exists('Log');
    }

    // }}}
    // {{{ _checkCacheDir()

    /**
     * Tests to see if the cache directory is writable 
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkCacheDir()
    {
        if (@touch(FASTFRAME_ROOT . 'cache/foo')) {
            unlink(FASTFRAME_ROOT . 'cache/foo');
            return true;
        }
        else {
            return false;
        }
    }

    // }}}
    // {{{ _checkConfFile()

    /**
     * Tests to see if the conf file exists
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkConfFile()
    {
        return file_exists(FASTFRAME_ROOT . 'config/conf.php');
    }

    // }}}
    // {{{ _checkAppsFile()

    /**
     * Tests to see if the apps file exists
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkAppsFile()
    {
        return file_exists(FASTFRAME_ROOT . 'config/apps.php');
    }

    // }}}
    // {{{ _checkGettext()

    /**
     * Tests to see if the gettext module is loaded 
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkGettext()
    {
        return extension_loaded('gettext'); 
    }

    // }}}
    // {{{ _checkPHPVersion()

    /**
     * Tests to see if the PHP version is good
     *
     * @access private
     * @return bool True if it succeeds, false otherwise
     */
    function _checkPHPVersion()
    {
        return version_compare(phpversion(), '4.2.1', '>=');
    }

    // }}}
}
?>
