<?php
// [!] make it work in IE [!]
// [!] close button on console window [!]
// [!] SYSTEM_LOG = Off doesn't seem to work [!]
// [!] display more info in output such as user's ip, referer, etc. [!]
// [!] send mail logs in one big batch [!]
// {{{ constants

define('SYSTEM_LOG', 0);
define('MAIL_LOG',   1);
define('FILE_LOG',   3);

define('E_USER_ALL',    E_USER_NOTICE | E_USER_WARNING | E_USER_ERROR);
define('E_NOTICE_ALL',  E_NOTICE | E_USER_NOTICE);
define('E_WARNING_ALL', E_WARNING | E_USER_WARNING | E_CORE_WARNING | E_COMPILE_WARNING);
define('E_ERROR_ALL',   E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);
define('E_NOTICE_NONE', E_ALL & ~E_NOTICE_ALL);
define('E_DEBUG',       0x10000000);
define('E_VERY_ALL',    E_ERROR_ALL | E_WARNING_ALL | E_NOTICE_ALL | E_DEBUG);

// constants for failure of loading this class.  These must be here since they
// are used as a dire straits situation.
define('ERRORHANDLER_INI',        dirname(__FILE__) . '/Error_Handler.ini');
define('ERRORHANDLER_LOG_TYPE',   FILE_LOG);
define('ERRORHANDLER_LOG_TARGET', '/tmp/error_log');

// }}}
// {{{ includes

require_once 'PEAR.php';
   
// }}}
// {{{ class Error_Handler
// }}}
class Error_Handler extends PEAR
{
   // {{{ properties

   /**
    * console link
    * @var string $consoleLink
    */
    var $consoleLink = '<div style="position: absolute; right: 0px; top: 1px;"><a style="%s" href="javascript: void(0);" onclick="return showErrors();">%s</a></div>';

   /**
    * The number of lines to allow for a variable in a context report
    * @var int $numContextLines
    */
    var $numContextLines = 15;

   /**
    * console javascript
    * @var string $consoleFunction
    */
    var $consoleFunction = '<script type="text/javascript" language="JavaScript"><!--
function showErrors() 
{
    Error_Handler = window.open("", "Error_HandlerConsole","resizable=yes,scrollbars=yes,directories=no,location=no,menubar=no,status=no,toolbar=no");
    Error_Handler.document.open();
    Error_Handler.document.writeln("<html><head><title>Error_Handler Console: %s</title>");
    Error_Handler.document.writeln("<style>body { %s } span.errorLevel { %s } span.errorMessage { %s } </style>");
    Error_Handler.document.writeln("<script>function errorJump(index, offset) { if (element = document.getElementById(\'error\' + (index + offset))) { window.scrollTo(0, element.offsetTop); } return void(0); }</scr" + "ipt>");
    Error_Handler.document.writeln("</head><body>");
    %s
    Error_Handler.document.writeln("</body></html>");
    Error_Handler.document.close();
    return false;
}
//--></script>';

   /**
    * small javascript code sent to the client for client-side redirection
    * @var string $replaceScript
    */
    var $replaceScript = '<script type="text/javascript" language="JavaScript"><!--
setTimeout("window.location.href = \'%1$s\'", 3000);
//--></script>
:::ERROR!::: You will be redirected automatically in 3 seconds,
otherwise <a href="%1$s">click here</a>.';

   /**
    * custom callback function to be used on error
    * @var string $CUSTOM
    */
   var $CUSTOM = '';

   /**
    * date format to be used in logs
    * @var string $DATEFORMAT
    */
   var $DATEFORMAT = "Y-m-dTH:i:s";

   /**
    * use the error console window
    * @var boolean $useConsole
    */
   var $useConsole = true;

   /**
    * Sections is the .ini file to be parsed
    * @var array $_section
    */
   var $_section = array(); 

   /**
    * array of report level's
    * @var array $_level
    */
   var $_level = array();

   /**
    * array of registered file log information
    * @var array $_altdlog
    */
   var $_altdlog = array();

   /**
    * array of context settings
    * @var array $_context
    */
   var $_context = array();

   /**
    * array of source settings
    * @var array $_source
    */
   var $_source = array();

   /**
    * array of logging information
    * @var array $_logging
    */
   var $_logging = array();

   /**
    * array of fault/replace report
    * @var array $_replace
    */
   var $_replace = array();

