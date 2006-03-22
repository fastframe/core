<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright 2003-2005 Chuck Hagenbuch <chuck@horde.org>                |
// | Copyright 2003-2005 Jan Schneider <jan@horde.org>                    |
// | Copyright (c) 2004 The Codejanitor Group                             |
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
// | Authors: Chuck Hagenbuch <chuck@horde.org                            |
// | Authors: Jan Schneider <jan@horde.org                                |
// | Authors: Jason Rust <jrust@codejanitor.com>                          |
// +----------------------------------------------------------------------+

// }}}
// {{{ requires

require_once dirname(__FILE__) . '/../FastFrame.php';
require_once dirname(__FILE__) . '/ErrorHandler.php';
require_once dirname(__FILE__) . '/Registry.php';
require_once dirname(__FILE__) . '/Request.php';
require_once dirname(__FILE__) . '/ActionHandler.php';

// }}}
// {{{ class FF_CLI

/**
 * FF_CLI:: API for basic command-line functionality/checks.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package FF_CLI
 */

// }}}
class FF_CLI {
    // {{{ properties

    /**
     * Are we running on a console?
     * @var bool
     */
    var $_console;

    /**
     * The newline string to use.
     * @var string
     */
    var $_newline;

    /**
     * The indent string to use.
     * @var string
     */
    var $_indent;

    /**
     * The string to mark the beginning of bold text.
     * @var string
     */
    var $_bold_start = '';

    /**
     * The string to mark the end of bold text.
     * @var string
     */
    var $_bold_end = '';

    /**
     * The strings to mark the beginning of coloured text.
     */
    var $_red_start    = '';
    var $_green_start  = '';
    var $_yellow_start = '';
    var $_blue_start   = '';

    /**
     * The strings to mark the end of coloured text.
     */
    var $_red_end      = '';
    var $_green_end    = '';
    var $_yellow_end   = '';
    var $_blue_end     = '';

    // }}}
    // {{{ constructor

    /**
     * Constructor.
     * Detects the current environment (web server or console)
     * and sets internal values accordingly.
     *
     * @param string $in_app (optional) The app name to push
     *
     * @access public
     * @return void
     */
    function FF_CLI($in_app = 'fastframe')
    {
        define('IS_AJAX', false);
        $this->_console = $this->runningFromCLI();

        if ($this->_console) {
            $this->init();
            $this->_newline = "\n";
            $this->_indent  = '    ';

            $term = getenv('TERM');
            if ($term) {
                if (preg_match('/^(xterm|vt220|linux)/', $term)) {
                    $this->_bold_start   = "\x1b[1m";
                    $this->_red_start    = "\x1b[01;31m";
                    $this->_green_start  = "\x1b[01;32m";
                    $this->_yellow_start = "\x1b[01;33m";
                    $this->_blue_start   = "\x1b[01;34m";
                    $this->_bold_end = $this->_red_end = $this->_green_end = $this->_yellow_end = $this->_blue_end = "\x1b[0m";
                } elseif (preg_match('/^vt100/', $term)) {
                    $this->_bold_start = "\x1b[1m";
                    $this->_bold_end   = "\x1b[0m";
                }
            }
        } else {
            $this->_newline = '<br />';
            $this->_indent  = str_repeat('&nbsp;', 4);

            $this->_bold_start  = '<strong>';
            $this->_bold_end    = '</strong>';
            $this->_red_start   = '<span style="color:red">';
            $this->_green_start = '<span style="color:green">';
            $this->_yellow_start = '<span style="color:yellow">';
            $this->_blue_start   = '<span style="color:blue">';
            $this->_red_end = $this->_green_end = $this->_yellow_end = $this->_blue_end = '</span>';
        }

        $o_registry =& FF_Registry::singleton();
        $o_registry->pushApp($in_app);
        if (!isset($GLOBALS['o_error'])) {
            PEAR::setErrorHandling(PEAR_ERROR_TRIGGER, E_USER_NOTICE); 
            if ($this->_console) {
                $a_reporter = array('stdout' => array('level' => E_VERY_ALL));
            }
            else {
                $a_reporter = array('console' => array('level' => E_VERY_ALL));
            }

            $o_error = new FF_ErrorHandler($a_reporter);
            $GLOBALS['o_error'] =& $o_error;
            $o_error->setDateFormat('[Y-m-d H:i:s]');
            $o_error->setExcludeObjects(false);
        }
    }

    // }}}
    // {{{ singleton()

