<?php
/** $Id: init.inc.php,v 1.3 2003/01/09 22:43:25 jrust Exp $ */
/**
 * If you want to use the FastFrame application framework in your application,
 * you have to specify the path, either full or relative to get to the
 * FASTFRAME_ROOT and then expand this line to reach the FastFrame_Registry.php class.
 * After that everything is taken care of.
 */
// {{{ check for hack attempt

if (!defined('IN_FASTFRAME')) {
    die('Hacking attempt');
}

// }}}
// {{{ initialize error handler

require_once dirname(__FILE__) . '/Error/Handler.php';
$o_error =& new Error_Handler(realpath(FASTFRAME_ROOT . 'config/Error_Handler.ini'));

// }}}
// {{{ include libraries

// PEAR libraries
require_once 'PEAR.php';
require_once 'Net/UserAgent/Detect.php';
require_once 'HTML/QuickForm.php';
require_once 'XML/XPath.php';
require_once 'MDB.php';
require_once 'File.php';

// FastFrame specific libraries (we use dirname() here so it doesn't conflict with PEAR libraries)
require_once dirname(__FILE__) . '/constants.inc.php';
require_once dirname(__FILE__) . '/FastFrame.php';
require_once dirname(__FILE__) . '/FastFrame/SQL.php';
require_once dirname(__FILE__) . '/FastFrame/HTML.php';

// }}}
// {{{ init

$o_registry =& FastFrame_Registry::singleton();

// this starts the session
$o_auth = FastFrame_Auth::singleton();
// if this is not the login page, then we need to check the auth!
if (empty($b_checkAuth)) {
    if (!$o_auth->checkAuth()) {
        // this session is not valid, handle appropriately
        $o_auth->logout();
    }
}

// load in the core language text
$a_lang =& $o_registry->getRootLocale();

// }}}
// {{{ set variables 

$s_pageType = 'normal';
$o_html =& FastFrame_HTML::singleton();
$a_persistent['actionID'] = FastFrame::getCGIParam('actionID', 'gp', NOOP);

// }}}
?>
