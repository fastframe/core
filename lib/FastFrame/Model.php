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

require_once dirname(__FILE__) . '/DataAccess.php';

// }}}
// {{{ class FF_Model 

/**
 * The FF_Model:: class is the abstract class to represent a model.  
 *
 * A model is a conception of a thing or item (such as a user) made into a class to
 * represent that thing.  A model is made persistent by using to DataAccess class to save or
 * edit it.  Likewise a new model is objtained through methods in the DataAccess class.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package FastFrame 
 */

// }}}
class FF_Model {
    // {{{ properties

    /**
     * The data access object. 
     * @var object
     */
    var $o_dataAccess;

    /**
     * The primary id field
     * @var int
     */
    var $id;

    // }}}
    // {{{ constructor

    /**
     * Initialize any needed class variables
     *
     * @access public
     * @return void
     */
    function FF_Model()
    {
        $this->_initDataAccess();
    }

    // }}}
    // {{{ reset()

    /**
     * Retets the model's data properties to their initial values (usually null)
     *
     * @access public
     * @return void
     */
    function reset()
    {
        // interface
    }

    // }}}
    // {{{ remove()

    /**
     * Removes the current model or the one specified
     *
     * @param int $in_id (optional) The id to delete.  If not specified then the
     *            current id is used
     *
     * @access public
     * @return object A result object 
     */
    function remove($in_id = null)
    {
        $s_id = is_null($in_id) ? $this->getId() : $in_id;
        return $this->o_dataAccess->remove($s_id);
    }

    // }}}
    // {{{ save()

    /**
     * Saves the current model to the persistent layer
     *
     * @param bool $in_isUpdate Is this an update?  Otherwise we add.
     *
     * @access public
     * @return object The result object
     */
    function save($in_isUpdate)
    {
        if ($in_isUpdate) {
            return $this->o_dataAccess->update($this->exportToArray());
        }
        else { 
            $this->setId($this->o_dataAccess->getNextId());
            return $this->o_dataAccess->add($this->exportToArray());
        }
    }

    // }}}
    // {{{ importFromArray()

    /**
     * Imports an array of data used the dataccess constants as keys into the model's
     * properties
     *
     * @param array $in_data The data array
     *
     * @access public
     * @return void
     */
    function importFromArray($in_data)
    {
        // interface
    }

    // }}}
    // {{{ exportToArray()

    /**
     * Exports the data properties to an array using the dataaccess constants as keys
     *
     * @param array $in_data The data array
     *
     * @access public
     * @return array The array of data 
     */
    function exportToArray()
    {
        // interface
    }

    // }}}
    // {{{ fillById()

    /**
     * Fills the models data in based on the id field
     *
     * @param int $in_id The id field
     *
     * @access public
     * @return bool True if fill succeeded, false otherwise
     */
    function fillById($in_id)
    {
        $this->reset();
        $a_data = $this->o_dataAccess->getDataByPrimaryKey($in_id);
        if (count($a_data) == 0) {
            return false;
        }
        else {
            $this->importFromArray($a_data);
            return true;
        }
    }

    // }}}
    // {{{ getId()

    /**
     * Returns the primary key field
     *
     * @access public
     * @return int The primary key field
     */
    function getId()
    {
        return $this->id;
    }
    
    // }}}
    // {{{ setId()

    /**
     * Sets the primary key field
     *
     * @param int $in_id The primary key id
     *
     * @access public
     * @return void 
     */
    function setId($in_id)
    {
        $this->id = $in_id;
    }
    
    // }}}
    // {{{ getDataAccessObject()

    /**
     * Gets the data access object
     *
     * @access public
     * @return object The data access object
     */
    function &getDataAccessObject()
    {
        return $this->o_dataAccess;
    }

    // }}}
    // {{{ _initDataAccess()

    /**
     * Initializes the data access object
     *
     * @access private
     * @return void
     */
    function _initDataAccess()
    {
        // interface
    }

    // }}}
}
?>