   /**
    * Styles used in the console popup window
    * @var array $_styles
    */
   var $_styles = array();

   /**
    * error trap stack (not affected by LOCKED directive)
    * @var array $_trap
    */
   var $_trap = array();

   /**
    * error trap stack (not affected by LOCKED directive)
    * @var int $_trap_level
    */
   var $_trap_level = 0;

   /**
    * console buffer for the console window html
    * @var string $_console
    */
   var $_console = '';

   /**
    * number of errors
    * @var int $_error_count
    */
   var $_error_count = 0;

   // }}}
    // {{{ constructor

    function Error_Handler($inifile = ERRORHANDLER_INI) 
    {
        // call this so that our deconstructor can be registered
        $this->Pear();

        // names of the built-in sections to be recognized
        $this->_section = array('CONTEXT', 'LOGGING', 'REPLACE', 'SOURCE', 'STYLES');
        $this->_level   = array('ALL' => 0);
        $this->_trap    = array();
        $this->_escchrs = array("\t" => '\\t', "\n" => '\\n', "\r" => '\\r', '\\' => '&#092;', "'" => '&#39;');
        // setup from the supplied ini file
        if ($this->loadConfig($inifile)) {
            // E_ERRORs cannot be handled by Error_Handler under some circumstances.
            // Thus, "display_errors" php.ini setting is enabled if the console is being used
            ini_set('display_errors', $this->useConsole);
        } 
        // some error occured during Error_Handler start up, so abort
        else {
            error_log($inifile.": configuration error, script terminated.\n", ERRORHANDLER_LOG_TYPE, ERRORHANDLER_LOG_TARGET);
            exit;
         }
    }

    // }}}
    // {{{ destructor

    /**
     * Output the console buffer to the browser and generate link
     *
     * This function will be invoked when the page is done loading as handled
     * by the PEAR destructor handler function.  It determines if the
     * console is being used and if so outputs the appropriate javascript code.
     *
     * @access private
     * @return void()
     */
    function _Error_Handler()
    {
        // unlock preferences
        $this->_lock_reset();

        // if the console is empty or if we are not using console,
        // just return the captured buffer and no console and no link
        if (empty($this->_console) || !$this->useConsole) {
            return;
        }

        // merge the console buffer into the javascript template
        $console = sprintf($this->consoleFunction, $_SERVER['SCRIPT_NAME'], $this->_styles['body'], $this->_styles['error_level'], $this->_styles['error_message'], $this->_console);

        $console_link_content = isset($this->_styles['console_link_text']) ? $this->_styles['console_link_text'] : '';
        if (isset($this->_styles['console_link_img'])) {
            $console_link_content .= '<img src="' . $this->_styles['console_link_img'] . '" border="0" />';
        }

        $consoleLink  = sprintf($this->consoleLink, $this->_styles['console_link_style'], $console_link_content);

        echo $console . $consoleLink;
    }

    // }}}
    // {{{ loadConfig()

    /**
     * Load the configuration settings from the .ini file
     *
     * @param  string $inifile ini file for Error_Handler
     *
     * @access public
     * @return boolean success
     */
    function loadConfig($inifile) 
    {
        // make sure we can read this thing
        if (!file_exists($inifile) || !is_readable($inifile)) {
           return false;
        }

        $ini = parse_ini_file($inifile, true);
        foreach ($ini as $section => $setting) {
            // if the setting is an array, we will process it
            if (is_array($setting)) {
                // handle the section
                foreach ($setting as $flag => $value) {
                    // if flag or value can be a constant, use that constant instead
                    if (defined($flag)) {
                        $flag = constant($flag);
                    }

                    if (in_array($section, $this->_section)) {
                        // built-in SECTION directives
                        if (!strcasecmp($flag, 'level')) {
                            $this->set($section, $this->_int_level($value));
                        } 
                        else {
                            call_user_func(array(&$this, 'set'.ucfirst($section)), $flag, $value);
                        }
                    } 
                    // not built-in SECTION directives ~> ADLTLOG file-registering
                    else {
                        $this->setAltdlog($section, $flag, $value);
                    }
                }
            } 
            // register the global settings from the ini file
            else {
                switch ($flag = strtoupper($section)) {
                    case 'LEVEL' :
                        $this->set('DEFAULT', $this->_int_level($setting));
                        break;
                    case 'CONSOLE':
                        $this->useConsole = (bool)$setting;
                        break;
                    case 'LOCKED': 
                    case 'CUSTOM':
                    case 'DATEFORMAT':
                        $this->$flag = $setting;
                    default:
                }
            }
        }

        // if global LEVEL is not specified, try to get from php.ini
        if (!isset($this->_level['DEFAULT'])) {
            $this->set('DEFAULT', ini_get('error_reporting'));
        }

        // lock Error_Handler configuration properties
        if (!empty($this->LOCKED)) {
            if (!defined('ERRORHANDLER_LOCKED')) {
                $lock = get_object_vars($this);
                unset($lock['_CONSOLE']);
                unset($lock['_TRAP']);
                unset($lock['_TRAP_LEVEL']);
                // LOCK has been activated
                define('ERRORHANDLER_LOCKED', serialize($lock));
            } 
            else {
                return false;
            }
        }

        return true;
    }

