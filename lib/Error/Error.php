<?php
// {{{ includes

require_once ECLIPSE_ROOT . 'Loop.php';
require_once ECLIPSE_ROOT . 'ArrayList.php';

// }}}
// {{{ constants

define('E_USER_ALL',	E_USER_NOTICE | E_USER_WARNING | E_USER_ERROR);
define('E_NOTICE_ALL',	E_NOTICE | E_USER_NOTICE);
define('E_WARNING_ALL',	E_WARNING | E_USER_WARNING | E_CORE_WARNING | E_COMPILE_WARNING);
define('E_ERROR_ALL',	E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);
define('E_NOTICE_NONE',	E_ALL & ~E_NOTICE_ALL);
define('E_DEBUG',		0x10000000);
define('E_VERY_ALL',	E_ERROR_ALL | E_WARNING_ALL | E_NOTICE_ALL | E_DEBUG);

define('SYSTEM_LOG',	0);
define('TCP_LOG',		2);
define('MAIL_LOG',		1);
define('FILE_LOG',		3);

// }}}
// {{{ helper functions

function strrpos2($string, $needle, $offset = 0)
{
	$addLen = strlen($needle);
	$endPos = $offset - $addLen;

	while (1)
	{
		if (($newPos = strpos($string, $needle, $endPos + $addLen)) === false) {
			break;
		}

		$endPos = $newPos;
	}

	return ($endPos >= 0) ? $endPos : false;
}

function addPhpTags($source)
{
	$startTag  = '<?php';
	$endTag = '?>';

	$firstStartPos  = ($pos = strpos($source, $startTag)) !== false ? $pos : -1;
	$firstEndPos = ($pos = strpos($source, $endTag)) !== false ? $pos : -1;
	
	// no tags found then it must be solid php since html can't throw a php error
	if ($firstStartPos < 0 && $firstEndPos < 0)
	{
		return $startTag . "\n" . $source . "\n" . $endTag;
	}

	// found an end tag first, so we are missing a start tag
	if ($firstEndPos >= 0 && ($firstStartPos < 0 || $firstStartPos > $firstEndPos))
	{
		$source = $startTag . "\n" . $source;
	}

	$sourceLength = strlen($source);
	$lastStartPos  = ($pos = strrpos2($source, $startTag)) !== false ? $pos : $sourceLength + 1;
	$lastEndPos  = ($pos = strrpos2($source, $endTag)) !== false ? $pos : $sourceLength + 1;

	if ($lastEndPos < $lastStartPos || ($lastEndPos > $lastStartPos && $lastEndPos > $sourceLength))
	{
		$source .= $endTag;
	}

	return $source;
}

