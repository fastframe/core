<?php
/** $Id: Registry.php,v 1.7 2003/01/22 02:04:10 jrust Exp $ */
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
// {{{ includes

require_once dirname(__FILE__) . '/Error.php';

// }}}
// {{{ class FastFrame_Registry

/**
 * The FastFrame Registry class provides the innerworkings necessary for
 * transitioning between different applications, getting file paths, database
 * connections, and keeping track of configuration information 
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lesser.html.
 *
 * @version Revision: 2.0 
 * @author  Horde  http://www.horde.org/
 * @author  Dan Allen <dan@mojavelinux.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FastFrame_Registry {
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

    function FastFrame_Registry()
    {
        $this->importConfig();

        // Initial FastFrame-wide settings
        // Set the error reporting level in accordance with the config settings.
        error_reporting($this->getConfigParam('error/debug_level', null, array('app' => FASTFRAME_DEFAULT_APP)));

        // Set the maximum execution time in accordance with the config settings.
        @set_time_limit($this->getConfigParam('general/max_exec_time', null, array('app' => FASTFRAME_DEFAULT_APP)));

        // Set the umask according to config settings
        if (!is_null($umask = $this->getConfigParam('general/umask', null, array('app' => FASTFRAME_DEFAULT_APP)))) {
            umask($umask);
        }

        // Setup the default language, pending change based on app prefs
        $s_language = $this->getConfigParam('general/language', 'en_US', array('app' => FASTFRAME_DEFAULT_APP));
        setlocale(LC_MESSAGES, $s_language);
        putenv('LANG=' . $s_language);
        putenv('LANGUAGE=' . $s_language);

        // Set other common defaults like templates and graphics
        // All apps to be used must be included in the apps.php file
        // still need to enforce this though
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
     * @return object FastFrame_Registry instance
     */
    function &singleton ()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new FastFrame_Registry();
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
     */
    function importConfig($in_app = FASTFRAME_DEFAULT_APP)
    {
        $config =& $this->config;
        $apps =& $this->apps;

        // make sure we have the fastframe configuration loaded
        if (empty($config)) {
            $config = array();
            include_once FASTFRAME_ROOT . 'config/conf.php';
            $config[FASTFRAME_DEFAULT_APP] =& $conf;
        } 

        // make sure we have the apps loaded
        if (empty($apps)) {
            $apps = array();
            include_once FASTFRAME_ROOT . 'config/apps.php';
            // add the default app which might not be in the apps file since it is sort of a pseudo app
            if (!isset($apps[FASTFRAME_DEFAULT_APP])) {
                $apps[FASTFRAME_DEFAULT_APP] = array();
            }
        }

        // now load the configuration for this specific app
        if ($in_app != FASTFRAME_DEFAULT_APP && isset($apps[$in_app]) && !isset($config[$in_app])) {
            // here we change our root, but not our basic FastFrame structure in that root
            $s_file = File::buildPath(array($this->getConfigParam('webserver/file_root'), $this->getAppParam('root_apps'), $in_app, 'config/conf.php'));
            if (!is_readable($s_file)) {
                $tmp_error = PEAR::raiseError(null, FASTFRAME_NOT_CONFIGURED, null, E_USER_ERROR, "Can not import the config file for $in_app ($s_file)", 'FastFrame_Error', true);
                FastFrame::fatal($tmp_error, __FILE__, __LINE__); 
            }
            else {
                include_once $s_file;
            }

            $config[$in_app] =& $conf;
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
     * @param  string $in_app name of the application as listed in the application config file
     *
     * @access public
     * @return bool whether or not the application was added (only adds if not already current)
     */
    function pushApp($in_app)
    {
        static $b_sessionStarted;

        // we always push the app onto the app stack so it can be popped later
        $s_currentApp = $this->getCurrentApp();
        settype($this->appStack, 'array');
        array_push($this->appStack, $in_app);

        // if we are changing apps then we need to import config
        if ($in_app != $s_currentApp) {
            // if this is a login site and we have  not started a session, 
            // then we need to do so now
            if (!$this->useGuestFramework() && !isset($b_sessionStarted)) {
                $b_sessionStarted = true; 
                // don't use cookies to do the session
                if ($this->getConfigParam('session/append')) {
                    ini_alter('session.use_cookies', 0);
                }
                else {
                    // let the domain set itself, since it will default to the current domain
                    ini_alter('session.use_cookies', 1);
                    session_set_cookie_params(0, File::buildPath(array($this->getConfigParam('webserver/web_root'))));
                }

                // use a common session name for all apps
                session_name(urlencode($this->getConfigParam('session/name'))); 
                @session_start();
            }

            // import the config for this application
            $this->importConfig($in_app);

            // setup the language (could be overwritten by app)
            //bindtextdomain(FASTFRAME_DEFAULT_APP, $this->getAppBase() . 'locale');
            //textdomain(FASTFRAME_DEFAULT_APP) ;

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
        $currentApp = array_pop($this->appStack);

        $app = $this->getCurrentApp();
        // Import the new active application's configuration values again
        // if there is still at least one application on the stack
        if ($app) {
            $this->importConfig($app);
        }

        return $currentApp;
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
        switch ($in_type) {
            case FASTFRAME_WEBPATH:
                $pathParts = array($this->getConfigParam('webserver/web_root'), $in_file);
                break;
            case FASTFRAME_FILEPATH_PUBLIC:
                $pathParts = array($this->getConfigParam('webserver/file_root'), $in_file);
                break;
            case FASTFRAME_DATAPATH:
                $pathParts = array($this->getConfigParam('webserver/data_root'), $in_file);
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
     * @example getAppFile('view.inc.php', 'login', FASTFRAME_APP_TEMPLATES);
     */
    function getAppFile($in_filename, $in_app = null, $in_service = '', $in_type = FASTFRAME_FILEPATH_PUBLIC)
    {
        // this is how we handle the root path to the app 
        if (FastFrame::isEmpty($in_service)) {
            $s_service = '%app%';
        }
        elseif (is_null($s_service = $this->getAppParam('app_' . $in_service))) {
            return PEAR::raiseError(null, FASTFRAME_ERROR, null, E_USER_ERROR, "The app service 'app_$in_service' could not be found.", 'FastFrame_Error', true);
        }

        $s_app = !is_null($in_app) ? $in_app : $this->getCurrentApp();

        // if they want the data, there is only one option available 
        // for $in_type so we will set it here manually
        if ($in_service == 'data') {
            $in_type = FASTFRAME_DATAPATH;
        }

        $s_filename = File::buildPath(array($this->getAppParam('root_apps'), str_replace('%app%', $in_app, $s_service), $in_filename));
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
            return PEAR::raiseError(null, FASTFRAME_ERROR, null, E_USER_ERROR, "The service root_$in_service could not be found", 'FastFrame_Error', true);
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
    // {{{ getUserParam()

    /**
     * Lookup a setting for the current user 
     *
     * @param string $in_setting The setting to look up
     * @param string $in_default (optional) The default value if we cannot determine one
     *
     * @access public
     * @return string value of the variable or null
     */
    function getUserParam($in_setting, $in_default = null)
    {
        settype($this->user['settings'], 'array');
        // see if we've already gotten the variable before
        if (isset($this->user['settings'][$in_setting])) {
            return $this->user['settings'][$in_setting];
        } 

        // first settings are not app specific
        if ($in_setting == 'theme') {
            // [!] would first look up in database here [!]
            $s_setting = $this->getConfigParam('general/default_theme');
            // check if it's a valid theme
            if (!is_dir($this->getRootFile($s_setting, 'themes'))) {
                return PEAR::raiseError(null, FASTFRAME_ERROR, null, E_USER_ERROR, "Theme $s_setting does not exist", 'FastFrame_Error', true);
            }
        }
        // these settings are app specific or don't exist
        else {
            // [!] include extended Registry from app here to look up setting? [!]
            $s_setting = $in_default;
        }

        $this->user['settings'][$in_setting] = $s_setting;
        return $s_setting;
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

        list($s_category, $s_parameter) = explode('/', $in_paramPath);

        if (isset($this->config[$s_app][$s_category][$s_parameter])) {
            return $this->config[$s_app][$s_category][$s_parameter];
        } 
        else {
            // first check the default app for the setting before returning default val
            return isset($this->config[FASTFRAME_DEFAULT_APP][$s_category][$s_parameter]) ?
            $this->config[FASTFRAME_DEFAULT_APP][$s_category][$s_parameter] : $in_default;
        }
    }

    // }}}
    // {{{ getInitialPage()

    /**
     * Return the initial page hit when starting up this application
     *
     * Especially when switching applications, we need to know where to land.
     * This will tell us where.
     *
     * @param  string $in_app (optional) force the application to check
     *
     * @access public
     * @return string web-path for used to redirect
     */
    function getInitialPage($in_credentials = array())
    {
        $app = isset($in_credentials['app']) ? $in_credentials['app'] : $this->getCurrentApp();

        if (!isset($this->apps[$app])) {
            return FastFrame::fatal('"' . $app . '" is not configured in the FastFrame Registry.', __FILE__, __LINE__);
        }
        
        return FastFrame::url(File::buildPath(array($this->getConfigParam('webserver/web_root'), $this->getAppParam('initial_page', null, array('app' => $app), false)))); 
    }

    // }}}
    // {{{ initDataObject()

    /**
     * Initializes the data object
     *
     * @param array $in_options (optional) The options to initialize the DataObject class.
     *              If an empty array we use the getDataObjectOptions() method.  If it is
     *              set then we assume you have the necessary variables set that
     *              getDataObjectOptions() sets.  This allows you to use an ini file if you
     *              wish.
     *
     * @access public
     * @return void 
     */
    function initDataObject($in_options = array())
    {
        $options =& PEAR::getStaticProperty('DB_DataObject','options');
        if (empty($in_options)) {
            $a_config = $this->getDataObjectOptions();
        }
        else {
            $a_config = $in_options;
        }

        $options = $a_config;
    }

    // }}}
    // {{{ getDataObjectOptions()

    /**
     * Gets the variables needed for initializing the DataObject 
     *
     * @param array $in_options (optional) Additional options that will be added to the conf array.
     * @param array $in_credentials (optional) override of current state, such as the app
     *
     * @see The example.ini in the DB_DataObject package
     * @access public
     * @return array The options needed for setting up the DataObjects class 
     */
    function getDataObjectOptions($in_options = array(), $in_credentials = array())
    {
        $s_app = isset($in_credentials['app']) ? $in_credentials['app'] : $this->getCurrentApp();

        $a_config = array();
        // see if we should even set up a database setup 
        $s_type = $this->getConfigParam('data/type', null, array('app' => $s_app));
        if (is_null($s_type)) {
            return $a_config; 
        }

        // construct dsn
        $a_config['database'] = $s_type . '://' .
                                $this->getConfigParam('data/username', null, array('app' => $s_app)) . ':' .
                                $this->getConfigParam('data/password', null, array('app' => $s_app)) . '@' .
                                $this->getConfigParam('data/host', null, array('app' => $s_app)) . '/' .
                                $this->getConfigParam('data/database', null, array('app' => $s_app));
        $a_config['schema_location'] = $this->getConfigParam('data/schema_location', null, array('app' => $s_app)); 
        $a_config['class_location'] = $this->getConfigParam('data/class_location', null, array('app' => $s_app)); 
        $a_config['require_prefix'] = 'DataObjects/'; 
        $a_config['class_prefix'] = 'DataObjects_'; 
        $a_config['debug'] = $this->getConfigParam('data/debug_level', null, array('app' => $s_app));
        $a_config['production'] = $this->getConfigParam('data/production', null, array('app' => $s_app));
        $a_config = array_merge($a_config, $in_options);
        
        return $a_config;
    }

    // }}}
    // {{{ getDataConnection()

    /**
     * Gets the data connection which has been set up by DataObject. 
     *
     * @param object $in_obj_data The data object
     *
     * @access public
     * @return object The database connection
     */
    function &getDataConnection(&$in_obj_data)
    {
        $in_obj_data->_connect();
        return $GLOBALS['_DB_DATAOBJECT']['CONNECTIONS'][$in_obj_data->_database_dsn_md5];
    }

    // }}}
    // {{{ getAppRoot()

    /**
     * Get the relative base of the application
     *
     * This function requires that in each file for your application you
     * define the constant *_ROOT where * is the name of the application.
     * This function is more of a convenience function for getting the current
     * base without having to hardcode in the name of the application.
     *
     * @param  array $in_credentials (optional) overrides for the current state
     *
     * @access public
     * @return string path to the base of the application
     */
    function getAppRoot($in_credentials = array())
    {
        $app = isset($in_credentials['app']) ? $in_credentials['app'] : $this->getCurrentApp();

        return ($base = constant(strtoupper($app) . '_ROOT')) ? $base : '.';
    }

    // }}}
    // {{{ getAppDatabase()

    /**
     * Retrieve the database for the current application
     *
     * Provided that there are not credential overrides, this function returns
     * the database of the current application.  Overrides can be passed in through
     * the credential array
     *
     * @param  array $in_credentials (optional) overrides for the current state
     *
     * @access public
     * @return string application database name
     */
    function getAppDatabase($in_credentials = array()) 
    {
        static $s_lastApp, $s_database;

        $s_app = !isset($in_credentials['app']) ? $this->getCurrentApp() : $in_credentials['app'];

        // if we switched apps then we need to reset the database name
        if ($s_app != $s_lastApp) {
            $s_lastApp = $s_app;
            $s_database = $this->getConfigParam('data/database');
        }

        return $s_database;
    }

    // }}}
    // {{{ getRootLocale()
   
    /**
     * Get locale for the root
     * 
     * @return void
     */
    function &getRootLocale()
    {
        $lang = array();
        //include_once $this->getRootFile('core.lang.php', 'langs');
 
        return $lang;
    }

    // }}}
    // {{{ getAppLocale()

    /**
     * Sets the locale for a particular app
     *
     * @return void
     */
    function &getAppLocale($in_appPath, $in_library = 'main')
    {
        $lang = array();
        //include_once $this->getAppFile($in_library . '.lang.php', $in_appPath, 'langs');
        global $a_lang;
        settype($a_lang, 'array');
        return array_merge($a_lang, $lang);
    }

    // }}}
    // {{{ getStartPage()

    /**
     * Gets the start page for the system
     *
     * @param array $in_queryVars (optional) Additional query vars
     *
     * @access public
     * @return string
     */
    function getStartPage($in_queryVars = array())
    {
        $url = FastFrame::url($this->getAppParam('initial_page', null, array('app' => 'fastframeold'), false), array($this->getConfigParam('session/name') => false), $in_queryVars, true); 

        return $url;
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
        $a_apps = array();
        foreach ($this->apps as $s_app => $a_vals) {
            $a_apps[] = $s_app;
        }

        return $a_apps;
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