    // }}}
    // {{{ restore()

    /**
     * Restore the default php.ini settings affected by Error_Handler
     *
     * This is a quick hack, it restores the values available at script
     * start-up not the values which is available Error_Handler's startup
     *
     * @access public
     * @return void
     */
    function restore() 
    {
        restore_error_handler();
        ini_restore('error_log');
        ini_restore('log_errors');
        ini_restore('display_errors');
    }

    // }}}
    // {{{ set() nd

    function set($section, $level) 
    {
        static $prev_level = null;

        if (defined('ERRORHANDLER_LOCKED') || (!in_array($section, $this->_section) && strcmp($section, 'DEFAULT'))) {
            return false;
        }
        // "quick switch" (ON/OFF), isset(...) indicates that it can be used after loadConfig() only
        if (is_bool($level) && isset($this->_level[$section])) {
            // turn ON report (specified in $section)
            if ($level) {
                // attempt to restore previous level, otherwise use DEFAULT
                $prev_level[$section] = $this->_level[$section];
                $this->_level[$section] = !empty($prev_level[$section]) ? $prev_level[$section] : $this->_level['DEFAULT'];
            }
            // turn OFF report (specified in $section) if possible (level > 0)
            else if (!empty($this->_level[$section])) {
                $prev_level[$section]   = $this->_level[$section];
                $this->_level[$section] = 0;
            }
        }
        // general purpose switch / change the actual report level
        else { 
            $level = $this->_int_level($level);
            $prev_level[$section]  = isset($this->_level[$section]) ? $this->_level[$section] : 0;
            @$this->_level['ALL'] |= $this->_level[$section] = $level;
            if (strcasecmp($section, 'DEFAULT') == 0) {
                foreach ($this->_section as $s) {
                    if (empty($this->_level[$s])) {
                       $this->_level[$s] = $this->_level['DEFAULT'];
                    }
                }
           }

           return $prev_level[$section];
        }
    }

    // }}}
    // {{{ setAltdlog() nd

    function setAltdlog($file_name, $flag, $value = null)
    {
        if (defined('ERRORHANDLER_LOCKED')) {
            return false;
        }

        if ($flag === false) {
            // clears previously registered $file_name
            unset($this->_altdlog[crc32(realpath($file_name))]);
            return true;
        }
        else if (isset($value)) {
            $file_name = crc32(realpath($file_name));
            if ($file_name) {
                switch ($flag) {
                    case 'level':
                        @$this->_level['ALL'] |= $this->_level['ALTDLOG'][$file_name] = $this->_int_level($value);
                        break;
                    case MAIL_LOG:
                    case FILE_LOG:
                    case SYSTEM_LOG:
                        $this->_altdlog[$file_name][$flag] = $value;
                    default: 
                }

                return true;
            } 
            else {
                return false;
            }
        } 
    }

    // }}}
    // {{{ setContext() nd

    function setContext($flag, $value = null)
    {
        if (defined('ERRORHANDLER_LOCKED')) {
            return false;
        }

        switch ($flag = strtolower($flag)) {
            case 'strict':
                $this->_context[$flag] = intval($value); 
                break;
            case 'exclude':
                // if the 2nd arg is empty it clears the actual exclude list
                if ( empty($value) || empty($this->_context['exclude']) ){
                    $this->_context['exclude'] = array();
                }
                for ( $i = 1; $i < func_num_args(); $i++ ){
                    $arg = func_get_arg($i);
                    if ( is_string($arg) ){
                        $this->_context['exclude'] = array_merge($this->_context['exclude'], preg_split('/[\W]+/', $arg, -1, PREG_SPLIT_NO_EMPTY));
                    }
                    else if (is_array($arg)) {
                        $this->_context['exclude'] = array_merge($this->_context['exclude'], $arg);
                    }
                }
            default:
        }
    }

