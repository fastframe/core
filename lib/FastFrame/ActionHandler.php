<?php
/** $Id$ */
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
// | Authors: Jason Rust <jrust@codejanitor.com>                          |
// +----------------------------------------------------------------------+

// }}}
// {{{ requires

require_once 'File.php';
require_once dirname(__FILE__) . '/../FastFrame.php';
require_once dirname(__FILE__) . '/Registry.php';
require_once dirname(__FILE__) . '/Output.php';
require_once dirname(__FILE__) . '/Auth.php';
require_once dirname(__FILE__) . '/Perms.php';

// }}}
// {{{ constants

/**
 * The predefined action constants.  If an app wants to define additional constants to be
 * used it needs to put it in a actions.php file in the ActionHandler/ directory
 */
define('ACTION_PROBLEM',        'problem');
define('ACTION_ADD',            'add');
define('ACTION_ADD_SUBMIT',     'add_submit');
define('ACTION_EDIT',           'edit');
define('ACTION_EDIT_SUBMIT',    'edit_submit');
define('ACTION_VIEW',           'view');
define('ACTION_DELETE',         'delete');
define('ACTION_LIST',           'list');
define('ACTION_LOGIN',          'login');
define('ACTION_LOGIN_SUBMIT',   'login_submit');
define('ACTION_LOGOUT',         'logout');
define('ACTION_DISPLAY',        'display');
define('ACTION_TREE',           'tree');

// }}}
// {{{ class FF_ActionHandler 

/**
 * The FF_ActionHandler:: class handles page actions.
 *
 * Actions are are passed via a GET/POST actionId.  The action handler is meant to be
 * extended by each application and module to initialize the application actions and data
 * access object.  It is the controller that sets up the environment for the page and then
 * finds the action to take.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package FF_ActionHandler 
 */

// }}}
class FF_ActionHandler {
    // {{{ properties

    /**
     * The actionId.  One of the above-defined constants
     * @type string 
     */
    var $actionId;

    /**
     * The default actionId for when an invalid actionId is passed in
     * @type string 
     */
    var $defaultActionId;

    /**
     * This application's name
     * @type string
     */
    var $appName;

    /**
     * The registry object
     * @type object
     */
    var $o_registry;

    /**
     * The primary model object 
     * @type object
     */
    var $o_model;

    /**
     * The array of default available actions, the path to the action class file and the
     * name of the class
     * @type array
     */
    var $availableActions = array(
        ACTION_PROBLEM    => array('Action/Problem.php', 'FF_Action_Problem'),
        ACTION_ADD        => array('Action/Form.php', 'FF_Action_Form'),
        ACTION_ADD_SUBMIT => array('Action/FormSubmit.php', 'FF_Action_FormSubmit'),
        ACTION_EDIT       => array('Action/Form.php', 'FF_Action_Form'),
        ACTION_EDIT_SUBMIT=> array('Action/FormSubmit.php', 'FF_Action_FormSubmit'),
        ACTION_DELETE     => array('Action/Delete.php', 'FF_Action_Delete'),
        ACTION_LIST       => array('Action/List.php', 'FF_Action_List'),
        ACTION_LOGIN      => array('Action/Login.php', 'FF_Action_Login'),
        ACTION_LOGIN_SUBMIT=> array('Action/LoginSubmit.php', 'FF_Action_LoginSubmit'),
        ACTION_LOGOUT     => array('Action/Logout.php', 'FF_Action_Logout'),
        ACTION_DISPLAY    => array('Action/Display.php', 'FF_Action_Display'),
        ACTION_TREE       => array('Action/Tree.php', 'FF_Action_Tree'),
    );

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function FF_ActionHandler()
    {
        $this->_initializeErrorHandler();
        $this->setActionId(FastFrame::getCGIParam('actionId', 'gp'));
        $this->o_registry =& FF_Registry::singleton();
        FF_Auth::sessionStart();
        $this->_checkAuth();
        $this->_checkProfile();
        $this->_makeActionPathsAbsolute();
        $o_output =& FF_Output::singleton();
        $s_theme = FF_Auth::getCredential('theme');
        $s_theme = empty($s_theme) ? $this->o_registry->getConfigParam('general/default_theme') : $s_theme;
        $o_output->load($s_theme);
    }

    // }}}
    // {{{ takeAction()

    /**
     * Takes action on the current action ID.
     *
     * @access public
     * @return void
     */
    function takeAction()
    {
        if (empty($this->actionId) ||
            !isset($this->availableActions[$this->actionId])) {
            if (isset($this->availableActions[$this->defaultActionId])) {
                $this->setActionId($this->defaultActionId);
            }
            else {
                $this->setActionId(ACTION_PROBLEM);
            }
        }

        $hitLastAction = false;
        while (!$hitLastAction) {
            $pth_actionFile = $this->availableActions[$this->actionId][0];
            if (file_exists($pth_actionFile)) {
                require_once $pth_actionFile;
                $o_action =& new $this->availableActions[$this->actionId][1]($this->o_model);
                $o_action->setCurrentActionId($this->actionId);
                $o_nextAction =& $o_action->run();
                if ($o_nextAction->isLastAction()) {
                    $hitLastAction = true;
                }
                else {
                    $this->actionId = $o_nextAction->getNextActionId();
                }
            }
            else {
                $tmp_error = "The class file for action $this->actionId ($pth_actionFile) does not exist";
                FastFrame::fatal($tmp_error, __FILE__, __LINE__); 
            }
        }
    }

