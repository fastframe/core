<?php
/** $Id: ActionHandler.php,v 1.2 2003/02/08 00:10:54 jrust Exp $ */
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
// {{{ class ActionHandler 

/**
 * The ActionHandler:: class handles page actions.
 *
 * Actions are are passed via a GET/POST actionId.  The action handler is meant to be
 * extended by each application and module to override the default method for each possible
 * action that may take place in the application.  This level in the framework structure
 * should handle all incoming actions and pass them off to the Application class which will
 * handle any manipulation of data.  It also handles the creation of the output seen by the
 * user, be it error messages, list pages, or forms.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class ActionHandler {
    // {{{ properties

    /**
     * The actionId.  One of the above-defined constants
     * @type string 
     */
    var $actionId;

    /**
     * The application object.  Used for permforming actions on the database or performing
     * application specific logic.
     * @type object
     */
    var $o_application;

    /**
     * The default actionId for when an invalid actionId is passed in
     * @type string 
     */
    var $defaultActionId;

    /**
     * The array of default available actions (which are turned into constants later)
     * The key is the action ID.  0 => the name of the constant.  1 => the relative path
     * from here to the class file, 2 => the class name
     * @type array
     */
    var $availableActions = array(
        'problem'       => array('ACTION_PROBLEM', 'ActionHandler/Problem.php', 'ActionHandler_Problem'),
        'add'           => array('ACTION_ADD', 'ActionHandler/Add.php', 'ActionHandler_Add'),
        'add_submit'    => array('ACTION_ADD_SUBMIT', 'ActionHandler/AddSubmit.php', 'ActionHandler_AddSubmit'),
        'edit'          => array('ACTION_EDIT', 'ActionHandler/Edit.php', 'ActionHandler_Edit'),
        'edit_submit'   => array('ACTION_EDIT_SUBMIT', 'ActionHandler/EditSubmit.php', 'ActionHandler_EditSubmit'),
        'view'          => array('ACTION_VIEW', 'ActionHandler/View.php', 'ActionHandler_View'),
        'delete'        => array('ACTION_DELETE', 'ActionHandler/Delete.php', 'ActionHandler_Delete'),
        'list'          => array('ACTION_LIST', 'ActionHandler/List.php', 'ActionHandler_List'),
        'export'        => array('ACTION_EXPORT', 'ActionHandler/Export.php', 'ActionHandler_Export'),
        'login'         => array('ACTION_LOGIN', 'ActionHandler/Login.php', 'ActionHandler_Login'),
        'login_submit'  => array('ACTION_LOGIN_SUBMIT', 'ActionHandler/LoginSubmit.php', 'ActionHandler_LoginSubmit'),
        'logout'        => array('ACTION_LOGOUT', 'ActionHandler/Logout.php', 'ActionHandler_Logout'),
        'activate'      => array('ACTION_ACTIVATE', 'ActionHandler/Activate.php', 'ActionHandler_Activate'),
        'display'       => array('ACTION_DISPLAY', 'ActionHandler/Display.php', 'ActionHandler_Display'),
    );

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @param object $in_appObject The application object. 
     *
     * @access public
     * @return void
     */
    function ActionHandler(&$in_appObject)
    {
        $this->o_application =& $in_appObject;
        $this->_initializeDefaultConstants();
        $this->_makeActionPathsAbsolute();
        $this->setActionId(FastFrame::getCGIParam('actionId', 'gp'));
        FastFrame::setPersistentData('actionId', $this->actionId);
    }

    // }}}
    // {{{ singleton()

    /**
     * To be used in place of the contructor to return any open instance.
     *
     * This function is used in place of the contructor to return any open instances
     * of the html object, and if none are open will create a new instance and cache
     * it using a static variable
     *
     * @param object $in_dataObject (optional) The data object.  If not passed in then we
     *               assume this app does not use a database.
     *
     * @access public
     * @return object ActionHandler instance
     */
    function &singleton($in_dataObject = null)
    {
        static $o_instance;
        if (!isset($o_instance)) {
            $o_instance = new ActionHandler($in_dataObject);
        }

        return $o_instance;
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

        $pth_actionFile = $this->availableActions[$this->actionId][1];
        if (file_exists($pth_actionFile)) {
            require_once $pth_actionFile;
            $o_action = new $this->availableActions[$this->actionId][2];
            $o_action->o_application =& $this->o_application;
            $o_action->run();
        }
        else {
            PEAR::raiseError(null, FASTFRAME_ERROR, null, E_USER_ERROR, "The class file for action $this->actionId ($pth_actionFile) does not exist", 'FastFrame_Error', true);
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
}
?>
