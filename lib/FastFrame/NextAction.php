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
// {{{ class FF_NextAction

/**
 * The FF_NextAction:: class handles the setting of what the next action to take is.  All
 * actions must return an instance of this object so the ActionHandler knows what to do
 * next.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package Action 
 */

// }}}
class FF_NextAction{
    // {{{ properties

    /**
     * Next action Id 
     * @var string 
     */
    var $nextActionId;

    /**
     * Next app Id 
     * @var string 
     */
    var $nextAppId;

    /**
     * Next module Id 
     * @var string 
     */
    var $nextModuleId;

    /**
     * Is this the last action
     * @var boolean
     */
    var $lastAction = true;
    
    // }}}
    // {{{ isLastAction()

    /**
     * Tells if this the last action to be taken in the event loop
     *
     * @access public
     * @return bool True if this is the last action, false otherwise
     */
    function isLastAction()
    {
        return $this->lastAction;
    }

    // }}}
    // {{{ setNextAppId()

    /**
     * Sets the next application id.
     *
     * @param string $in_appId The app id
     * 
     * @access public
     * @return void
     */
    function setNextAppId($in_appId)
    {
        $this->lastAction = false;
        $this->nextAppId = $in_appId;
    }

    // }}}
    // {{{ getNextAppId()

    /**
     * Gets the next app Id
     *
     * @access public
     * @return string The next app id
     */
    function getNextAppId()
    {
        return $this->nextAppId;
    }

    // }}}
    // {{{ setNextModuleId()

    /**
     * Sets the next module id.
     *
     * @param string $in_moduleId The module id
     * 
     * @access public
     * @return void
     */
    function setNextModuleId($in_moduleId)
    {
        $this->lastAction = false;
        $this->nextModuleId = $in_moduleId;
    }

    // }}}
    // {{{ getNextModuleId()

    /**
     * Gets the next module Id
     *
     * @access public
     * @return string The next module id
     */
    function getNextModuleId()
    {
        $this->lastAction = false;
        return $this->nextModuleId;
    }

    // }}}
    // {{{ setNextActionId()

    /**
     * Sets the next action id.  This also sets the lastAction to false, since it assumes we
     * have some place to go if we are setting this.
     *
     * @param string $in_actionId The action id
     * 
     * @access public
     * @return void
     */
    function setNextActionId($in_actionId)
    {
        $this->lastAction = false;
        $this->nextActionId = $in_actionId;
    }

    // }}}
    // {{{ getNextActionId()

    /**
     * Gets the next action Id
     *
     * @access public
     * @return string The next action id
     */
    function getNextActionId()
    {
        return $this->nextActionId;
    }

    // }}}
}
?>