    // }}}
    // {{{ addAction()

    /**
     * Adds/modifies an available action and registers the necessary class filees.
     * 
     * @param string $in_actionId The action id 
     * @param string $in_classFile The path to the class file for this action
     * @param string $in_className The name of the class for this action
     *
     * @access public
     * @return void
     */
    function addAction($in_actionId, $in_classFile, $in_className)
    {
        $this->availableActions[$in_actionId] = array($in_classFile, $in_className);
    }

    // }}}
    // {{{ batchModifyActions()

    /**
     * This method makes it easier to modify a batch actions so that if the new action
     * classes are all in the same directory and are named the same as the default class
     * file, then you can easily modify the default actions to reflect this.
     *
     * @param array $in_actions The array of actions to modify
     * @param string $in_path The path to where all the classes for the modified actions are
     *               stored.
     * @param string $in_classExtension  The extension you are giving to the class name
     *               (i.e. Action_Form => becomes Action_Form_MyApp, then pass in _MyApp)
     * @param string $in_filePrefix (optional) The prefix to the give the filename.  I.e.
     *               List.php -> BorrowerList.php
     *
     * @access public
     * @return void
     */
    function batchModifyActions($in_actions, $in_path, $in_classExtension, $in_filePrefix = '') {
        foreach ($in_actions as $s_actionId) {
            $s_newPath = $in_path . '/' . $in_filePrefix . basename($this->availableActions[$s_actionId][0]);
            $s_newClass = $this->availableActions[$s_actionId][1] . $in_classExtension;
            $this->addAction($s_actionId, $s_newPath, $s_newClass);
        }
    }

    // }}}
    // {{{ setActionId()

    /**
     * Sets the actionId.
     *
     * @param string $in_actionId (optional) The actionId to act upon.
     *
     * @access public
     * @return void 
     */
    function setActionId($in_actionId)
    {
        $this->actionId = $in_actionId;
    }

    // }}}
    // {{{ getActionId()

    /**
     * Gets the actionId.
     *
     * @access public
     * @return string The current ActionId 
     */
    function getActionId()
    {
        return $this->actionId;
    }

    // }}}
    // {{{ setDefaultActionId()

    /**
     * Sets the default action to take if an invalid actionId is passed in
     *
     * @param string $in_actionId The default actionId
     *
     * @access public
     * @return void
     */
    function setDefaultActionId($in_actionId)
    {
        $this->defaultActionId = $in_actionId;
    }

    // }}}
    // {{{ _makeActionPathsAbsolute()

    /**
     * Fixes the action paths so that they are absolute paths
     *
     * @access private
     * @return void
     */
    function _makeActionPathsAbsolute()
    {
        $s_path = dirname(__FILE__) . '/';
        foreach ($this->availableActions as $s_action => $a_vals) {
            if (strpos($a_vals[0], '/') !== 0) {
                $this->availableActions[$s_action][0] = $s_path . $a_vals[0];
            }
        }
    }

    // }}}
    // {{{ _initializeErrorHandler()

    /**
     * Initializes the error handler
     *
     * @access private
     * @return void
     */
    function _initializeErrorHandler()
    {
        define('ECLIPSE_ROOT', dirname(__FILE__) . '/../eclipse/');
        require_once dirname(__FILE__) . '/../Error/Error.php';
        $o_reporter =& new ErrorReporter();
        $o_reporter->setDateFormat('[Y-m-d H:i:s]');
        $o_reporter->setStrictContext(false);
        $o_reporter->setExcludeObjects(false);
        $o_reporter->addReporter('console', E_VERY_ALL);
        ErrorList::singleton($o_reporter, 'o_error');
    }

    // }}}
    // {{{ _checkAuth()

    /**
     * Initializes the auth object and makes sure that the page can proceed because the
     * authentication passes
     *
     * @access private
     * @return void
     */
    function _checkAuth()
    {
        if (!FF_Auth::checkAuth()) {
            FF_Auth::logout();
        }
    }

    // }}}
    // {{{ _checkProfile()

    /**
     * Checks to see if the user must complete their profile before proceeding.  Redirects
     * them to the profile page if so.
     *
     * @access private
     * @return void
     */
    function _checkProfile()
    {
        if ($this->o_registry->hasApp('profile') &&
            FF_Auth::checkAuth() && 
            !FF_Auth::getCredential('isProfileComplete') &&
            $this->getActionId() != ACTION_LOGOUT &&
            $this->o_registry->getConfigParam('profile/force_complete', false, array('app' => 'profile'))) {
            require_once $this->o_registry->getAppFile('ActionHandler/actions.php', 'profile', 'libs');
            // don't redirect if already on the profile page or submitting it
            if ($this->getActionId() != ACTION_MYPROFILE && $this->getActionId() != ACTION_EDIT_SUBMIT) {
                FastFrame::redirect(FastFrame::url('index.php', array('app' => 'profile', 'actionId' => ACTION_MYPROFILE)));
            }
        }
    }

    // }}}
}
?>
