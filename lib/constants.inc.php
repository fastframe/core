<?php
/** $Id: constants.inc.php,v 1.4 2003/02/06 19:01:03 jrust Exp $ */
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