    // }}}
    // {{{ setLogging()

   /**
    * Set the logging as specified by the setting
    *
    * @param  int    $type type of log to use
    * @param  string $target target info of log data
    *
    * @access public
    * @return void
    */
    function setLogging($type, $target) 
    {
       // do not allow a change if the class is locked
       if (defined('ERRORHANDLER_LOCKED')) {
          return false;
       }

       switch ($type) {
          case SYSTEM_LOG: 
              ini_set('error_log', 'syslog'); 
              break;
          case FILE_LOG:
              ini_set('error_log', $target); 
              break;
          case MAIL_LOG:   
              ini_set('error_log', ERRORHANDLER_LOG_TARGET); 
              break;
          default:
              // Beware, this is an exit point!
              return; 
       }

       ini_set('log_errors', true);
       $this->_logging[$type] = $target;
    }

    // }}}
    // {{{ setReplace() nd

    function setReplace($flag, $value)
    {
        // do not allow a change if the class is locked
        if (defined('ERRORHANDLER_LOCKED')) {
            return false;
        }

        switch ($flag = strtolower($flag)) {
            case 'page':
            case 'redirect':
                $this->_replace[$flag] = $value; 
                break;
            default:
        }
    }

    // }}}
    // {{{ setSource() nd

    function setSource($flag, $value)
    {
        // do not allow a change if the class is locked
        if (defined('ERRORHANDLER_LOCKED')) {
            return false;
        }

        switch ($flag = strtolower($flag)) {
            case 'lines':
               $this->_source[$flag] = intval($value); 
               break;
            default:
        }
    }

    // }}}
    // {{{ setStyles()

    /**
     * Setup the styles for the console window
     *
     * @param  string $flag .ini flag
     * @param  string $value .ini setting
     *
     * @access public
     * @return void
     */
    function setStyles($flag, $value)
    {
        // do not allow a change if the class is locked
        if (defined('ERRORHANDLER_LOCKED')) {
            return false;
        }

        switch ($flag = strtolower($flag)) {
            case 'console_link_img':
            case 'console_link_text':
            case 'console_link_style':
            case 'body':
            case 'error_level':
            case 'error_message':
                $this->_styles[$flag] = preg_replace(";[\r\n]+;", ' ', $value);
            default:
        }
    }

    // }}}
    // {{{ trap() nd

    function trap($level)
    {
        if ($level === true) {
            $this->_trap_level = $this->_level['DEFAULT'];
        } 
        else {
            $this->_trap_level = $level;
        }

        $this->_trap = array();
    }

    // }}}
    // {{{ isTrapped() nd

    function isTrapped($level = null)
    {
        if (empty($level)) {
            $level = $this->_trap_level;
        }

        $size = count($this->_trap);
        for ($i = 0; $i < $size; $i++) {
           if ($this->_trap[$i]['level'] & $level) {
               return $this->_trap[$i];
           }
        }

        return false;
    }

    // }}}
    // {{{ debug()

    /**
     * User triggered error for debugging a variable
     *
     * This function will trigger an E_DEBUG level error used for outputting the
     * contents of a variable
     *
     * @param  mixed  $variable contents of variable to debug
     * @param  string $name (optional) name of variable, best we can do here
     * @param  string $file_name (optional) name of file
     * @param  int    $line_no (optional) line number of variable
     *
     * @access public
     * @return void
     */
    function debug($variable, $name = '*variable*', $file_name = '*file*', $line_no = '*line*')
    {
        // increment the error count
        $this->_error_count++;

        // reset any locks on the configuration
        $this->_lock_reset();

        // get the message for the debug process
        $message = $this->_message(E_DEBUG, $file_name, $line_no, 'user\'s debug.');

        if (($type = gettype($variable)) == 'object') {
            $var_type = '(object:'.get_class($variable).')';
        }
        elseif ($type == 'resource') {
            $var_type = '(resource:'.get_resource_type($variable).')';
        }
        else {
            $var_type = "($type)";
        }

        $context = $var_type . $name . ' = ' . @var_export($variable, true) . "\n";
        if (get_magic_quotes_gpc()) {
            $context = stripslashes($context);
        }
        $this->_log_all(E_DEBUG, $file_name, $message . $context . "\n");
        $this->_output($message, array(), array('==> CONTEXT REPORT', $context), false);
    }

