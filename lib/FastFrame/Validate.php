<?php
/** $Id: Validate.php,v 1.1 2003/02/22 01:49:08 jrust Exp $ */
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

require_once dirname(__FILE__) . '/Result.php';

// }}}
// {{{ class FF_Validate

/**
 * The FF_Validate:: class is used to validate a model object.  It filters user input to
 * make sure it is valid and when complex validation methods are needed (such as determining
 * if the username is unique) it asks the model about it.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package FastFrame 
 */

// }}}
class FF_Validate {
    // {{{ properties

    /**
     * The model object we are working with
     * @type object 
     */
    var $o_model;

    /**
     * The result object that we return from the validate method
     * @type object
     */
    var $o_result;

    /**
     * Whether or not this is an update or a new model we are verifying
     * @type bool
     */
    var $isUpdate;

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @param object $in_model The model object we will be validating
     * @param bool $in_isUpdate Is this an update of the model?
     *
     * @access public
     * @return void
     */
    function FF_Validate(&$in_model, $in_isUpdate)
    {
        $this->o_model =& $in_model; 
        $this->isUpdate = $in_isUpdate;
        $this->o_result =& new FF_Result();
    }

    // }}}
    // {{{ validate()

    /**
     * Checks the model to make sure all its data is valid.  Returns a result object with
     * any error messages.
     *
     * @access public
     * @return object A Result object
     */
    function validate()
    {
        return $this->o_result;
    }

    // }}}
}
?>