function &_var_export2(&$variable, $arrayIndent = '', $inArray = false, $level = 0)
{
	static $maxLevels = 0, $followObjectReferences = false;
	if ($inArray != false)
	{
		$leadingSpace = '';
		$trailingSpace = ',' . "\n";
	}
	else
	{
		$leadingSpace = $arrayIndent;
		$trailingSpace = '';
	}

	$result = '';
	switch (gettype($variable))
	{
		case 'object':
			if ($inArray && !$followObjectReferences)
			{
				$result = '*OBJECT REFERENCE*';
				$trailingSpace = "\n";
				break;
			}
		case 'array':
			if ($maxLevels && $level >= $maxLevels)
			{
				$result = '** truncated, too much recursion **';
			}
			else
			{
				$result = "\n" . $arrayIndent . 'array (' . "\n";
				foreach ($variable as $key => $value)
				{
					$result .= $arrayIndent . '  ' . (is_int($key) ? $key : ('\'' . str_replace('\'', '\\\'', $key) . '\'')) . ' => ' . _var_export2($value, $arrayIndent . '  ', true, $level + 1);
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

function var_export2(&$variable, $return = false)
{
	$result =& _var_export2($variable);
	if ($return)
	{
		return $result;
	}
	else
	{
		echo $result;
	}
}

// }}}

class ErrorList extends ArrayList
{
	// {{{ __constructor()

	function ErrorList(&$reporter, $variableName = 'error')
	{
		error_reporting(E_ALL);
		// :NOTE: dallen 2003/01/31 it might be a good idea to keep this on
		// if the console is used since some cases don't stop E_ERROR
        // :WARNING: jrust 2003/02/10 This makes it so we can't see parse/fatal errors
		// ini_set('display_errors', false);

		parent::ArrayList();
		$this->reporter =& $reporter;

		// trick to fix broken set_error_handler() function in php
		$GLOBALS[$variableName] =& $this; 
		trapError($variableName);
		set_error_handler('trapError');
		
		register_shutdown_function(array(&$this, '__destructor'));
	}
	
	// }}}
	// {{{ __destructor()

	function __destructor()
	{
		error_reporting(E_ALL ^ E_NOTICE);
		Loop::run(new ErrorIterator($this), $this->reporter);
	}

	// }}}
	// {{{ singleton()

	function &singleton(&$reporter, $variableName = 'error')
	{
		static $instance = 0;
		
		if ($instance == 0)
		{
			$instance =& new ErrorList($reporter, $variableName);
		}
		
		return $instance;
	}

	// }}}
	// {{{ add()

	function add(&$error)
	{
		// rearrange for eval'd code or create function errors
		if (preg_match(';^(.*?)\((\d+)\) : (.*?)$;', $error['file'], $matches))
		{
			$error['message'] .= ' on line ' . $error['line'] . ' in ' . $matches[3];
			$error['file'] = $matches[1];
			$error['line'] = $matches[2];
		}

		$error['context'] = $this->_getContext($error['file'], $error['line']);

	    parent::add($error);
	}

	// }}}
	// {{{ debug()

    function debug($variable, $name = '*variable*', $line = '*line*', $file = '*file*', $level = E_DEBUG)
	{
		$error = array(
			'level'		=> intval($level),
			'message'	=> 'user variable debug',
			'file'		=> $file,
			'line'		=> $line,
			'variables' => array($name => $variable),
			'signature'	=> mt_rand(),
		);
	     
		$this->add($error);
	}

	// }}}
	// {{{ _getContext()

	function _getContext($file, $line)
	{
		if (!@is_readable($file)) {
		    return array(
		    	'start'		=> 0,
		    	'end'		=> 0,
		    	'source'	=> '',
		    	'variables'	=> array(),
		    );
        }

		$sourceLines = file($file);
		$offset = max($line - 1 - $this->reporter->contextLines, 0);
		$numLines = 2 * $this->reporter->contextLines + 1;
		$sourceLines = array_slice($sourceLines, $offset, $numLines);
		$numLines = count($sourceLines);
		// add line numbers
		foreach ($sourceLines as $index => $line)
		{
			$sourceLines[$index] = ($offset + $index + 1)  . ': ' . $line;
		}
	
		$source = addPhpTags(join('', $sourceLines));
		preg_match_all(';\$([[:alnum:]]+);', $source, $matches);
		$variables = array_values(array_unique($matches[1]));
		return array(
			'start'		=> $offset + 1,
			'end'		=> $offset + $numLines,
			'source'	=> $source,
			'variables'	=> $variables,
		);
	}

	// }}}
}

class ErrorIterator /*extends Iterator*/
{
	// {{{ properties

	var $errorList;

	var $index;

	// }}}
	// {{{ __constructor()

	function ErrorIterator(&$errorList)
	{
		$this->errorList =& $errorList;
		$this->reset();
	}
	
	// }}}
	// {{{ reset()

	function reset()
	{
		$this->index = 0;
	}

	// }}}
	// {{{ next()

	function next()
	{
		$this->index++;
	}

	// }}}
	// {{{ isValid()

	function isValid()
	{
		return ($this->index < $this->errorList->size());
	}

	// }}}
	// {{{ getCurrent()

	function &getCurrent()
	{
		return $this->errorList->get($this->index);
	}
	
	// }}}
}

class ErrorReporter /*extends LoopManipulator*/
{
	// {{{ properties

	var $errorList;

	var $reports;
  
	var $contextLines;

	var $strictContext;

	var $dateFormat;

	var $classExcludeList;

    var $excludeObjects;

	// }}}
	// {{{ __constructor

	function ErrorReporter()
	{
		$this->errorList = array();
		$this->reports = array(
			'mail'		=> array('level' => 0, 'data' => null),
			'file'		=> array('level' => 0, 'data' => null),
			'console'	=> array('level' => 0, 'data' => null),
			'stdout'	=> array('level' => 0, 'data' => null),
			'system'	=> array('level' => 0, 'data' => null),
			'redirect'  => array('level' => 0, 'data' => null),
			'browser'   => array('level' => 0, 'data' => null),
		);

		$this->contextLines = 5;
		$this->strictContext = true;
		$this->classExcludeList = array();
        $this->excludeObjects = true;
	}
	
	// }}}
	// {{{ setDateFormat()

	function setDateFormat($format)
	{
		$this->dateFormat = $format;
	}

	// }}}
	// {{{ setContextLines()

	function setContextLines($lines)
	{
		$this->contextLines = intval($lines);
	}

	// }}}
	// {{{ setStrictContext()

	function setStrictContext($boolean)
	{
		$this->strictContext = $boolean ? true : false;
	}

	// }}}
    // {{{ setExcludeObjects()

    function setExcludeObjects()
    {
        if (gettype(func_get_arg(0)) == 'boolean')
        {
            $this->excludeObjects = func_get_arg(0);
        }
        else
        {
		    $list = func_get_args();
		    $this->classExcludeList = array_map('strtolower', $list);
        }
    }

    // }}}
	// {{{ addReporter()

	function addReporter($reporter, $level, $data = null)
	{
		$this->reports[$reporter] = array(
			'level'	=> $level,
			'data'	=> $data
		); 
	}

	// }}}
	// {{{ getMessage()

	function getMessage(&$error)
	{
		$message = '<div style="display: none;">' . date($this->dateFormat) . ' </div>';

		if ($error['level'] & E_ERROR_ALL)
		{
			$message .= '<span class="errorLevel">[error]</span>';
		}
		else if ($error['level'] & E_WARNING_ALL)
		{
			$message .= '<span class="errorLevel">[warning]</span>';
		}
		else if ($error['level'] & E_NOTICE_ALL)
		{
			$message .= '<span class="errorLevel">[notice]</span>';
		}
		else if ($error['level'] & E_DEBUG)
		{
			$message .= '<span class="errorLevel">[debug]</span>';
		}
		else if ($error['level'] & E_LOG)
		{
			$message .= '<span class="errorLevel">[log]</span>';
		}
		else
		{
			$message .= '<span class="errorLevel">[unknown]</span>';
		}

		$message .= ' in ' . $error['file'] . ' on line <span style="text-decoration: underline; padding-bottom: 1px; border-bottom: 1px solid black;">' . $error['line'] . '</span> <div class="errorMessage">' . $error['message'] . '</div>' . "\n";

		return $message;
	}

	// }}}
    // {{{ getDetails()

    /**
     * Gets the details of a message, such as the variable context
     *
     * @param object $error The error object
     *
     * @access public
     * @return string The html of any details of the error
     */
    function getDetails(&$error)
    {
        // :NOTE: dallen 2003/02/01 perhaps we can add an addition data option for error
        // level which includes source and variable context
        // :BUG: dallen 2003/02/03 this should be a class specification
        $output = '<div style="margin: 5px 5px 0 5px; font-family: sans-serif; font-size: 10px;">';
        $output .= '==> Source report from ' . $error['file'] . ' around line ' . $error['line'];
        $output .= ' (' . $error['context']['start'] . '-' . $error['context']['end'] . ')</div>';
        $output .= '<div style="margin: 0 5px 5px 5px; background-color: #EEEEEE; border: 1px dashed #000000;">';
        $output .= str_replace('  ', '&nbsp; ', str_replace('&nbsp;', ' ', @highlight_string(stripslashes($error['context']['source']), true)));
        $output .= '</div>';
        $variables = $this->_exportVariables($error['variables'], $error['context']['variables']);
        if ($variables !== false)
        {
            $variables = @highlight_string(stripslashes('<?php' . $variables . '?>'), true);
            // strip the php tags
            $variables = preg_replace(':(&lt;\?php(<br />)*|\?&gt;):', '', $variables);
            // :BUG: dallen 2003/02/03 this should be a class specification
            $output .= '<div style="margin: 5px 5px 0 5px; font-family: sans-serif; font-size: 10px;">';
            $output .= '==> Variable scope report</div>';
            $output .= '<div style="margin: 0px 5px 5px 5px;">' . $variables . '</div>';
        }
        
        return $output;
    }

    // }}}
	// {{{ prepare()

	function prepare()
	{
	}

	// }}}
	// {{{ current()

	function current(&$error, $index)
	{
		$message = $this->getMessage($error);

		// syslog
		if ($this->reports['system']['level'] & $error['level'])
		{
			@error_log(strip_tags($message), SYSTEM_LOG);
		}

		// file
		if ($this->reports['file']['level'] & $error['level'])
		{
			@error_log(strip_tags($message), FILE_LOG, $this->reports['file']['data']);
		}

		// email
		if ($this->reports['mail']['level'] & $error['level'])
		{
			@error_log(strip_tags($message), MAIL_LOG, $this->reports['mail']['data']);
		}
		
		// redirect
		if ($this->reports['redirect']['level'] & $error['level'])
		{
			echo '<script type="text/javascript">window.location.href = \'' . $this->reports['redirect']['data'] . '\';</script>';
			exit;
		}

		// stdout
		if ($this->reports['stdout']['level'] & $error['level'])
		{
			echo strip_tags($message);
		}

		// browser
		if ($this->reports['browser']['level'] & $error['level'])
		{
			echo $message . $this->getDetails($error);
		}

		// console
		if ($this->reports['console']['level'] & $error['level'])
		{
			$this->errorList[$index + 1] = $this->_makeStringJSSafe($message . $this->getDetails($error));
		}
	}

	// }}}
	// {{{ between()

	function between()
	{
	}

	// }}}
	// {{{ finish()

	function finish()
	{
		$errors =& $this->errorList;
		include 'error.tpl.php';
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

	function _exportVariables(&$variables, $contextVariables)
	{
		$variableString = '';
		foreach ($variables as $name => $contents)
		{
			// if we are using strict context and this variable is not in the context, skip it
			if ($this->strictContext && !in_array($name, $contextVariables))
			{
				continue;
			}

			// if this is an object and the class is in the exclude list, skip it
			if (is_object($contents) && in_array(get_class($contents), $this->classExcludeList))
			{
				continue;
			}

			$variableString .= '$' . $name . ' = ' . var_export2($contents, true) . ';' . "\n";
		}

		if (empty($variableString))
		{
			return false;
		}
		else
		{
			return "\n" . $variableString;
		}
	}

	// }}}
}

// {{{ trapError()

function trapError()
{
	static $variable, $signatures = array();

	if (!isset($prependString) || !isset($appendString))
	{
		$prependString = ini_get('error_prepend_string');
		$appendString = ini_get('error_append_string');
	}

	// error event has been caught
	if (func_num_args() == 5)
	{
		// silenced error (using @)
		if (error_reporting() == 0)
		{
			return;
		}
		
		$args = func_get_args();

		// weed out duplicate errors (coming from same line and file)
		$signature = md5($args[1] . ':' . $args[2] . ':' . $args[3]);
		if (isset($signatures[$signature]))
		{
			return;
		}
		else
		{
			$signatures[$signature] = true;
		}

		// cut out the fat from the variable context (we get back a lot of junk)
		$variables =& $args[4];
		$variablesFiltered = array();
        $excludeObjects = $GLOBALS[$variable]->reporter->excludeObjects;
		foreach (array_keys($variables) as $variableName)
		{
			// these are server variables most likely
			if ($variableName == strtoupper($variableName))
			{
				continue;
			}
			elseif ($variableName{0} == '_')
			{
				continue;
			}
			elseif ($variableName == 'argv' || $variableName == 'argc')
			{
				continue;
			}
            elseif ($excludeObjects && gettype($variables[$variableName]) == 'object')
            {
                continue;
            }
			// don't allow instance of errorstack to come through
			elseif (is_a($variables[$variableName], 'ErrorList') ||
					is_a($variables[$variableName], 'ErrorReporter'))
			{
				continue;
			}
			
			// :WARNING: dallen 2003/01/31 This could lead to a memory leak,
			// maybe only copy up to a certain size
			// make a copy to preserver the state at time of error
			$variablesFiltered[$variableName] = $variables[$variableName];
		}

		$error = array(
			'level'		=> $args[0],
			'message'	=> $prependString . $args[1] . $appendString,
			'file'		=> $args[2],
			'line'		=> $args[3],
			'variables'	=> $variablesFiltered,
			'signature'	=> $signature,
		);

		$GLOBALS[$variable]->add($error);
	}
	elseif (func_num_args() == 1)
	{
		$variable = func_get_arg(0);
	}
	else {
		return $variable;
	}
}

// }}}
?>
