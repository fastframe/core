<?php
/** $Id: Action.php,v 1.2 2003/02/22 02:07:56 jrust Exp $ */
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

require_once dirname(__FILE__) . '/Output.php';
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
     * FastFrame_Registry instance
     * @type object
     */
    var $o_registry;

    /**
     * Output instance
     * @type object 
     */
    var $o_output;

    /**
     * The next action object which is returned from all run() methods
     * @type object
     */
    var $o_nextAction;

    /**
     * The DataAccess object
     * @type object
     */
    var $o_dataAccess;

    /**
     * The current actionId
     * @type string
     */
    var $currentActionId;

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function FF_Action()
    {
        $this->o_registry =& FastFrame_Registry::singleton();
        $this->o_output =& FastFrame_Output::singleton();
        $this->o_nextAction =& new FF_NextAction();
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
    // {{{ setCurrentActionId()

    /**
     * Sets the current action id that this action was called with
     *
     * @param string $in_actionId The current action Id
     *
     * @access public
     * @return void
     */
    function setCurrentActionId($in_actionId)
    {
        $this->currentActionId = $in_actionId;
    }

    // }}}
    // {{{ setDataAccessObject()

    /**
     * Sets the data access object
     *
     * @param object $in_dao The data access object
     *
     * @access public
     * @return void
     */
    function setDataAccessObject(&$in_dao)
    {
        $this->o_dataAccess =& $in_dao;
    }

    // }}}
}
?>
