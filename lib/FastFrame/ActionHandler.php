<?php
/** $Id: ActionHandler.php,v 1.6 2003/03/15 01:26:57 jrust Exp $ */
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
     * The array of default available actions (which are turned into constants later)
     * The key is the action ID.  0 => the name of the constant.  1 => the relative path
     * from here to the class file, 2 => the class name
     * @type array
     */
    var $availableActions = array(
        'problem'       => array('ACTION_PROBLEM', 'Action/Problem.php', 'FF_Action_Problem'),
        'add'           => array('ACTION_ADD', 'Action/Form.php', 'FF_Action_Form'),
        'add_submit'    => array('ACTION_ADD_SUBMIT', 'Action/FormSubmit.php', 'FF_Action_FormSubmit'),
        'edit'          => array('ACTION_EDIT', 'Action/Form.php', 'FF_Action_Form'),
        'edit_submit'   => array('ACTION_EDIT_SUBMIT', 'Action/FormSubmit.php', 'FF_Action_FormSubmit'),
        'view'          => array('ACTION_VIEW', 'Action/View.php', 'FF_Action_View'),
        'delete'        => array('ACTION_DELETE', 'Action/Delete.php', 'FF_Action_Delete'),
        'list'          => array('ACTION_LIST', 'Action/List.php', 'FF_Action_List'),
        'export'        => array('ACTION_EXPORT', 'Action/Export.php', 'FF_Action_Export'),
        'login'         => array('ACTION_LOGIN', 'Action/Login.php', 'FF_Action_Login'),
        'login_submit'  => array('ACTION_LOGIN_SUBMIT', 'Action/LoginSubmit.php', 'FF_Action_LoginSubmit'),
        'logout'        => array('ACTION_LOGOUT', 'Action/Logout.php', 'FF_Action_Logout'),
        'activate'      => array('ACTION_ACTIVATE', 'Action/Activate.php', 'FF_Action_Activate'),
        'display'       => array('ACTION_DISPLAY', 'Action/Display.php', 'FF_Action_Display'),
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
        $this->_checkAuth();
        $this->_initializeDefaultConstants();
        $this->_makeActionPathsAbsolute();
        $this->o_registry =& FastFrame_Registry::singleton();
        $this->setActionId(FastFrame::getCGIParam('actionId', 'gp'));
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
            $pth_actionFile = $this->availableActions[$this->actionId][1];
            if (file_exists($pth_actionFile)) {
                require_once $pth_actionFile;
                $o_action = new $this->availableActions[$this->actionId][2]($this->o_model);
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
     * Adds/replaces an available action and registers the necessary class filees.
     * 
     * @param string $in_constantName The name of the constant for this action
     * @param string $in_actionId The corresponding action value to assign to the constant
     * @param string $in_classFile The path to the class file for this action
     * @param string $in_className The name of the class for this action
     *
     * @access public
     * @return void
     */
    function addAction($in_constantName, $in_actionId, $in_classFile, $in_className)
    {
        $this->availableActions[$in_actionId] = array($in_constantName, $in_classFile, $in_className);
        define($in_constantName, $in_actionId);
    }

    // }}}
    // {{{ modifyAction()

    /**
     * Modifies an existing action.  Thus, the actionId must already be defined. 
     * Use if you want to override the class file with your own.
     *
     * @param $in_actionId The action ID to modify
     * @param $in_classFile The path to the new class file
     * @param $in_className The name of the new class
     *
     * @accesss public
     * @return void
     */
    function modifyAction($in_actionId, $in_classFile, $in_className)
    {
        $this->availableActions[$in_actionId][1] = $in_classFile;
        $this->availableActions[$in_actionId][2] = $in_className;
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
     *
     * @access public
     * @return void
     */
    function batchModifyActions($in_actions, $in_path, $in_classExtension) {
        foreach ($in_actions as $s_actionId) {
            $s_newPath = File::buildPath(array($in_path, basename($this->availableActions[$s_actionId][1])));
            $s_newClass = $this->availableActions[$s_actionId][2] . $in_classExtension;
            $this->modifyAction($s_actionId, $s_newPath, $s_newClass);
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
            if (strpos($a_vals[1], '/') !== 0) {
                $this->availableActions[$s_action][1] = $s_path . $a_vals[1];
            }
        }
    }

    // }}}
    // {{{ _initializeDefaultConstants()

    /**
     * Takes care of initializing the default constants from the availableActions array
     *
     * @access private
     * @return void
     */
    function _initializeDefaultConstants()
    {
        foreach ($this->availableActions as $s_action => $a_vals) {
            define($a_vals[0], $s_action);
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
        require_once dirname(__FILE__) . '/Auth.php';
        $o_auth =& FastFrame_Auth::singleton();
        if (!$o_auth->checkAuth()) {
            $o_auth->logout();
        }
    }

    // }}}
}
?>