    // }}}
    // {{{ log() nd

    function log($message, $file_name = '*unspecified* file', $line_no = '*unknown*', $type = null, $target = null)
    {
        // reset any locks on the configuration
        $this->_lock_reset();

        // get the message for the log process, but don't allow html tags
        $message = strip_tags($this->_message(E_LOG, $file_name, $line_no, $message));

        // check if $type and $target points to a valid log destination
        if (is_null($type)) {
            // $this->_level['ALL'] forces logging
            $this->_log_all($this->_level['ALL'], $file_name, $message);
        } 
        else {
            // log to the supplied destination
            $this->_log($message, array($type, $target));
        }
    }

    // }}}
    // {{{ _context() nd

    // create CONTEXT report
    function _context($level, &$var_context, $source)
    {
        // if CONTEXT report should be generated
        if (!($this->_level['CONTEXT'] & $level)) {
            return array('', '');
        }

        $contextHeader = sprintf("==> CONTEXT REPORT (strict: %s, filtered: %s)\n", $this->_context['strict'] ? 'yes' : 'no', $this->_context['exclude'] ? 'yes' : 'no');
        $context = '';
        $var_names = array_keys($var_context);
        if (!empty($this->_context['strict'])) {
            if (!empty($source)) {
                $strict = array();
                // normal variables
                if (preg_match_all('!({)? \$ (?(1) ([\'"])? | ({[\'"])?)  # simple curly syntax
                   (?'.'>([_a-z\x7f-\xff][\w\x7f-\xff]*)) # character class taken from PHP Manual
                   (?(1) (?(2)$2)} | (?(2)))!ix', $source, $matches) ) {
                    $strict = array_unique($matches[4]);
                }
                // variable variables, it's a crude hack (can be fooled quite easily)
                // now, simple variables found ~> check each variable of type string
                // if there is any variable named as its value, respectively
                foreach ($strict as $value) {
                    if (isset($context[$value]) && is_string($context[$value])) {
                        if (in_array($context[$value], $var_names) && !in_array($context[$value], $strict)) {
                            $strict[] = $context[$value];
                        }
                    }
                }
                $var_names = array_intersect($var_names, $strict);
            } 
            else {
                return array($contextHeader, "* no source to extract variables. *\n");
            }
        }

        if (empty($this->_context['exclude'])) {
            foreach ($var_names as $name) {
                if ($name === 'GLOBALS') {
                    $context .= '(array)GLOBALS = **excluded**' . "\n";
                    continue;
                }

                if (($type = gettype($var_context[$name])) == 'object') {
                    $var_type = "(object:".get_class($var_context[$name]).")";
                }
                elseif ($type == 'resource') {
                    $var_type = '(resource:'.get_resource_type($variable).')';
                }
                else {
                    $var_type = "($type)";
                }
                $context .= $var_type . $name . ' = ' . @var_export($var_context[$name], true) . "\n";
            }
        }
        else {
            settype($this->_context['exclude'], 'array');
            foreach ($var_names as $name) {
                if (!in_array($name, $this->_context['exclude'])) {
                    if ($name === 'GLOBALS') {
                        $context .= '(array)GLOBALS = **excluded**' . "\n";
                        continue;
                    }

                    if (($type = gettype($var_context[$name])) == 'object') {
                        $var_type = "(object:".get_class($var_context[$name]).")";
                    }
                    elseif ($type == 'resource') {
                        $var_type = '(resource:'.get_resource_type($variable).')';
                    }
                    else {
                        $var_type = "($type)";
                    }
                    $context .= $var_type . $name . ' = ' . @var_export($var_context[$name], true) . "\n";
                }
            }
        }

        return array($contextHeader, $context);
    }

    // }}}
    // {{{ _handle_error()

