<?php
/** $Id: Problem.php,v 1.4 2003/02/12 20:51:43 jrust Exp $ */
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

require_once dirname(__FILE__) . '/GenericProblem.php';

// }}}
// {{{ class ActionHandler_Problem 

/**
 * The ActionHandler_Problem:: class handles the occurrence of a problem on the
 * page. 
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class ActionHandler_Problem extends ActionHandler_Action {
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_Problem()
    {
        ActionHandler_Action::ActionHandler_Action();
    }

    // }}}
    // {{{ run()
    
    /**
     * Registers the problem message with the output class.
     *
     * @access public
     * @return void
     */
    function run()
    {
        $this->o_output->setMessage($this->getProblemMessage(), FASTFRAME_ERROR_MESSAGE);
        $this->o_output->output();
    }

    // }}}
    // {{{ getProblemMessage()

    /**
     * Gets the problem message
     *
     * @access public
     * @return void
     */
    function getProblemMessage()
    {
        return _('A problem was encountered in processing your request.'), 
    }

    // }}}
}
?>
