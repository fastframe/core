<?php
/** $Id: Model.php,v 1.1 2003/02/22 01:49:08 jrust Exp $ */
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
     * @type object
     */
    var $o_dataAccess;

    // }}}
    // {{{ constructor

    /**
     * Initialize any needed class variables
     *
     * @param object $in_dataAccess The data access object
     *
     * @access public
     * @return void
     */
    function FF_Model(&$in_dataAccess)
    {
        $this->o_dataAccess =& $in_dataAccess;
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
}
?>