    /**
     * Core functionality of the class, handles the error
     *
     * This function handles the error thrown and works to place the messages
     * in the appropriate places
     *
     * @param  int    $level level of the error
     * @param  string $err_str string invoked by error
     * @param  string $file_name where error occured
     * @param  int    $line_no on which error occured
     * @param         $context of the error
     *
     * @access private
     * @return void
     */
    function _handle_error($level, $err_str, $file_name, $line_no, $context)
    {
        // the very first thing to check is if the @ sign was used...if it was
        // used, then error_reporting() will be 0 and we want to not do anything
        if (error_reporting() == 0) {
            return;
        }

        // if this error should be trapped
        if ($this->_trap_level & $level) {
            $this->_trap[] = compact('level', 'err_str', 'file_name', 'line_no');
            return;
        }

        // restore locked settings if needed
        $this->_lock_reset();

        // determine if any kind of report should be generated from this error
        // else we want to just return false
        if (!($this->_level['ALL'] & $level)) {
            return false;
        }

        // split error messages emitted by run-time generated codes
        // (e.g. create_function(), eval(), etc. )
        if (preg_match('!^(.*)\((\d+)\) : (.*)$!U', $file_name, $match)) {
            $file_name = $match[1];
            $line_no   = $match[2];
            $err_str   = $err_str.' in '.$match[3];
        }

        // CUSTOM callback produces the error messages or the built-in or not?
        if (!empty($this->CUSTOM) && is_callable($this->CUSTOM)) {
            $message = call_user_func($this->CUSTOM, $err_str, $level, $file_name, $line_no);
        } 
        else {
            $message = $this->_message($level, $file_name, $line_no, $err_str);
        }

        // generate the source and context debug output
        $source  = $this->_source($level, $file_name, $line_no);
        $context = $this->_context($level, $context, $source[1]);

        // send log to each destination, but don't allow html tags in message
        $this->_log_all($level, $file_name, str_replace("\r", '', strip_tags($message) . implode($source, "\n") . implode($context, '') . "\n"));

        // if the actual error hits the replace error level, then replace the page
        if ($this->_level['REPLACE'] & $level) {
            $this->_replace();
        }
        // if we are using the console, then add to the console buffer
        else if ($this->useConsole) {
            $this->_error_count++;
            $this->_output($message, $source, $context);
        }
    }

    // }}}
    // {{{ _message()

    /**
     * Construct our own error message
     *
     * This function works to construct the error message from the parts
     * provided by the php error callback parameters.  We add some formatting
     * and grammer and return it.
     *
     * @param int    $level level of the error
     * @param string $file_name name of the file
     * @param int    $line_no number line error occured
     * @param string $err_str provided by php
     *
     * @access private
     * @return string message
     */
    function _message($level, $file_name, $line_no, $err_str) 
    {
        $message = '<div style="display: none;">' . date($this->DATEFORMAT) . ' </div>';

        if ($level & E_ERROR_ALL) {
            $message .= '<span class="errorLevel">ERROR</span> ';
        }
        else if ($level & E_WARNING_ALL) {
            $message .= '<span class="errorLevel">WARNING</span> ';
        }
        else if ($level & E_NOTICE_ALL) {
            $message .= '<span class="errorLevel">NOTICE</span> ';
        }
        else if ($level & E_DEBUG) {
            $message .= '<span class="errorLevel">DEBUG</span> ';
        }
        else if ($level & E_LOG) {
            $message .= '<span class="errorLevel">LOG</span> ';
        }

        $message .= sprintf("(0x%02x) in %s on line <span style=\"text-decoration: underline; padding-bottom: 1px; border-bottom: 1px solid black;\">%s</span>\n<span class=\"errorMessage\">%s</span>\n", $level, $file_name, $line_no, $err_str);

        return $message;
    }

    // }}}
    // {{{ _log() nd

    // underlying LOGGING mechanism
    function _log($logmsg, $logging)
    {
        // traverse $logging destination ~> log to each destination
        foreach ($logging as $type => $target) {
            // is the current destination is a mailbox and mail encryption is ON?
            if ($type == MAIL_LOG && is_callable(@$this->_logging['encrypt'])) {
                // call user defined function to encrypt $logmsg
                $logmsg = call_user_func($this->_logging['encrypt'], $logmsg);
                // it is allowed to return with an array, too
                if (is_array($logmsg)) {
                    // 1st element (at index 0) is the enrypted $logmsg,
                    // 2nd element (at index 1) is the additional headers
                    @error_log($logmsg[0], $type, $target, $logmsg[1]);
                } 
                // normal mail will be sent
                else {
                    @error_log($logmsg, $type, $target);
                }
            } 
            else {
                @error_log($logmsg, $type, $target);
            }
        }
    }

    // }}}
    // {{{ _log_all()

