<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2004 The Codejanitor Group                        |
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

require_once dirname(__FILE__) . '/../Action.php';
require_once dirname(__FILE__) . '/../FileCache.php';

// }}}
// {{{ class FF_Action_Download 

/**
 * The FF_Action_Download:: class handles pushing a download of a
 * specified file from the file cache.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package Action 
 */

// }}}
class FF_Action_Download extends FF_Action {
    // {{{ run()
    
    /**
     * Sends out the file to be downloaded.
     *
     * @access public
     * @return object The next action object
     */
    function run()
    {
        $o_fileCache =& FF_FileCache::singleton();
        if (!$o_fileCache->exists(FF_Request::getParam('objectId', 'g'), FF_Request::getParam('useApp', 'g', false))) {
            $this->o_output->setMessage(_('The specified file could not be found.'), FASTFRAME_ERROR_MESSAGE, true);
            $this->o_nextAction->setActionId(ACTION_PROBLEM);
            return $this->o_nextAction;
        }

        $s_file = $o_fileCache->getPath(FF_Request::getParam('objectId', 'g'), FF_Request::getParam('useApp', 'g', false));
        $s_name = basename($s_file);
        if (!FastFrame::isEmpty(FF_Request::getParam('prefix', 'g'))) {
            $s_name = substr($s_name, strlen(FF_Request::getParam('prefix', 'g')));
        }

        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/force-download');
        header('Content-Disposition: attachment; filename=' . $s_name);
        header('Content-Description: File Transfer');
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . filesize($s_file));
        @readfile($s_file);
        exit;
    }

    // }}}
}
?>
