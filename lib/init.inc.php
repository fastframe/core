<?php
/** $Id: init.inc.php,v 1.9 2003/02/08 00:01:25 jrust Exp $ */
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

// }}}
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

define('ECLIPSE_ROOT', dirname(__FILE__) . '/eclipse/');
require_once dirname(__FILE__) . '/Error/Error.php';
$o_reporter =& new ErrorReporter();
$o_reporter->setDateFormat('[Y-m-d H:i:s]');
$o_reporter->setStrictContext(false);
$o_reporter->setExcludeObjects(false);
$o_reporter->addReporter('console', E_VERY_ALL);
ErrorList::singleton($o_reporter, 'o_error');

// }}}
// {{{ require libraries

// PEAR libraries
require_once 'PEAR.php';
require_once 'Net/UserAgent/Detect.php';
require_once 'File.php';

// FastFrame specific libraries (we use dirname() here so it doesn't conflict with PEAR libraries)
require_once dirname(__FILE__) . '/constants.inc.php';
require_once dirname(__FILE__) . '/FastFrame.php';
require_once dirname(__FILE__) . '/FastFrame/Auth.php';
require_once dirname(__FILE__) . '/FastFrame/ActionHandler.php';
require_once dirname(__FILE__) . '/FastFrame/Application.php';

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
?>
