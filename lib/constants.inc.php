<?php
/** $Id: constants.inc.php,v 1.3 2003/01/23 23:00:52 jrust Exp $ */
if (!defined('IN_FASTFRAME')) {
    die('Hacking Attempt');
}

/**
 * Global list of all actionID constants used in the different apps.
 */
define('ACTION_NOOP',             'noop');
define('ACTION_ADD',              'add');
define('ACTION_ADD_SUBMIT',       'add_submit');
define('ACTION_EDIT',             'edit');
define('ACTION_EDIT_SUBMIT',      'edit_submit');
define('ACTION_VIEW',             'view');
define('ACTION_DELETE',           'delete');
define('ACTION_LIST',             'list');
define('ACTION_EXPORT',           'export');
define('ACTION_LOGIN',            'login');
define('ACTION_LOGIN_SUBMIT',     'login_submit');
define('ACTION_LOGOUT',           'logout');
define('ACTION_ACTIVATE',         'activate');
define('ACTION_DISPLAY',          'display');

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
