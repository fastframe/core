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

require_once dirname(__FILE__) . '/Perms.php';
require_once dirname(__FILE__) . '/NextAction.php';

// }}}
// {{{ class FF_Action

/**
 * The FF_Action:: class is the super class for all actions 
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class FF_Action {
    // {{{ properties

    /**
     * The registry object
     * @var object
     */
    var $o_registry;

    /**
     * Output instance
     * @var object 
     */
    var $o_output;

    /**
     * The next action object which is returned from all run() methods
     * @var object
     */
    var $o_nextAction;

    /**
     * The primary model object 
     * @var object
     */
    var $o_model;

    /**
     * The permissions object
     * @var object
     */
    var $o_perms;

    /**
     * The current actionId
     * @var string
     */
    var $currentActionId;

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @param object $in_model The model object
     *
     * @access public
     * @return void
     */
    function FF_Action(&$in_model)
    {
        $o_actionHandler =& FF_ActionHandler::singleton();
        $this->currentActionId = $o_actionHandler->getActionId();
        $this->o_output =& FF_Output::singleton();
        $this->o_nextAction =& new FF_NextAction();
        $this->o_registry =& FF_Registry::singleton();
        $this->o_perms =& FF_Perms::factory();
        $this->o_model =& $in_model;
    }

    // }}}
    // {{{ run()

    /**
     * Interface run class
     *
     * @access public
     * @returns object The next action object
     */
    function run()
    {
        return $this->o_nextAction; 
    }

    // }}}
}
?>
