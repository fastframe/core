<?php
/** $Id: constants.inc.php,v 1.2 2003/01/09 22:43:51 jrust Exp $ */
if (!defined('IN_FASTFRAME')) {
    die('Hacking Attempt');
}

/**
 * Global list of all actionID constants used in the different apps.
 */
define('NOOP',                   -1, true);
define('ADD_OBJECT',              1, true);
define('EDIT_OBJECT',             2, true);
define('ADD_OBJECT_SUBMIT',       3, true);
define('EDIT_OBJECT_SUBMIT',      4, true);
define('DELETE_OBJECT_SUBMIT',    5, true);
define('LIST_OBJECTS',            6, true);
define('EXPORT_OBJECTS',          7, true);
define('SUBSCRIBE',               8, true);
define('SUBSCRIBE_SUBMIT',        9, true);
define('LOGIN',                  10, true);
define('LOGIN_SUBMIT',           11, true);
define('LOGOUT_SUBMIT',          12, true);
define('DISPLAY_MAIN',           13, true);

/**
 * Message constants.  These images need to be in the graphics/alerts directory.
 * The theme can mimic the grapics/alerts directory to override the default image.
 */
define('FASTFRAME_NORMAL_MESSAGE', 'message.gif', true);
define('FASTFRAME_ERROR_MESSAGE', 'error.gif', true);
define('FASTFRAME_WARNING_MESSAGE', 'warning.gif', true);
define('FASTFRAME_SUCCESS_MESSAGE', 'success.gif', true);
?>