    /**
     * Returns a single instance of the FF_CLI class.
     */
    function &singleton()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = &new FF_CLI();
        }

        return $instance;
    }

    // }}}
    // {{{ writeln()

    /**
     * Prints $text on a single line.
     *
     * @param string $text  The text to print.
     * @param bool $pre     If true the linebreak is printed before
     *                      the text instead of after it.
     */
    function writeln($text = '', $pre = false)
    {
        if ($pre) {
            echo $this->_newline . $text;
        } else {
            echo $text . $this->_newline;
        }
    }

    // }}}
    // {{{ indent()

    /**
     * Returns the indented string.
     *
     * @param string $text  The text to indent.
     */
    function indent($text)
    {
        return $this->_indent . $text;
    }

    // }}}
    // {{{ bold()

    /**
     * Returns a bold version of $text.
     *
     * @param string $text  The text to bold.
     */
    function bold($text)
    {
        return $this->_bold_start . $text . $this->_bold_end;
    }

    // }}}
    // {{{ red()

    /**
     * Returns a red version of $text.
     *
     * @param string $text  The text to print in red.
     */
    function red($text)
    {
        return $this->_red_start . $text . $this->_red_end;
    }

    // }}}
    // {{{ green()

    /**
     * Returns a green version of $text.
     *
     * @param string $text  The text to print in green.
     */
    function green($text)
    {
        return $this->_green_start . $text . $this->_green_end;
    }

    // }}}
    // {{{ blue()

    /**
     * Returns a blue version of $text.
     *
     * @param string $text  The text to print in blue.
     */
    function blue($text)
    {
        return $this->_blue_start . $text . $this->_blue_end;
    }

    // }}}
    // {{{ yellow()

    /**
     * Returns a yellow version of $text.
     *
     * @param string $text  The text to print in yellow.
     */
    function yellow($text)
    {
        return $this->_yellow_start . $text . $this->_yellow_end;
    }

    // }}}
    // {{{ message()

    /**
     * Displays a message.
     *
     * @param string $event          The message string.
     * @param optional string $type  The type of message: 'cli.error',
     *                               'cli.warning', 'cli.success', or
     *                               'cli.message'.
     */
    function message($message, $type = 'cli.message')
    {
        switch ($type) {
        case 'cli.error':
            $type_message = '[ ' . $this->red('ERROR!') . ' ] ';
            break;
        case 'cli.warning':
            $type_message = '[  ' . $this->yellow('WARN') . '  ] ';
            break;
        case 'cli.success':
            $type_message = '[   ' . $this->green('OK') . '   ] ';
            break;
        case 'cli.message':
            $type_message = '[  ' . $this->blue('INFO') . '  ] ';
            break;
        }

        $this->writeln($type_message . $message);
    }

    // }}}
    // {{{ fatal()

    /**
     * Displays a fatal error message.
     *
     * @param string $error  The error text to display.
     */
    function fatal($error)
    {
        $this->writeln($this->red('===================='));
        $this->writeln();
        $this->writeln($this->red(_("Fatal Error!")));
        $this->writeln($this->red($error));
        $this->writeln();
        $this->writeln($this->red('===================='));
        exit;
    }

    // }}}
    // {{{ prompt()

    /**
     * Prompts for a user response.
     *
     * @param string $prompt            The message to display when
     *                                  prompting the user.
     * @param optional array $choices   The choices available to the
     *                                  user or null for a text input.
     *
     * @return mixed   The user's response to the prompt.
     */
    function prompt($prompt, $choices = null)
    {
        $stdin = fopen('php://stdin', 'r');

        // Main event loop to capture top level command.
        while (true) {
            // Print out the prompt message.
            $this->writeln($prompt . ' ', !is_array($choices));
            if (is_array($choices) && !empty($choices)) {
                foreach ($choices as $key => $choice) {
                    $key = $this->bold($key);
                    $this->writeln($this->indent('(' . $key . ') ' . $choice));
                }
                $this->writeln(_("Type your choice: "), true);

                // Get the user choice.
                $response = trim(fgets($stdin, 256));

                if (isset($choices[$response])) {
                    // Close standard in.
                    fclose($stdin);
                    return $response;
                } else {
                    $this->writeln(sprintf(_("'%s' is not a valid choice."), $response));
                }
            } else {
                $response = trim(fgets($stdin, 256));
                fclose($stdin);
                return $response;
            }
        }

        return true;
    }

    // }}}
    // {{{ init()

    /**
     * CLI scripts shouldn't timeout, so try to set the time limit to
     * none. Also initialize a few variables in $_SERVER that aren't
     * present from the CLI.
     *
     * @access static
     */
    function init()
    {
        @set_time_limit(0);
        ob_implicit_flush(true);
        ini_set('html_errors', false);
        $_SERVER['HTTP_HOST'] = '127.0.0.1';
        $_SERVER['SERVER_NAME'] = '127.0.0.1';
        $_SERVER['SERVER_PORT'] = '';
        $_SERVER['REMOTE_ADDR'] = '';
        $_SERVER['PHP_SELF'] = isset($argv) ? $argv[0] : '';
    }

    // }}}
    // {{{ runningFromCLI()

    /**
     * Make sure we're being called from the command line, and not via
     * the web.
     *
     * @access static
     *
     * @return boolean  True if we are, false otherwise.
     */
    function runningFromCLI()
    {
        // STDIN isn't a CLI constant before 4.3.0
        $sapi = php_sapi_name();
        if (version_compare(PHP_VERSION, '4.3.0') >= 0 && $sapi != 'cgi') {
            return @is_resource(STDIN);
        } else {
            return in_array($sapi, array('cli', 'cgi')) && empty($_SERVER['REMOTE_ADDR']);
        }
    }

    // }}}
}
?>
