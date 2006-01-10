<?php
/** $Id$ */
// {{{ license

// +----------------------------------------------------------------------+
// | FastFrame Application Framework                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2005 The Codejanitor Group                        |
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
// {{{ class FF_FileCache

/**
 * The FF_FileCache:: class provides methods for saving, retrieving, and
 * removing files from the data cache.
 *
 * @author  Jason Rust <jrust@codejanitor.com>
 * @version Revision: 1.0 
 * @access  public
 * @package FastFrame
 */

// }}}
class FF_FileCache {
    // {{{ properties
    
    /**
     * The registry object
     * @var object
     */
    var $o_registry;

    // }}}
    // {{{ constructor

    /**
     * Set variables on class initialization.
     *
     * @access public
     * @return void
     */
    function FF_FileCache()
    {
        $this->o_registry =& FF_Registry::singleton();
    }

    // }}}
    // {{{ singleton()

    /**
     * To be used in place of the contructor to return any open instance.
     *
     * @access public
     * @return object FF_FileCache instance
     */
    function &singleton()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new FF_FileCache();
        }

        return $instance;
    }

    // }}}
    // {{{ save()

    /**
     * Saves a file to cache.
     *
     * @param string $in_data The file data
     * @param string $in_filePath The subpath an filename (i.e.
     *        css/gecko.css)
     * @param bool $in_useApp (optional) Save it into the current app
     *        dir?
     *
     * @access public
     * @return object A success object
     */
    function save($in_data, $in_filePath, $in_useApp = false)
    {
        $o_result = new FF_Result();
        $this->checkDir($in_filePath, $in_useApp);
        $s_path = $this->getPath($in_filePath, $in_useApp);
        require_once 'File.php';
        $tmp_result = File::write($s_path, $in_data, FILE_MODE_WRITE);
        if (PEAR::isError($tmp_result)) {
            $o_result->setSuccess(false);
            $o_result->addMessage($tmp_result->getMessage());
        }

        File::close($s_path, FILE_MODE_WRITE);
        return $o_result;
    }

    // }}}
    // {{{ saveUploadedFile()

    /**
     * Saves an uploaded file to cache.  Does all the checks to make
     * sure it is a valid upload file and within the size limits.
     *
     * @param array $in_uploadData The data about the file from $_FILES
     * @param string $in_filePath The subpath an filename (i.e.
     *        12.jpg)
     * @param int $in_maxSize The max size of the file.  Pass 0 for no
     *        max size.
     *  @param array $in_validTypes (optional) An array of valid file
     *         types.
     * @param bool $in_useApp (optional) Save it into the current app
     *        dir?
     *
     * @access public
     * @return object A success object
     */
    function saveUploadedFile($in_uploadData, $in_filePath, $in_maxSize, $in_validTypes = array(), $in_useApp = false)
    {
        $o_result = new FF_Result();
        // See if a file was sent
        if (empty($in_uploadData['name']) || $in_uploadData['name'] == 'none') {
            $o_result->addMessage(_('You must specify a name for your attachment.'));
            $o_result->setSuccess(false);
            return $o_result;
        }

        if ($in_uploadData['size'] == 0) {
            $o_result->addMessage(_('The file you tried to attach contains no data.'));
            $o_result->setSuccess(false);
            return $o_result;
        }

        // Check size
        if ($in_maxSize > 0 && 
            ((isset($in_uploadData['error']) && 
              ($in_uploadData['error'] == 1 || $in_uploadData['error'] == 2)) ||
             $in_uploadData['size'] > $in_maxSize)) {
            require_once dirname(__FILE__) . '/Util.php';
            $o_result->addMessage(sprintf(_('The uploaded file was too big.  The maximum size is %s.'),
                        FF_Util::bytesToHuman($in_maxSize)));
            $o_result->setSuccess(false);
            return $o_result;
        }

        if (!is_uploaded_file($in_uploadData['tmp_name'])) {
            $o_result->addMessage(_('There was an error uploading the file.'));
            $o_result->setSuccess(false);
            return $o_result;
        }

        if (count($in_validTypes) > 0 && !in_array($in_uploadData['type'], $in_validTypes)) {
            $o_result->addMessage(_('The uploaded file was not a valid type.'));
            $o_result->setSuccess(false);
            return $o_result;
        }

        $this->checkDir($in_filePath, $in_useApp);
        if (!move_uploaded_file($in_uploadData['tmp_name'], $this->getPath($in_filePath, $in_useApp))) {
            $o_result->addMessage(_('Could not save the uploaded file.'));
            $o_result->setSuccess(false);
            return $o_result;
        }

        return $o_result;
    }

    // }}}
    // {{{ makeViewableFromWeb()
    
    /**
     * Makes the files in the cache directory viewable by web browsers.
     * By default access is denied to the files.
     *
     * @param string $in_filePath The subpath an filename (i.e.
     *        css/gecko.css)
     * @param bool $in_useApp (optional) Save it into the current app
     *        dir?
     *
     * @access public
     * @return void
     */
    function makeViewableFromWeb($in_filePath, $in_useApp = false)
    {
        $a_parts = pathinfo($in_filePath);
        if (!$this->exists($a_parts['dirname'] . '/.htaccess', $in_useApp)) {
            $o_reult =& $this->save('Allow from all', $a_parts['dirname'] . '/.htaccess', $in_useApp);
        }
    }

    // }}}
    // {{{ exists()

    /**
     * Checks if a cache file exists
     *
     * @param string $in_filePath The subpath an filename (i.e.
     *        css/gecko.css)
     * @param bool $in_useApp (optional) Check in the current app dir?
     *
     * @access public
     * @return bool True if it exists, false otherwise
     */
    function exists($in_filePath, $in_useApp = false)
    {
        // If it's a directory then don't test existence
        if (substr($in_filePath, -1) == '/') {
            return false;
        }

        return file_exists($this->getPath($in_filePath, $in_useApp));
    }

    // }}}
    // {{{ getFilesByPrefix()

    /**
     * Returns all the files with the specified prefix.
     *
     * @param string $in_prefix The file prefix
     * @param string $in_filePath The subpath an filename (i.e.
     *        css/gecko.css)
     * @param bool $in_useApp (optional) Check in the current app dir?
     *
     * @access public
     * @return array The array of file names 
     */
    function getFilesByPrefix($in_prefix, $in_filePath, $in_useApp = false)
    {
        $a_files = array();
        if ($handle = @opendir($this->getPath($in_filePath, $in_useApp))) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..' && strpos($file, $in_prefix) === 0) {
                    $a_files[] = $file;
                }
            }

            closedir($handle);
        }

        return $a_files;
    }

    // }}}
    // {{{ remove()

    /**
     * Remove an uploaded file.
     *
     * @param string $in_filePath The subpath an filename (i.e.
     *        css/gecko.css)
     * @param bool $in_useApp (optional) Look in the current app dir?
     *
     * @access public
     * @return void
     */
    function remove($in_filePath, $in_useApp = false)
    {
        @unlink($this->getPath($in_filePath, $in_useApp));
    }

    // }}}
    // {{{ getPath()

    /**
     * Gets the path to the cache file
     *
     * @param string $in_filePath The subpath an filename (i.e.
     *        css/gecko.css)
     * @param bool $in_useApp (optional) Check in the current app dir?
     * @param int $in_type (optional) What type of path to build, based
     *        on the constants (default is FASTFRAME_FILEPATH)
     *
     * @access public
     * @return string The path to the cache file
     */
    function getPath($in_filePath, $in_useApp = false, $in_type = FASTFRAME_FILEPATH)
    {
        $s_app = $in_useApp ? $this->o_registry->getCurrentApp() . '/' : '';
        return $this->o_registry->getRootFile($s_app . $in_filePath, 'data', $in_type);
    }

    // }}}
    // {{{ checkDir()

    /**
     * Checks to make sure the directory is created for the cache file
     *
     * @param string $in_filePath The subpath an filename (i.e.
     *        css/gecko.css)
     * @param bool $in_useApp (optional) Check inthe current app dir?
     *
     * @access public
     * @return void
     */
    function checkDir($in_filePath, $in_useApp = false)
    {
        $a_parts = pathinfo($this->getPath($in_filePath, $in_useApp));
        if (!is_dir($a_parts['dirname'])) {
            require_once 'System.php';
            if (!@System::mkdir("-p {$a_parts['dirname']}"))  {
                trigger_error('Could not write to the FastFrame data directory.', E_USER_ERROR);
            }
        }
    }

    // }}}
}
?>
