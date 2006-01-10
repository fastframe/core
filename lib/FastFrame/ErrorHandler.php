<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2005 The Codejanitor Group                        |
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
// |          Dan Allen <dan@mojavelinux.com>                             |
// +----------------------------------------------------------------------+

// }}}
// {{{ constants

define('E_USER_ALL', E_USER_NOTICE | E_USER_WARNING | E_USER_ERROR);
define('E_NOTICE_ALL', E_NOTICE | E_USER_NOTICE);
define('E_WARNING_ALL', E_WARNING | E_USER_WARNING | E_CORE_WARNING | E_COMPILE_WARNING);
define('E_ERROR_ALL', E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);
define('E_NOTICE_NONE', E_ALL & ~E_NOTICE_ALL);
define('E_DEBUG', 0x10000000);
define('E_VERY_ALL', E_ERROR_ALL | E_WARNING_ALL | E_NOTICE_ALL | E_DEBUG);

define('SYSTEM_LOG', 0);
define('TCP_LOG',    2);
define('MAIL_LOG',   1);
define('FILE_LOG',   3);

// }}}
// {{{ class FF_ErrorHandler

/**
 * The FF_ErrorHandler is a class that handles all the errors (and debug
 * statements) in FastFrame.  It has several "reporters" so that errors
 * can be logged to different locations such as a log file, the browser,
 * or an email.
 *
 * @version Revision: 2.0 
 * @author  Jason Rust <jrust@codejanitor.com>
 * @author  Dan Allen <dan@mojavelinux.com>
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_ErrorHandler {
    // {{{ properties

    /**
     * The list of errors that occur
     * @var array
     */
    var $errorList = array();

    /**
     * The array of errors that will be displayed via console
     * @var array
     */
    var $consoleErrors = array();

    /**
     * The array of errors that will be sent via email
     * @var array
     */
    var $mailErrors = array();

    /**
     * The available reporters
     * @var array
     */
    var $reporters = array(
            'mail'      => array('level' => 0, 'data' => null),
            'file'      => array('level' => 0, 'data' => null),
            'console'   => array('level' => 0, 'data' => null),
            'stdout'    => array('level' => 0, 'data' => null),
            'system'    => array('level' => 0, 'data' => null),
            'redirect'  => array('level' => 0, 'data' => null),
            'browser'   => array('level' => 0, 'data' => null));
  
    /**
     * The url we hyperlink filenames to
     * @var string
     */
    var $fileUrl = '';

    /**
     * The number of context lines to display
     * @var int
     */
    var $contextLines = 5;

    /**
     * Should we only show variables that are in the context as defined
     * by contextLines?
     * @var bool
     */
    var $strictContext = true;

    /**
     * The date format
     * @var string
     */
    var $dateFormat = '[Y-m-d H:i:s]';

    /**
     * List of classes to exclude from the error dump
     * @var array
     */
    var $classExcludeList = array();

    /**
     * Should objects be excluded from the context dumps?
     * @var bool
     */
    var $excludeObjects = true;

    /**
     * An array of templates used for displaying errors
     * @var array
     */
    var $templates = array(
            'message' => '<b>%date%</b> <span class="errorLevel">[%errorLevel%]</span> in %file% on line <span style="text-decoration: underline; padding-bottom: 1px; border-bottom: 1px solid black;">%line%</span>
<div class="errorMessage">%errorMessage%</div>',
            'header' => '<div id="%id%" style="border: thin solid #000; background-color: #eee; margin: 5px 5px 2px 5px; font-weight: bold;">==> %header%</div>',
            'variable' => '<div style="margin: 0px 5px 5px 5px;">%variable%</div>',
            'backtrace' => '<div style="margin: 0px 5px 5px 5px;"><b>%num%</b>. Function %func% called in %file% on line %line%</div>',
            'sourceMessage' => 'Source report from %file% around line %line% (%ctxStart% - %ctxEnd%)',
            'source' => '<div style="margin: 0 5px 5px 5px; background-color: #eee; border: 1px dashed #000;">%source%</div>');

    // }}}
    // {{{ __constructor()

    function FF_ErrorHandler()
    {
        error_reporting(E_ALL);
        // Trick to fix broken set_error_handler() function in php
        $GLOBALS['_o_error_reporter'] =& $this; 
        trapError('_o_error_reporter');
        set_error_handler('trapError');
        register_shutdown_function(array(&$this, '__destructor'));
    }
    
    // }}}
    // {{{ __destructor()

    function __destructor()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        // Process errors
        foreach ($this->errorList as $a_error) {
            $this->_processError($a_error);
        }

        // Handle those reporters that handle their errors as a batch
        if (count($this->consoleErrors)) {
            $a_errors =& $this->consoleErrors;
            include dirname(__FILE__) . '/ErrorHandler/error.tpl.php';
        }

        if (count($this->mailErrors)) {
            $msg = _('User Information:') . "\n";
            $msg .= "\t" . _('Username') . ': ' . FF_Auth::getCredential('username') . "\n"; 
            $msg .= "\tSERVER_NAME: " . $_SERVER['SERVER_NAME'] . "\n";
            $msg .= "\tREQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
            $msg .= "\tHTTP_REFERER: " . $_SERVER['HTTP_REFERER'] . "\n";
            $msg .= "\tHTTP_USER_AGENT: " . $_SERVER['HTTP_USER_AGENT'] . "\n\n";
            $msg .= implode(str_repeat('-=', 50) . "\n\n", $this->mailErrors);
            @error_log($this->_cleanMessage($msg), MAIL_LOG, $this->reporters['mail']['data']);
        }
    }

    // }}}
    // {{{ setTemplate()

    /**
     * Sets one of the templates.
     * 
     * @param string $in_name The name of the template (message,
     *        header, variable, backtrace, sourceMessage, source)
     * @param string $in_template The template string
     *
     * @access public
     * @return void
     */
    function setTemplate($in_name, $in_template)
    {
        $this->templates[$in_name] = $in_template;
    }

    // }}}
    // {{{ setFileUrl()

    /**
     * Sets the file url.  Keywords that are replaced are %file% and
     * %line% in the url.  Example would be file:///%file%#%line%
     *
     * @param string $in_value The url value
     *
     * @access public
     * @return void
     */
    function setFileUrl($in_value)
    {
        $this->fileUrl = $in_value;
    }

    // }}}
    // {{{ setDateFormat()

    function setDateFormat($in_format)
    {
        $this->dateFormat = $in_format;
    }

    // }}}
    // {{{ setContextLines()

    function setContextLines($in_lines)
    {
        $this->contextLines = intval($lines);
    }

    // }}}
    // {{{ setStrictContext()

    function setStrictContext($bool)
    {
        $this->strictContext = $bool ? true : false;
    }

    // }}}
    // {{{ setExcludeObjects()

    /**
     * A list of objects to exclude from the context error report.
     * Arguments can be a list of classes or a boolean telling whether
     * or not to exclude objects.
     *
     * @access public
     * @return void
     */
    function setExcludeObjects()
    {
        if (gettype(func_get_arg(0)) == 'boolean') {
            $this->excludeObjects = func_get_arg(0);
        }
        else {
            $list = func_get_args();
            $this->classExcludeList = array_map('strtolower', $list);
        }
    }

    // }}}
    // {{{ addReporter()

    /**
     * Adds a reporter.
     *
     * @param string $in_reporter The reporter name (must be one of the
     *        valid reporters)
     * @param int $in_level The debug level at which this reporter is
     *        active.
     * @param array $in_data (optional) An array of any needed data for
     *        the reporter.
     *
     * @access public
     * @return void
     */
    function addReporter($in_reporter, $in_level, $in_data = null)
    {
        $this->reporters[$in_reporter] = array('level' => $in_level, 'data' => $in_data); 
    }

    // }}}
    // {{{ addError()

    /**
     * Adds an error to the stack
     *
     * @param array $in_error The error
     *
     * @access public
     * @return void
     */
    function addError($in_error)
    {
        // rearrange for eval'd code or create function errors
        if (preg_match(';^(.*?)\((\d+)\) : (.*?)$;', $in_error['file'], $matches)) {
            $in_error['message'] .= ' on line ' . $in_error['line'] . ' in ' . $matches[3];
            $in_error['file'] = $matches[1];
            $in_error['line'] = $matches[2];
        }

        $in_error['date'] = date($this->dateFormat);
        $in_error['context'] = $this->_getContext($in_error['file'], $in_error['line']);
        if (function_exists('debug_backtrace')) {
            $in_error['backtrace'] = debug_backtrace();
        }
        else {
            $in_error['backtrace'] = array();
        }

        $this->errorList[] = $in_error;
        // E_USER_ERROR are showstoppers
        if ($in_error['level'] == E_USER_ERROR) {
            $this->_fatal($in_error);
        }
    }

    // }}}
    // {{{ debug()

    /**
     * Adds a debug error to the error stack.
     *
     * @param string $in_variable The variable to debug
     * @param string $in_name (optional) The variable name
     * @param int $in_line (optional) The line number
     * @param string $in_file (optional) The file name
     * @param int $in_level (optional) At what debug level to display the debug
     *
     * @access public
     * @return void
     */
    function debug($in_variable, $in_name = '*variable*', $in_line = '*line*', $in_file = '*file*', $in_level = E_DEBUG)
    {
        $a_error = array('level' => intval($in_level), 
                'message' => 'user variable debug: $' . $in_name,
                'file' => $in_file, 'line' => $in_line,
                'variables' => array($in_name => $in_variable), 'signature' => mt_rand());
        $this->addError($a_error);
    }

    // }}}
    // {{{ _fatal()

    /**
     * Called when a fatal error occurs.  Shows a minimal amount of
     * information so as not to be a security risk.  The error is still
     * processed via the other reporters, so details can be logged via
     * that mechanism.
     *
     * @access private
     * @return void
     */
    function _fatal($in_error)
    {
        echo '<html><body>';
        echo '<style>.errorBox { background-color: #ccc; border: thin dashed #000; font-weight: bold; text-align: center; } .errorMessage { color: red; } .errorNotice { margin-bottom: 10px; color: green; }</style>';
        echo '<div class="errorBox">';
        echo '<div class="errorNotice">' . _('A fatal error has occured') . '</div>';
        echo '<div class="errorMessage">' . $in_error['message'] . '</div>';
        if ($this->reporters['mail']['level'] & $in_error['level'] ||
            $this->reporters['file']['level'] & $in_error['level'] ||
            $this->reporters['system']['level'] & $in_error['level']) {
            echo '<div style="margin-top: 10px;">' . _('The details have been logged for the administrator') . '</div>';
        }

        echo '</div>';
        echo '</body></html>';
        exit;
    }

    // }}}
    // {{{ _processError()

    /**
     * Processes an error for each reporter.
     *
     * @var array $in_error The error array
     *
     * @access private
     * @return void
     */
    function _processError($in_error)
    {
        $message = $this->_getMessage($in_error);

        // syslog
        if ($this->reporters['system']['level'] & $in_error['level']) {
            @error_log(str_replace("\n", '', strip_tags($message)) . "\n", SYSTEM_LOG);
        }

        // file
        if ($this->reporters['file']['level'] & $in_error['level']) {
            @error_log(str_replace("\n", ' ', strip_tags($message)) . "\n", FILE_LOG, $this->reporters['file']['data']);
        }

        // stdout
        if ($this->reporters['stdout']['level'] & $in_error['level']) {
            echo $this->_cleanMessage($message . $this->_getDetails($in_error));
        }

        // redirect
        if ($this->reporters['redirect']['level'] & $in_error['level']) {
            echo '<script type="text/javascript">window.location.href = \'' . $this->reporters['redirect']['data'] . '\';</script>';
            exit;
        }

        // email
        if ($this->reporters['mail']['level'] & $in_error['level']) {
            $this->mailErrors[] = $message . $this->_getDetails($in_error);
        }
        
        // browser
        if ($this->reporters['browser']['level'] & $in_error['level']) {
            echo $message . $this->_getDetails($in_error);
        }

        // console
        if ($this->reporters['console']['level'] & $in_error['level']) {
            $this->consoleErrors[] = $this->_makeStringJSSafe($message . $this->_getDetails($in_error));
        }
    }

    // }}}
    // {{{ _getMessage()

    /**
     * Gets the message for an error.
     *
     * @access private
     * @return string The error message, formatted nicely.
     */
    function _getMessage($in_error)
    {
        if ($in_error['level'] & E_ERROR_ALL) {
            $tmp_level = 'error';
        }
        elseif ($in_error['level'] & E_WARNING_ALL) {
            $tmp_level = 'warning';
        }
        elseif ($in_error['level'] & E_NOTICE_ALL) {
            $tmp_level = 'notice';
        }
        elseif ($in_error['level'] & E_DEBUG) {
            $tmp_level = 'debug';
        }
        elseif ($in_error['level'] & E_LOG) {
            $tmp_level = 'log';
        }
        else {
            $tmp_level = 'unknown';
        }

        $a_replace = array('%date%' => $in_error['date'], '%errorLevel%' => $tmp_level, 
                '%file%' => $this->_getFileLink($in_error['file'], $in_error['line']), 
                '%line%' => $in_error['line'], '%errorMessage%' => $in_error['message']);
        return str_replace(array_keys($a_replace), $a_replace, $this->templates['message']);
    }

    // }}}
    // {{{ _getDetails()

    /**
     * Gets the details of a message, such as the variable context
     *
     * @param array $in_error The error array 
     *
     * @access public
     * @return string The html of any details of the error
     */
    function _getDetails($in_error)
    {
        $a_replace = array('%file%' => $this->_getFileLink($in_error['file'], $in_error['line']), 
                '%line%' => $in_error['line'], '%ctxStart%' => $in_error['context']['start'], 
                '%ctxEnd%' => $in_error['context']['end']);
        $s_message = str_replace(array_keys($a_replace), $a_replace, $this->templates['sourceMessage']);
        $a_replace = array('%header%' => $s_message, '%id%' => 'src_' . $in_error['signature']);
        $s_message = "\n" . str_replace(array_keys($a_replace), $a_replace, $this->templates['header']);
        // Highlight source and highlight the line with the problem
        $s_source = preg_replace("/(.*)(<font color.*?>{$in_error['line']}<\/font><.*?>:.*?<br \/>)/", 
                '\\1<span style="background-color: #efbfbf;">\\2</span>', 
                @highlight_string(stripslashes($in_error['context']['source']), true));
        $s_message .= str_replace('%source%', str_replace('  ', '&nbsp; ', str_replace('&nbsp;', ' ', $s_source)), $this->templates['source']);
        $s_message .= $this->_getBacktrace($in_error);
        $variables = $this->_exportVariables($in_error['variables'], $in_error['context']['variables'], $in_error['level']);
        if ($variables !== false) {
            $variables = @highlight_string(stripslashes('<?php' . $variables . '?>'), true);
            // strip the php tags
            $variables = preg_replace(':(&lt;\?php(<br />)*|\?&gt;):', '', $variables);
            $a_replace = array('%header%' => 'Variable scope report', '%id%' => 'var_' . $in_error['signature']);
            $s_message .= str_replace(array_keys($a_replace), $a_replace, $this->templates['header']);
            $s_message .= str_replace('%variable%', $variables, $this->templates['variable']);
        }
        
        return $s_message;
    }

    // }}}
    // {{{ _getBacktrace()

    /**
     * Gets the backtrace details of the error.
     *
     * @param array $in_error The error
     *
     * @access private
     * @return string The backtrace list.
     */
    function _getBacktrace($in_error)
    {
        // First two are always from the error handler.
        @array_shift($in_error['backtrace']);
        @array_shift($in_error['backtrace']);
        $s_message = '';
        if (count($in_error['backtrace'])) {
            $in_error['backtrace'] = array_reverse($in_error['backtrace']);
            $a_replace = array('%header%' => 'Backtrace report', '%id%' => 'bt_' . $in_error['signature']);
            $s_message = str_replace(array_keys($a_replace), $a_replace, $this->templates['header']);
            // All backtraces need to be in one big div for console js to work
            $s_message .= '<div>' . "\n";
            foreach ($in_error['backtrace'] as $k => $a_trace) {
                if (isset($a_trace['class'])) {
                    $a_trace['function'] = $a_trace['class'] . $a_trace['type'] . $a_trace['function'];
                }

                $a_trace['function'] = '<span style="color: #0000cc;">' . $a_trace['function'] . '</span>';
                $a_trace['function'] .= '<span style="color: #006600;">(</span>';
                $a_trace['function'] .= isset($a_trace['args']) ? '<span style="color: #cc0000;">' . implode('</span>, <span style="color: #cc0000;">', $a_trace['args']) . '</span>' : '';
                $a_trace['function'] .= '<span style="color: #006600;">)</span>';

                $a_replace = array('%num%' => $k + 1, '%func%' => $a_trace['function'], 
                        '%line%' => $a_trace['line'], 
                        '%file%' => $this->_getFileLink($a_trace['file'], $a_trace['line']));
                $s_message .= str_replace(array_keys($a_replace), $a_replace, $this->templates['backtrace']) . "\n";
            }

            $s_message .= "</div>\n";
        }

        return $s_message;
    }

    // }}}
    // {{{ _getContext()

    function _getContext($file, $line)
    {
        if (!@is_readable($file)) {
            return array('start' => 0, 'end' => 0, 'source' => '', 'variables' => array());
        }

        $fp = fopen($file, 'r');
        $start = max($line - $this->contextLines, 0);
        $end = $line + $this->contextLines;
        $lineNum = 0;
        $source = '';
        // Get context lines
        while ($lineNum < $end) {
            $lineNum++;
            if ($lineNum < $start) {
                fgets($fp, 4096);
            }
            else {
                $data = fgets($fp, 4096);
                if (feof($fp)) {
                    break;
                }

                $source .= $lineNum . ': ' . $data; 
            }
        }

        fclose($fp);
        $source = $this->_addPhpTags($source);
        preg_match_all(';\$([[:alnum:]_]+);', $source, $matches);
        $variables = array_values(array_unique($matches[1]));
        return array('start' => $start + 1, 'end' => $lineNum,
            'source' => $source, 'variables' => $variables);
    }

    // }}}
    // {{{ _makeStringJSSafe()

    /**
     * Makes a string safe for being in a javascript string
     *
     * @param string $in_data The data to clean
     *
     * @access private
     * @return string The cleaned up string
     */
    function _makeStringJSSafe($in_data)
    {
        return strtr($in_data, array("\t" => '\\t', "\n" => '\\n', "\r" => '\\r', '\\' => '&#092;', "'" => '&#39;'));
    }

    // }}}
    // {{{ _exportVariables()

    function _exportVariables(&$variables, $contextVariables, $level)
    {
        $variableString = '';
        foreach ($variables as $name => $contents) {
            // If we are using strict context and this variable is not in the context, skip it
            // When debugging always show the variable (this allows us to catch constants)
            if (!($level & E_DEBUG) && $this->strictContext && !in_array($name, $contextVariables)) {
                continue;
            }

            // if this is an object and the class is in the exclude list, skip it
            if (is_object($contents) && in_array(get_class($contents), $this->classExcludeList)) {
                continue;
            }

            $variableString .= '$' . $name . ' = ' . $this->_var_export($contents) . ';' . "\n";
        }

        if (empty($variableString)) {
            return false;
        }
        else {
            return "\n" . $variableString;
        }
    }

    // }}}
    // {{{ _strrpos2() 

    function _strrpos2($string, $needle, $offset = 0)
    {
        $addLen = strlen($needle);
        $endPos = $offset - $addLen;

        while (1) {
            if (($newPos = strpos($string, $needle, $endPos + $addLen)) === false) {
                break;
            }

            $endPos = $newPos;
        }

        return ($endPos >= 0) ? $endPos : false;
    }

    // }}}
    // {{{ _addPhpTags()

    function _addPhpTags($source)
    {
        $startTag  = '<?php';
        $endTag = '?>';

        $firstStartPos  = ($pos = strpos($source, $startTag)) !== false ? $pos : -1;
        $firstEndPos = ($pos = strpos($source, $endTag)) !== false ? $pos : -1;
        
        // no tags found then it must be solid php since html can't throw a php error
        if ($firstStartPos < 0 && $firstEndPos < 0) {
            return $startTag . "\n" . $source . "\n" . $endTag;
        }

        // found an end tag first, so we are missing a start tag
        if ($firstEndPos >= 0 && ($firstStartPos < 0 || $firstStartPos > $firstEndPos)) {
            $source = $startTag . "\n" . $source;
        }

        $sourceLength = strlen($source);
        $lastStartPos  = ($pos = $this->_strrpos2($source, $startTag)) !== false ? $pos : $sourceLength + 1;
        $lastEndPos  = ($pos = $this->_strrpos2($source, $endTag)) !== false ? $pos : $sourceLength + 1;

        // See if we need to add an end tag
        if ($lastEndPos < $lastStartPos || ($lastEndPos > $lastStartPos && $lastEndPos > $sourceLength)) {
            $source .= $endTag;
        }

        return $source;
    }

    // }}}
    // {{{ _var_export()

    function _var_export(&$variable, $arrayIndent = '', $inArray = false, $level = 0)
    {
        static $maxLevels = 0, $followObjectReferences = false;
        if ($inArray != false) {
            $leadingSpace = '';
            $trailingSpace = ',' . "\n";
        }
        else {
            $leadingSpace = $arrayIndent;
            $trailingSpace = '';
        }

        $result = '';
        switch (gettype($variable)) {
            case 'object':
                if ($inArray && !$followObjectReferences) {
                    $result = '*OBJECT REFERENCE*';
                    $trailingSpace = "\n";
                    break;
                }
            case 'array':
                if ($maxLevels && $level >= $maxLevels) {
                    $result = '** truncated, too much recursion **';
                }
                else {
                    $result = "\n" . $arrayIndent . 'array (' . "\n";
                    foreach ($variable as $key => $value) {
                        $result .= $arrayIndent . '  ' . (is_int($key) ? $key : ('\'' . str_replace('\'', '\\\'', $key) . '\'')) . ' => ' . $this->_var_export($value, $arrayIndent . '  ', true, $level + 1);
                     }

                    $result .= $arrayIndent . ')';
                }
            break;
            case 'string':
                $result = '\'' . str_replace('\'', '\\\'', $variable) . '\'';
            break;
            case 'boolean':
                $result = $variable ? 'true' : 'false';
            break;
            case 'NULL':
                $result = 'NULL';
            break;
            case 'resource':
                $result = get_resource_type($variable);
            break;
            default:
                $result = $variable;
            break;
        }

        return $leadingSpace . $result . $trailingSpace;
    }

    // }}}
    // {{{ _cleanMessage()

    function _cleanMessage($in_msg)
    {
        $in_msg = str_replace('<br />', "\n", $in_msg);
        $in_msg = str_replace('&nbsp;', ' ', $in_msg);
        $in_msg = strip_tags($in_msg);
        $in_msg = str_replace(get_html_translation_table(HTML_ENTITIES), array_keys(get_html_translation_table(HTML_ENTITIES)), $in_msg);
        return $in_msg;
    }

    // }}}
    // {{{ _getFileLink()

    function _getFileLink($in_file, $in_line)
    {
        if (!empty($this->fileUrl)) {
            $a_replace = array('%file%' => $in_file, '%line%' => $in_line);
            return '<a href="' . str_replace(array_keys($a_replace), $a_replace, $this->fileUrl) . 
                '" target="_blank">' .  $in_file . '</a>';
        }
        else {
            return $in_file;
        }
    }

    // }}}
}

