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
// | Authors: The Horde Team <http://www.horde.org>                       |
// |          Jason Rust <jrust@codejanitor.com>                          |
// |          Dan Allen <dan@mojavelinux.com>                             |
// +----------------------------------------------------------------------+

// }}}
// {{{ includes

require_once 'File.php';
require_once dirname(__FILE__) . '/Error.php';
require_once dirname(__FILE__) . '/Locale.php';

// }}}
// {{{ constants/globals

// types of filepaths that can be generated
define('FASTFRAME_WEBPATH',           1);
define('FASTFRAME_FILEPATH_PUBLIC',   2);
define('FASTFRAME_FILEPATH_RELATIVE', 3);
define('FASTFRAME_DATAPATH',          4);

// the default application
define('FASTFRAME_DEFAULT_APP', 'fastframe');

$GLOBALS['_FASTFRAME_PATH'] = array(
    // names of the directories in the fastframe root
    'root_apps'       =>  'apps',
    'root_config'     =>  'config',
    'root_language'   =>  'locale',
    'root_libs'       =>  'lib',
    'root_javascript' =>  'js',
    'root_logs'       =>  'logs',
    'root_graphics'   =>  'graphics',
    'root_cache'      =>  'cache',
    'root_fonts'      =>  'fonts',
    'root_themes'     =>  'themes',
    // names of the services inside an app (%app% is the basename of the app)
    'app_pages'       => '%app%/pages',
    'app_data'        => '%app%/data',
    'app_libs'        => '%app%/lib',
    'app_graphics'    => '%app%/graphics',
    'app_language'    => '%app%/locale',
    'app_config'      => '%app%/config',
);

// }}}
// {{{ class FF_Registry