    /**
     * Send log string to the approriate log locations
     *
     * All the work has been done up to this point to generate the output,
     * now it is only necessary to record it in places other than the console.
     *
     * @param  int    $level leve of the error
     * @param  string $file_name name of the file to log to
     * @param  string $logstr error message
     *
     * @access private
     * @return void
     */
    function _log_all($level, $file_name, $logstr)
    {
        if ($this->_level['LOGGING'] & $level) {
            $this->_log($logstr, $this->_logging);
        }

        $key = crc32($file_name);
        // if the actual file is registered or not
        if (!empty($this->_altdlog[$key])) {
            // $level must match against 'level' if specified, otherwise DEFAULT level
            if ((isset($this->_level['ALTDLOG'][$key]) && ($this->_level['ALTDLOG'][$key] & $level)) ||
               ($this->_level['DEFAULT'] & $level)) {
                $this->_log($logstr, $this->_altdlog[$key]);
            }
        }
    }

    // }}}
    // {{{ _output()

    /**
     * Output the message to the console
     *
     * This function takes the message source and context and generates html to append
     * the console window for viewing the errors.
     *
     * @param  string $message message that the error generated
     * @param  array  $source section of source code to be highlighted
     * @param  array  $context the variable dumps of the souce code
     * @param  bool   $in_truncateContext (optional) Truncate the context variables?
     *
     * @access private
     * @return void
     */
    function _output($message, $source, $context, $in_truncateContext = true)
    {
        // concat the error message with the prepend and append settings from php.ini
        $report = ini_get('error_prepend_string').nl2br($message).ini_get('error_append_string');

        // opens an output buffer to gather formatted source
        if (!empty($source[0])) {
            $hiSource = @highlight_string($source[1], true);
            $report .= '<div style="margin: 5px 5px 0 5px; font-family: sans-serif; font-size: 10px;">' . $source[0] . '</div><div style="margin: 0 5px 5px 5px; background-color: #EEEEEE; border: 1px dashed #000000;">' . $hiSource . '</div>';
        }

        // opens an output buffer to gather formatted context
        if (!empty($context[0])) {
            $contextArr = explode("\n", $context[1]);

            // truncate the context variable, since some of them are huge
            if ($in_truncateContext && count($contextArr) > $this->numContextLines) {
                array_splice($contextArr, $this->numContextLines);
                $contextArr[] = '--> Variable Truncated due to Excessive Length <--';
            }

            // add php tags so that the highlighting will work
            $output = @highlight_string('<?php' . implode("\n", $contextArr) . '?>', true);
            // strip the php tags
            $output = preg_replace(':<font[^>]*>&lt;\?php</font>:', '', $output);
            $output = preg_replace(':<font[^>]*>\?&gt;</font>:', '', $output);
            $report .= '<div style="margin: 5px 5px 0 5px; font-family: sans-serif; font-size: 10px;">' . $context[0] . '</div><div style="margin: 0px 5px 5px 5px;">'.$output."</div>";
        }

        // append reports to the console buffer as a javascript instruction
        $this->_console .= @sprintf("Error_Handler.document.writeln('<div id=\"error{$this->_error_count}\" style=\"float: right;\">Error Number: {$this->_error_count} | <a href=\"javascript: errorJump({$this->_error_count}, -1);\">prev</a> | <a href=\"javascript: errorJump({$this->_error_count}, 1);\">next</a></div>%s<div style=\"height: 1px; line-height: 1px; background-color: black; border: 0; margin-bottom: 5px;\"><br /></div>');\n", @strtr($report, $this->_escchrs));
    }

    // }}}
    // {{{ _replace() nd

    // handles REPLACE page
    function _replace()
    {
        switch ($this->_replace['redirect']) {
            // attempt to do a header redirect
            case 'server':
                if (!headers_sent()) {
                    header('Location:'. $this->_replace['page']);
                    exit;
                }
            // attempt client-side redirection
            case 'client':
                printf($this->replaceScript, $this->_replace['page']);
                exit;
            // just include the file
            default:
                if (file_exists($this->_replace['page'])) {
                    include($this->_replace['page']);
                } 
                else {
                    $this->_log($this->_replace['page']." not found\n", $this->_logging);
                }
                exit;
        }
    }

    // }}}
    // {{{ _source() nd

