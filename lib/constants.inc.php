<?php
/** $Id: constants.inc.php,v 1.5 2003/02/08 00:10:54 jrust Exp $ */
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
 * This file defines constants used throughout FastFrame
 */
if (!defined('IN_FASTFRAME')) {
    die('Hacking Attempt');
}

/**
 * User classes.  Rough grained permissions.
 */
define('UC_GUEST',          1);
define('UC_USER',           2); 
define('UC_ADMIN_VIEWER',   4); 
define('UC_ADMIN_EDITOR',   8); 
define('UC_ADMIN',          16); 
define('UC_ROOT',           32); 

/**
 * Message constants.  These images need to be in the graphics/alerts directory.
 * The theme can mimic the grapics/alerts directory to override the default image.
 */
define('FASTFRAME_NORMAL_MESSAGE', 'message.gif', true);
define('FASTFRAME_ERROR_MESSAGE', 'error.gif', true);
define('FASTFRAME_WARNING_MESSAGE', 'warning.gif', true);
define('FASTFRAME_SUCCESS_MESSAGE', 'success.gif', true);
?>
