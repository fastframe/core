<?php
/** $Id: AddSubmit.php,v 1.2 2003/02/08 00:10:55 jrust Exp $ */
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

require_once dirname(__FILE__) . '/EditSubmit.php';

// }}}
// {{{ class ActionHandler_AddSubmit

/**
 * The ActionHandler_Edit:: class handles the processing of the form information and adds a
 * new record to the database. 
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package ActionHandler 
 */

// }}}
class ActionHandler_AddSubmit extends ActionHandler_EditSubmit {
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function ActionHandler_AddSubmit()
    {
        ActionHandler_EditSubmit::ActionHandler_EditSubmit();
    }

    // }}}
    // {{{ updateData()
        
    /**
     * Adds the data with the new form values.
     *
     * @access public
     * @return bool True if the insert succeeded, false otherwise
     */
    function updateData()
    {
        return $this->o_application->insertData($GLOBALS['_' . $this->formAction]);
    }

    // }}}
    // {{{ getSuccessMessage()

    /**
     * Gets the message for a successful add 
     *
     * @access public
     * @return string The message
     */
    function getSuccessMessage()
    {
        return _('Added the data successfully.');
    }

    // }}}
    // {{{ getProblemMessage()

    /**
     * Gets the message for when adding the data fails 
     *
     * @access public
     * @return string The message
     */
    function getProblemMessage()
    {
        return _('There was an error in adding your information.');
    }

    // }}}
    // {{{ setProblemActionId()

    /**
     * Sets the actionId to invoke when an error occurs in the update of the database. 
     *
     * @access public
     * @return void
     */
    function setProblemActionId()
    {
        $this->setActionId(ACTION_ADD);
    }

    // }}}
}
?>