    // creates SOURCE report
    // extract from the source file the line which generates the error
    function _source($level, $file_name, $line_no)
    {
        // if SOURCE report should be generated
        if (($this->_level['SOURCE'] & $level) == 0) {
            return array('', '');
        }

        $header = sprintf("==> SOURCE REPORT from %s around line %d\n", $file_name, $line_no);
        if ($line_no == 0) {
            return array($header."\n", '');
        }

        $file = file($file_name, 1);
        $context = $this->_source['lines'] >= 0 ? $this->_source['lines'] : 1;
        $offset  = $line_no-1 >= $context ? $line_no-1 - $context : 0;
        $count   = 2*$context+1;
        $source  = array_slice($file, $offset, $count);
        $count   = count($source);
        $source  = join('', $source);
        return array(sprintf("%s (%d-%d)", $header, $offset+1, $offset+$count), $this->_php_scope($source));
    }

    // }}}
    // {{{ _int_level() nd

    // restore Error_Handlers locked settings
    function _int_level($value)
    {
        $value = trim($value);
        if (is_string($value)) {
            if (defined($value)) {
                $value = constant($value);
            }
            else if (preg_match('!^0x[\da-f]+!i', $value)) {
                $value = hexdec($value);
            }
            else if (preg_match('!^0[0-7]+!i', $value)) {
                $value = octdec($value);
            }
        }

        return intval($value);
    }

    // }}}
    // {{{ _lock_reset() nd

    function _lock_reset() 
    {
        if (defined('ERRORHANDLER_LOCKED')) {
            foreach (@unserialize(ERRORHANDLER_LOCKED) as $property => $value) {
                $this->$property = $value;
            }
        }
    }

    // }}}
    // {{{ _php_scope()

    /**
     * Assure the the php code has start and end tags for the highlighting
     *
     * We need to make sure that the source code will be recognized by the highlighter
     * so we need to take what we cut and then put on the appropriate php tags
     *
     * @param string $source php source code
     *
     * @access private
     * @return string repaired php source code
     */
    function _php_scope($source) 
    {
        $opentag  = '<\?php';
        $closetag = '\?>';

        $closepos = preg_match("!($closetag)!", $source, $match) ? strpos($source, $match[0]): false;
        if (preg_match("!($opentag)!", $source, $match)) {
            $openpos1 = strpos($source, $match[1]);
            $openpos2 = strpos($source, $match[1], $closepos);
            if ($openpos1 > $closepos) {
                $source = "<?php\n".$source;
            }
        } 
        else {
            $openpos1 = $openpos2 = false;
            $source   = "<?php\n".$source;
        }

        if ($openpos2 > $closepos || $closepos === false) {
            $source  .= "?>\n";
        }

        return $source;
    }

    // }}}
}
// {{{ errorhandler_callback

/**
 * Find an open instance of Error_Handler and run callback
 *
 * This function determines if an Error_Handler instance has been set and
 * if so, uses that instance to run the callback function.  If it does not
 * find one then it restores the error handler and uses that function.  If
 * called with no arguments it just returns the open instance or false if
 * one does not exists.
 *
 * @access public
 * @return mixed either an open instance if called with no args or boolean success
 */
function &errorhandler_callback() 
{
    static $instance, $exists;

    // try to find an instance of errorHandler, we really have to do
    // this under the circumstances
    if (empty($instance)) {
        foreach($GLOBALS as $key => $value) {
            if (is_a($value, 'error_handler')) {
                $instance = $key;
                $exists = true; 
                break;
            }
        }
    }

    // if no args where passed, return the open instance or false if it doesn't exist
    if (func_num_args() == 0) {
        return !empty($exists) ? $GLOBALS[$instance] : false;
    }
    elseif (func_num_args() != 5) {
        return;
    }
    else {
        $args = func_get_args();
        if (!empty($exists)) {
            call_user_func_array(array(&$GLOBALS[$instance], '_handle_error'), $args);
            return true;
        }
        else { 
            restore_error_handler();
            if (function_exists(OLD_ERROR_HANDLER) ){
                call_user_func_array(OLD_ERROR_HANDLER, $args);
            }
            return false;
        }
    }
}

// }}}
// {{{ registration

// store the default error handler
define ('OLD_ERROR_HANDLER', set_error_handler('errorhandler_callback'));
// allow Error_Handler to display all errors
ini_set('display_errors', false);

// make PEAR give us errors
function pear_error_handler($errobj)
{
    trigger_error($errobj->getMessage() . '<br />' . htmlspecialchars($errobj->getDebugInfo()), $errobj->level);
}

PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'pear_error_handler');

// }}}
?>
