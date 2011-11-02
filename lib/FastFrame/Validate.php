<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2009 The Codejanitor Group                        |
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
     * @var object 
     */
    var $o_model;

    /**
     * The result object that we return from the validate method
     * @var object
     */
    var $o_result;

    /**
     * Whether or not this is an update or a new model we are verifying
     * @var bool
     */
    var $isUpdate;

    /**
     * Additional data that was inputted by the user and needs to be validated
     * but is not part of the model
     * @var array
     */
    var $additionalData = array();

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @param object $in_model The model object we will be validating
     * @param bool $in_isUpdate Is this an update of the model?
     * @param array $in_additionalData (optional) If there is extra data needed by the
     *              validation class, but is not part of the model (i.e. password2), then
     *              pass it in here.
     *
     * @access public
     * @return void
     */
    function FF_Validate(&$in_model, $in_isUpdate, $in_additionalData = array())
    {
        $this->o_model =& $in_model; 
        $this->isUpdate = $in_isUpdate;
        $this->o_result = new FF_Result();
        $this->additionalData = $in_additionalData;
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
    // {{{ setAdditionalData()

    /**
     * Sets the additional data variable
     *
     * @param array $in_data Any additional data to put into the additionalData array
     *
     * @access public
     * @return void
     */
    function setAdditionalData($in_data)
    {
        $this->additionalData = array_merge($this->additionalData, $in_data);
    }

    // }}}
    // {{{ validateFormToken

    /**
     * Validates the token submitted by the form. Clears the token from the form
     * tokens if it exists as the form will generate a new token.
     *
     * @access private
     * @return void
     */
    function validateFormToken()
    {
        $s_app = FF_Registry::singleton()->getCurrentApp();
        $s_token = FF_Request::getParam('token', 'p', null);
        $b_isValid = true;
        if (empty($s_token) || empty($_SESSION['ff_form_tokens'][$s_token])) {
            $b_isValid = false;
        }
        else {
            if ($_SESSION['ff_form_tokens'][$s_token]['app'] != $s_app) {
                $b_isValid = false;
            }
            unset($_SESSION['ff_form_tokens'][$s_token]);
        }

        if (!$b_isValid) {
            $this->o_result->addMessage(_('Invalid form data submitted.'));
            $this->o_result->setSuccess(false);
        }
    }
    
    // }}}
}
?>