// {{{ trapError()

function trapError()
{
    static $variable, $signatures = array();

    if (!isset($prependString) || !isset($appendString)) {
        $prependString = ini_get('error_prepend_string');
        $appendString = ini_get('error_append_string');
    }

    // error event has been caught
    if (func_num_args() == 5) {
        // silenced error (using @)
        if (error_reporting() == 0) {
            return;
        }
        
        $args = func_get_args();

        // Weed out duplicate errors (coming from same line and file)
        $signature = md5($args[1] . ':' . $args[2] . ':' . $args[3]);
        if (isset($signatures[$signature])) {
            return;
        }
        else {
            $signatures[$signature] = true;
        }

        // Cut out the fat from the variable context (we get back a lot of junk)
        $variables =& $args[4];
        $variablesFiltered = array();
        $excludeObjects = $GLOBALS[$variable]->excludeObjects;
        foreach (array_keys($variables) as $variableName) {
            // These are server variables most likely
            if ($variableName == strtoupper($variableName) ||
                // Private/Super-Global vars
                $variableName{0} == '_' ||
                // Passed in vars
                $variableName == 'argv' || $variableName == 'argc' ||
                // See if objects should be excluded
                ($GLOBALS[$variable]->excludeObjects && gettype($variables[$variableName]) == 'object') ||
                // Don't allow instance of errorstack to come through
                is_a($variables[$variableName], 'FF_ErrorHandler')) {
                continue;
            }
            
            // Make a copy to preserve the state at time of error
            $variablesFiltered[$variableName] = $variables[$variableName];
        }

        $a_error = array('level' => $args[0],
            'message' => $prependString . $args[1] . $appendString,
            'file' => $args[2], 'line' => $args[3],
            'variables' => $variablesFiltered, 'signature' => $signature);
        $GLOBALS[$variable]->addError($a_error);
    }
    // If only one arg this is the initialization of this function
    elseif (func_num_args() == 1) {
        $variable = func_get_arg(0);
    }
    else {
        return $variable;
    }
}

// }}}
?>