/**
 * The FastFrame Registry class provides the innerworkings necessary for
 * transitioning between different applications, getting file paths, database
 * connections, and keeping track of configuration information 
 *
 * @version Revision: 2.0 
 * @author  The Horde Team <http://www.horde.org/>
 * @author  Dan Allen <dan@mojavelinux.com>
 * @author  Jason Rust <jrust@codejanitor.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_Registry {
    // {{{ properties

    /**
     * Hash storing information on each registry-aware application
     * @var array $apps
     */
    var $apps = array();

    /**
     * Hash storing application configurations.
     * @var array $conf
     */
    var $config = array();

    /**
     * Stores all user settings 
     * @var array $user
     */
    var $user = array();

    /**
     * The application stack
     * @var array $appStack
     */
    var $appStack = array();

    // }}}
    // {{{ constructor

    function FF_Registry()
    {
        // Don't set the language because it will run into an infinite loop otherwise,
        // since the Locale class uses the registry.
        $this->pushApp(FASTFRAME_DEFAULT_APP, false);

        // Initial FastFrame-wide settings
        // Set the error reporting level in accordance with the config settings.
        error_reporting($this->getConfigParam('error/debug_level'));

        // Set the maximum execution time in accordance with the config settings.
        @set_time_limit($this->getConfigParam('general/max_exec_time'));

        // Set the umask according to config settings
        if (!is_null($umask = $this->getConfigParam('general/umask'))) {
            umask($umask);
        }

        // Set other common defaults like templates and graphics
        // All apps to be used must be included in the apps.php file
        foreach (array_keys($this->apps) as $s_name) {
            $a_app =& $this->apps[$s_name];
            // load the overrides for the different location paths in the application
            foreach ($GLOBALS['_FASTFRAME_PATH'] as $s_name => $s_path) {
                if (!isset($a_app[$s_name])) {
                    $a_app[$s_name] = $s_path;
                }
            }
        } 
    }

    // }}}
    // {{{ singleton()

    /**
     * To be used in place of the contructor to return any open instance.
     *
     * The idea with a registry is that we only ever want a single instance since
     * all of the properties and methods must map to a single state.  Therefore, this
     * function is used in place of the contructor to return any open instances of
     * the registry object, and if none are open will create a new instance and cache
     * it using a static variable
     *
     * @access public
     * @return object FF_Registry instance
     */
    function &singleton()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new FF_Registry();
        }

        return $instance;
    }

    // }}}
    // {{{ importConfig()

    /**
     * Read in configuration values for the application
     *
     * Reads the configuration values for the given application and
     * imports them into the class configuration variable.
     *
     * @param string $in_app (optional) name of the application.
     *
     * @access public
     * @return void
     */
    function importConfig($in_app = FASTFRAME_DEFAULT_APP)
    {
        // :NOTE: All fatal errors generated in this method should not be logged
        // since the log method calls getConfigParam() which could cause an infinite loop

        // Make sure we have the fastframe configuration loaded
        if (empty($this->config)) {
            $this->config = array();
            include_once FASTFRAME_ROOT . '/config/conf.php';
            $this->config[FASTFRAME_DEFAULT_APP] =& $conf;
        } 

        // Make sure we have the apps loaded
        if (empty($this->apps)) {
            $this->apps = array();
            include_once FASTFRAME_ROOT . '/config/apps.php';
            $this->apps =& $apps;
            // Add the default app which might not be in the apps file
            // since it is sort of a pseudo app
            if (!isset($this->apps[FASTFRAME_DEFAULT_APP])) {
                $this->apps[FASTFRAME_DEFAULT_APP] = array();
            }
        }

        if (!isset($this->apps[$in_app])) {
            $tmp_error = PEAR::raiseError(null, FASTFRAME_NOT_CONFIGURED, null, E_USER_ERROR, "The application $in_app is not a defined application.  Check your apps.php file.", 'FF_Error', true);
            FastFrame::fatal($tmp_error, __FILE__, __LINE__, false); 
        }

        // Now load the configuration for this specific app
        if (!isset($this->config[$in_app]) && $in_app != FASTFRAME_DEFAULT_APP) {
            // Look up which profile to use for the config files
            if (isset($this->apps[$in_app]['profile'])) {
                $s_configFile = sprintf('conf.%s.php', $this->apps[$in_app]['profile']);
            } 
            else {
                $s_configFile = 'conf.php';
            }

            // here we change our root, but not our basic FastFrame structure in that root
            //$s_file = $this->getAppFile($s_configFile, $in_app, 'config');
            $s_file = FASTFRAME_ROOT . "apps/$in_app/config/$s_configFile";
            if (!is_readable($s_file)) {
                $tmp_error = PEAR::raiseError(null, FASTFRAME_NOT_CONFIGURED, null, E_USER_ERROR, "Can not import the config file for $in_app ($s_file)", 'FF_Error', true);
                FastFrame::fatal($tmp_error, __FILE__, __LINE__, false); 
            }
            else {
                include_once $s_file;
            }

            $this->config[$in_app] =& $conf;
        }
    }

    // }}}
    // {{{ setLocale()

    /**
     * Sets up the locale information. 
     *
     * @param string $in_app (optional) The application name
     *
     * @access public
     * @return void
     */
    function setLocale($in_app = null)
    {
        if (is_null($in_app)) {
            $in_app = $this->getCurrentApp();
        }

        FF_Locale::setLang();
        if ($in_app == FASTFRAME_DEFAULT_APP) {
            FF_Locale::setTextdomain($in_app, $this->getRootFile('', 'language'), FF_Locale::getCharset());
        }
        else {
            FF_Locale::setTextdomain($in_app, $this->getAppFile('', $in_app, 'language'), FF_Locale::getCharset());
        }
    }

    // }}}
    // {{{ pushApp()

    /**
     * Add an application to the application stack and make it the current application
     *
     * Instead of having a single application, a layered approach is used.  This way, when
     * one application shuts down, it can fall back to the previous application if this
     * method is desired.  The configuration also works in this way, by layering the
     * configuration settings, the previous settings are updated by the new applications
     * settings.
     *
     * @param string $in_app name of the application as listed in the application config file
     * @param bool $in_locale (optional) Set the app's locale info?
     *
     * @access public
     * @return bool whether or not the application was added (only adds if not already current)
     */
    function pushApp($in_app, $in_locale = true)
    {
        $s_currentApp = $this->getCurrentApp();
        settype($this->appStack, 'array');
        // We always push the app onto the app stack so it can be popped later
        array_push($this->appStack, $in_app);

        // If we are changing apps then we need to import config
        if ($in_app != $s_currentApp) {
            // Import the config for this application
            $this->importConfig($in_app);
            if ($in_locale) {
                $this->setLocale($in_app);
            }

            return true;
        }
        
        return false;
    }

    // }}}
    // {{{ popCurrentApp()

    /**
     * Pop the current application from the application stack and return it
     *
     * Just as pushApp() adds a new application, popCurrentApp() takes away the current
     * application and re-reads the configuration values again pertaining to that application.
     *
     * @access public
     * @return string current application
     */
    function popCurrentApp()
    {
        $s_currentApp = array_pop($this->appStack);
        $s_app = $this->getCurrentApp();
        // Import the new active application's configuration values again
        // if there is still at least one application on the stack
        if (!is_null($s_app) && $s_app != $s_currentApp) {
            $this->importConfig($s_app);
            $this->setLocale($s_app);
        }

        return $s_currentApp;
    }

    // }}}
    // {{{ getCurrentApp()

    /**
     * Get the current application that is being utilized
     *
     * @access public
     * @return string The most recent application name.  null is returned if no app has been
     * pushed 
     */
    function getCurrentApp()
    {
        return isset($this->appStack) ? end($this->appStack) : null;
    }

    // }}}
    // {{{ getFile()

    /**
     * Puts togehter the parts of a file to return the correct path, based on type to the
     * file.
     *
     * @param string $in_file The file name
     * @param int    $in_type (optional) The type of path
     *
     * @access public
     * @return string The file path
     */
    function getFile($in_file, $in_type = FASTFRAME_FILEPATH_PUBLIC)
    {
        $pathParts = array();
        // App should always be FASTFRAME_DEFAULT_APP because this method can be called
        // from getConfigParam() and we don't want an infinite loop
        switch ($in_type) {
            case FASTFRAME_WEBPATH:
                $pathParts = array($this->getConfigParam('webserver/web_root', '', array('app' => FASTFRAME_DEFAULT_APP)), $in_file);
                break;
            case FASTFRAME_FILEPATH_PUBLIC:
                $pathParts = array($this->getConfigParam('webserver/file_root', '', array('app' => FASTFRAME_DEFAULT_APP)), $in_file);
                break;
            case FASTFRAME_DATAPATH:
                $pathParts = array($this->getConfigParam('webserver/data_root', '', array('app' => FASTFRAME_DEFAULT_APP)), $in_file);
                break;
            case FASTFRAME_FILEPATH_RELATIVE:
                $pathParts = array($in_file);
                break;
            default:
                break;
        }
        
        return File::buildPath($pathParts);
    }

    // }}}
    // {{{ getAppFile()

    /**
     * Return a path that pertains to the app provided.
     *
     * By passing in a app directory name, it is possible to retrieve paths
     * that pertain to apps, such as template and data paths without having
     * to manual construct a path.
     *
     * @param  string $in_filename name of the file the path leads to
     * @param  string $in_app (optional) name of the app directory, such as 'login'
     * @param  string $in_service (optional) category of file, such as 'pages', 'data', etc..
     * @param  int    $in_type (optional) what type of path to build, based on the constants
     *
     * @access public
     * @return string path for the app file
     * @example getAppFile('view.inc.php', 'login', 'lib', FASTFRAME_APP_TEMPLATES);
     */
    function getAppFile($in_filename, $in_app = null, $in_service = '', $in_type = FASTFRAME_FILEPATH_PUBLIC)
    {
        // this is how we handle the root path to the app 
        if (FastFrame::isEmpty($in_service)) {
            $s_service = '%app%';
        }
        elseif (is_null($s_service = $this->getAppParam('app_' . $in_service))) {
            return PEAR::raiseError(null, FASTFRAME_ERROR, null, E_USER_ERROR, "The app service 'app_$in_service' could not be found.", 'FF_Error', true);
        }

        $s_app = !is_null($in_app) ? $in_app : $this->getCurrentApp();

        // if they want the data, there is only one option available 
        // for $in_type so we will set it here manually
        if ($in_service == 'data') {
            $in_type = FASTFRAME_DATAPATH;
        }

        // use the app name as the apps directory unless the user has 
        // overriden it in apps.php
        if ($this->getAppParam('app_dir',null, array('app' => $s_app))) {
            $s_appDir = str_replace('%app%', $this->getAppParam('app_dir', null, array('app' => $s_app)), $s_service);
        }
        else {
            $s_appDir = str_replace('%app%', $s_app, $s_service);
        }

        $s_filename = File::buildPath(array($this->getAppParam('root_apps'), $s_appDir, $in_filename));
        return $this->getFile($s_filename, $in_type);
    }

    // }}}
    // {{{ getRootFile()

    /**
     * Return a path that pertains to the FastFrame framework
     *
     * By passing in a app directory name, it is possible to retrieve paths
     * that pertain to apps, such as template and data paths without having
     * to manually construct a path.  The type of path generated will either be
     * a filepath (full path to file) or a webpath (full web path to file) which
     * can be used appropriately.
     *
     * @param  string $in_filename name of the file the path leads to
     * @param  string $in_service (optional) category of file, such as 'templates', 'data', etc..
     * @param  int    $in_type what type of path to build, based on the constants
     *
     * @access public
     * @return string Path for the root file
     */
    function getRootFile($in_filename, $in_service = '', $in_type = FASTFRAME_FILEPATH_PUBLIC)
    {
        if ($in_service == '') {
            $s_service = '';
        }
        elseif (is_null($s_service = $this->getAppParam('root_' . $in_service))) {
            return PEAR::raiseError(null, FASTFRAME_ERROR, null, E_USER_ERROR, "The service root_$in_service could not be found", 'FF_Error', true);
        }

        // if they want the data, there is only one option available 
        // for $in_type so we will set it here manually
        if ($in_service == 'data') {
            $in_type = FASTFRAME_DATAPATH;
        }

        // right now we have no variables in the root services
        $s_filename = File::buildPath(array($s_service, $in_filename));
        return $this->getFile($s_filename, $in_type);
    }

    // }}}
    // {{{ getAppParam()

    /**
     * Get a parameter for the current application
     *
     * @param string    $in_parameter The parameter to get
     * @param string    $in_default (optional) What to return if the app param is not set
     * @param array     $in_credentials (optional) Any credentials to pass in (i.e. force an app)
     * @param bool      $in_fallback (optional) Fallback to default (fastframe) application if app setting
     *                  is not set?
     *
     * @see config/apps.php
     * @access public
     * @return mixed The application parameter
     */
    function getAppParam($in_parameter, $in_default = null, $in_credentials = array(), $in_fallback = true)
    {
        $s_app = isset($in_credentials['app']) ? $in_credentials['app'] : $this->getCurrentApp();

        if (isset($this->apps[$s_app][$in_parameter])) {
            return $this->apps[$s_app][$in_parameter];
        } 
        elseif ($in_fallback) {
            return isset($this->apps[FASTFRAME_DEFAULT_APP][$in_parameter]) ?
            $this->apps[FASTFRAME_DEFAULT_APP][$in_parameter] : $in_default;
        }
        else {
            return $in_default;
        }
    }

    // }}}
    // {{{ getConfigParam()

    /**
     * Lookup a configuration variable for a particular application
     *
     * Lookup the configuration variable for a particular appliation and if it does not
     * exist then look in the fastframe configuration and see if it is there.  If the variable
     * is found in neither place then it returns null.
     *
     * @param  string $in_paramPath The path of the configuration parameter (e.g. general/language) 
     * @param  string $in_default (optional) Default value to give if param is not present
     * @param  string $in_credentials (optional) override of current state
     *
     * @access public
     * @return string value of the variable or null
     */
    function getConfigParam($in_paramPath, $in_default = null, $in_credentials = array()) 
    {
        $s_app = isset($in_credentials['app']) ? $in_credentials['app'] : $this->getCurrentApp();
        // Import the config for this app if it has not yet beeen imported 
        if (!isset($this->config[$s_app])) {
            $this->importConfig($s_app);
        }

        $a_parts = explode('/', $in_paramPath);
        $s_path = implode('\'][\'', $a_parts);
        $s_varName = '$this->config[\'' . $s_app . '\'][\'' . $s_path . '\']';
        if (eval('return isset(' . $s_varName . ');')) {
            $result = eval('return ' . $s_varName . ';'); 
        } 
        else {
            // First check the default app for the setting before returning default val
            $s_varName = '$this->config[\'' . FASTFRAME_DEFAULT_APP . '\'][\'' . $s_path . '\']';
            $result = eval('return isset(' . $s_varName . ');') ? eval('return ' . $s_varName . ';') : $in_default;
        }

        return $result;
    }

    // }}}
    // {{{ getDataDsn()

    /**
     * Creates the dsn used by the PEAR DB library from the config values.
     *
     * @param array $in_credentials (optional) override of current state, such as the app
     *
     * @access public
     * @return string The DSN
     */
    function getDataDsn($in_credentials = array())
    {
        $s_dsn = $this->getConfigParam('data/type', null, $in_credentials) . '://' .
                 $this->getConfigParam('data/username', null, $in_credentials) . ':' .
                 $this->getConfigParam('data/password', null, $in_credentials) . '@' .
                 $this->getConfigParam('data/host', null, $in_credentials) . '/' .
                 $this->getConfigParam('data/database', null, $in_credentials);
        return $s_dsn;
    }

    // }}}
    // {{{ getApps()

    /**
     * Gets a list of the available apps.
     *
     * @access public
     * @return array An array of the apps.
     */

    function getApps()
    {
        static $a_apps;
        if (isset($a_apps)) {
            return $a_apps;
        }

        $a_apps = array();
        foreach ($this->apps as $s_app => $a_vals) {
            $a_apps[] = $s_app;
        }

        return $a_apps;
    }

    // }}}
    // {{{ hasApp()

    /**
     * Determine if the current installation has a certain app
     *
     * @param string $in_app The app name to check
     *
     * @access public
     * @return bool True if the app is installed, false otherwise
     */
    function hasApp($in_app)
    {
        if (isset($this->apps[$in_app]) &&
            isset($this->apps[$in_app]['status']) &&
            $this->apps[$in_app]['status'] != 'disabled') {
            return true;
        }
        else {
            return false;
        }
    }

    // }}}
    // {{{ useGuestFramework()

    /**
     * Determine if guests are allowed for this application
     *
     * Check the configuration for this application and see if this is a public
     * application or if authentication is required.  This is the main barrier
     * that must be crossed for taking a site from public to private.
     *
     * @param  string $in_app (optional) name of the application to test
     *
     * @access public
     * @return bool whether or not this is a public application
     */
    function useGuestFramework($in_credentials = array())
    {
        $app = isset($in_credentials['app']) ? $in_credentials['app'] : $this->getCurrentApp();

        return $this->getAppParam('allow_guests', false, array('app' => $app)) ? true : false;        
    }

    // }}}
}
?>
