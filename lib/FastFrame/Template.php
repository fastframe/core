<?php
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
// | Authors: Dan Allen <dan@mojavelinux.com>                             |
// +----------------------------------------------------------------------+

// }}}
/**
 * RULES:
 * - if you branch a block from a variable, it will branch from where the variable is
 *   owned and will leave behind the old variable for all other places...so block references
 *   are not, and maybe never be, implemented...if we did do this we would have to cache
 *   block results somewhere and use that when plugging in, but the results could still be
 *   unpredictable
 *
 * - you only have to cycle a block if it is going to face multiple cycles...you don't have
 *   to cycle it for that one time use deal
 *
 * TODO:
 * - 4th param to assignBlockData, if data has already been assigned to variable, append new data
 * - don't have to check for global block if just make global block persistent when creating blockList
 * - add a settings function which will set things like leaveUnparsedBlocks, leaveUnparsedVariables and parseUntouchedBlocks
 * - be able to append to a variable after it is assigned
 * - add a bit more error throwing
 * - we might be able to get away with caching branched blocks by having fixed length namespaces...then we can just do a string replace an not worry about destroying serialization
 */
// {{{ includes

require_once 'PEAR.php';

// }}}
// {{{ constants

define('FASTFRAME_TEMPLATE_OK',                   0);
define('FASTFRAME_TEMPLATE_ERROR',               -1);
define('FASTFRAME_TEMPLATE_PARSE_ERROR',         -2);
define('FASTFRAME_TEMPLATE_BLOCK_NOT_FOUND',     -3);
define('FASTFRAME_TEMPLATE_INVALID_BLOCK_NAME',  -4);
define('FASTFRAME_TEMPLATE_DUPLICATE_BLOCK',     -5);
define('FASTFRAME_TEMPLATE_CYCLE_NOT_ALLOWED',   -6);
define('FASTFRAME_TEMPLATE_FILE_NOT_FOUND',      -7);

define('FASTFRAME_TEMPLATE_GLOBAL_BLOCK', '**global**');
// }}}
// {{{ class FF_Template

/**
 * A template class which allows the programmer to abstract the HTML from the programming
 *
 * @author   Dan Allen <dan@mojavelinux.com>
 * @version  Revision 1.0
 * @access   public
 * @package  FastFrame
 */

// }}}
class FF_Template {
    // {{{ properties

    var $debug = false;
    /**
     * Array of accumulated errors, used primarily for non-fatal error accumulation.  Fatal
     * errors are returned when they occur.
     * @var array $errorStack
     */
    var $errorStack = array();

    /**
     * [!] Still need to implement this [!]
     * @var string $globalBlockName
     */
    var $globalBlockName = FASTFRAME_TEMPLATE_GLOBAL_BLOCK;

    /**
     * Regular expression used to test for a valid blockname.
     * @var string $blockNamePattern
     */    
    var $blockNamePattern = '[[:alnum:]_]+';
    
    /**
     * Regular expression built to locate variables in a block.
     * @var $variableLocatorPattern
     * @see constructor
     */
    var $variableLocatorPattern;

    /**
     * Regular expression built to locate blocks within the template.
     * @var $blockLocatorPattern
     * @see constructor
     */
    var $blockLocatorPattern;

    /**
     * Regular expression used for blocks in the template.  The template is used because
     * sometimes we want to match the general pattern for a block variable and sometimes
     * we are looking for a specific one.
     * @var string $blockPatternTemplate
     */
    var $blockPatternTemplate = '<!-- BLOCK (%s) -->(\s*<!-- DELIMITER -->(.*?)<!-- /DELIMITER -->)?\s*(.*)<!-- /BLOCK \1 -->';

    /**
     * Used for creating new blocks
     * @var $blockTemplate
     */
    var $blockTemplate = '<!-- BLOCK %s -->%s<!-- /BLOCK %s -->';

    /**
     * Runtime regular expression used for blocks in the template.  This is created by
     * using the block template and filling in the $blockNamePattern using sprintf()
     * @var string $blockPattern, contructor
     * @see $blockPatternTemplate
     */
    var $blockPattern = '';

    /**
     * Regular expression used to find include files in the template.
     * @var string $includePattern
     */
    var $includePattern = '<!-- INCLUDE (.*) -->';

    /**
     * Runtime regular expression used for finding include files in the template.
     * @var string $includeLocatorPattern
     */
    var $includeLocatorPattern = '';

    /**
     * Regular expression matching a variable name in the template
     * @var string $variableNamePattern
     * @see $blockNamePattern, $variableWrapper, constructor
     */
    var $variableNamePattern    = '[[:alnum:]_]+';

    /**
     * Runtime regular expression used to find a variable in the template. This is created
     * by surrounding this pattern with the variableWrappers
     * @var string $variablePattern
     * @see $variableWrappers, contructor
     */
    var $variablePattern = '';

    /**
     * An array of size 2 that contains the start and end delimiters.
     * @var string $variableWrapper
     * @see $variableNamePattern
     */
    var $variableWrapper = array('{', '}');

    /**
     * Toggles whether untouched blocks are permitted to be openned for parsing.
     * This is good to disabled if you know you have no conditional blocks.
     * @var boolean $parseUntouchedBlocks
     */
    var $parseUntouchedBlocks = false;

    /**
     * Controls the handling of unassigned variables, default is to remove them.
     * @var boolean $removeUnusedVariables
     */
    var $removeUnusedVariables = true;

    /**
     * Controls the handling of untouched blocks, default is to remove them.
     * It will actually be somewhat slower to leave them in because we have to
     * rebuild the template underneath them.
     * @var boolean $removeUntouchedBlocks
     */
    var $removeUntouchedBlocks = true;

    /**
    * Name of the current block.  We begin with the global block, which is assigned in load()
    * @var string $currentBlockName
    * @see load()
    */
    var $currentBlockName = null;

    /**
     * Original content of the template.  Once the tree is loaded this variable is cleared
     * @var string $template
     */
    var $template = '';

    /**
     * Flag specifing whether or not a template is currently loaded.  This flag
     * can be toggled after loading a template with a call to reset()
     * @var boolean $templateLoaded
     * @see reset(), load()
     */
    var $templateLoaded = false;

    /**
     * Array of all blocks and their content.  This is an associative array
     * which stores the following keys:
     *   'contents'  => string  the template contents (this is the original template code)
     *   'data'      => array   variable assignments as an array of rows
     *   'result'    => string  result from the parsing of the block
     *   'delimiter' => string  the string used between iterations of a block
     *   'children'  => array   All the child blocks in the given block, with the number of 
     *                          iterations that the child should receive
     *   'parent'    => string  name of parent block
     *   'variables' => array   list of variables, values is either owns or inherited
     *   'parsed'    => int     number of times the block has been parsed
     *   'persistent'=> bool    auto-touch, block does not need to be touched or assigned data to be parsed
     *   'callback'  => string  filter function that will be run on the block after parsing it
     * @var array $blockList
     * @see _build_block_tree()
     */
    var $blockList = array();

    /**
     * Variables which are available to the current block when parsing it.  This
     * array is a temporary storage location of variable references.
     * @var array $availableVariables
     * @see parse()
     */
    var $availableVariables = array();

    var $persistentBlocks = array();

    /**
     * Root directory for all file operations, which gets prefixed to all filenames given.
     * @var string $fileRoot
     * @see setDirectory()
     */
    var $fileRoot = './';

    /**
     * Internal flag indicating that a blockName was used multiple times.
     * @var boolean $flagBlockTrouble
     */
    var $flagBlockTrouble = false;

    /**
     * Array of variables which have been claimed by a parent block.
     * This array is critical in building the ownership of each variable
     * so that we know when it is appropriate to delete the variables from the cache.
     * @var array $ownedVariables
     */
    var $ownedVariables = array();

    /**
     * Cache the build templates so that we don't have to ever rebuild them again.  These
     * templates are stored as serialized arrays (it is just the blockList)
     * @var array $templateCache
     * @see $blockList, load()
     */
    var $templateCache = array();

    // }}}
    // {{{ constructor

    /**
     * Establish the regular expressions to search for elements in the template and optionally
     * set the root file directory which is used when loading files.
     *
     * @param  string file root directory, which is the prefix for all filenames
     *
     * @see setDirectory()
     */
    function FF_Template($in_directory = null) 
    {
        // build expression for locating variables in the template
        $this->variableLocatorPattern = ';' . $this->variableWrapper[0] . '(' . $this->variableNamePattern . ')' . $this->variableWrapper[1] . ';s';

        // wrap the regular expression in delimiters for checking names when adding new blocks
        $this->blockValidatePattern = ';' . $this->blockNamePattern . ';';

        // build the regular expression for matching full blocks
        $this->blockLocatorPattern = ';' . sprintf($this->blockPatternTemplate, $this->blockNamePattern . '|' . preg_quote($this->globalBlockName)) . ';s';

        // build the regular expression for matching files
        $this->includeLocatorPattern = ';' . $this->includePattern . ';ie';

        if (!is_null($in_directory)) {
            $this->setDirectory($in_directory);
        }
    }

    // }}}
    // {{{ string  errorMessage()

    /**
     * Return a textual error message for a IT error code
     *
     * @param integer $value error code
     *
     * @return string error message, or false if the error code was not recognized
     */
    function errorMessage($value)
    {
        static $errorMessages;
        if (!isset($errorMessages)) {
            $errorMessages = array(
                FASTFRAME_TEMPLATE_OK                  => 'no error',
                FASTFRAME_TEMPLATE_ERROR               => 'unknown error',
                FASTFRAME_TEMPLATE_PARSE_ERROR         => 'parse error loading template',
                FASTFRAME_TEMPLATE_BLOCK_NOT_FOUND     => 'no such block',
                FASTFRAME_TEMPLATE_INVALID_BLOCK_NAME  => 'block name invalid',
                FASTFRAME_TEMPLATE_DUPLICATE_BLOCK     => 'duplicate block name', 
                FASTFRAME_TEMPLATE_CYCLE_NOT_ALLOWED => 'cannot cycle the global block',
                FASTFRAME_TEMPLATE_FILE_NOT_FOUND      => 'template file not found',
            );
        }

        if (PEAR::isError($value)) {
            $value = $value->getCode();
        }

        return isset($errorMessages[$value]) ? $errorMessages[$value] : $errorMessages[FASTFRAME_TEMPLATE_ERROR];
    }

    // }}}
    // {{{ boolean isError()

    function isError($in_object)
    {
        return is_a($in_object, 'mojave_template_error');
    }

    // }}}
    // {{{ void    setDirectory()

    /**
    * Sets the file root. The file root gets prefixed to all filenames passed to the object.
    *
    * Make sure that you override this function when using the class
    * on windows.  The function will return the old root just like the ini_set
    * functions in php.
    *
    * @param   string
    *
    * @return  string old file root (for reseting)
    * @access  public
    * @see     constructor, _get_file()
    */
    function setDirectory($root)
    {
        if ($root != '/' && substr($root, -1) != '/') {
            $root .= '/';
        }

        $oldRoot = $this->fileRoot;
        $this->fileRoot = $root;
        return $oldRoot;
    }

    // }}}
    // {{{ boolean load()

    /**
     * Sets the template.
     *
     * You can either load a template file from disk or from string
     *
     * @param  string template content or filename
     * @param  string (optional) type of template (string|file)
     *
     * @return boolean success
     * @access public
     * @see    $template
     */
    function load($in_template, $in_type = 'file')
    {
        if ($this->templateLoaded == true) {
            return PEAR::raiseError(null, FASTFRAME_TEMPLATE_ALREADY_LOADED, null, E_USER_NOTICE, null, 'FF_Template_Error', true);
        }

        $rebuild = true;
        if ($in_type == 'file') {
            $cacheReference =& $this->templateCache['file'][$in_template];
            if (isset($cacheReference)) {
                $this->blockList = unserialize($cacheReference);
                $rebuild = false;
            }
            else {
                $contents = $this->_get_file($in_template);
            }
        }
        elseif ($in_type == 'string') {
            $checkSum = md5($in_template);
            $cacheReference =& $this->templateCache['string'][$checkSum];
            if (isset($cacheReference)) {
                $this->blockList = unserialize($cacheReference);
                $rebuild = false;
            }
            else {
                $contents = $in_template;
            }
        }

        $this->currentBlockName = $this->globalBlockName;
        if ($rebuild) {
            $this->template = sprintf($this->blockTemplate, $this->globalBlockName, $contents, $this->globalBlockName);
            $this->_rebuild();
            $cacheReference = serialize($this->blockList);
        }

        if ($this->flagBlockTrouble) {
            return PEAR::raiseError(null, FASTFRAME_TEMPLATE_PARSE_ERROR, null, E_USER_WARNING, $this->blockList, 'FF_Template_Error', true);
        }

        $this->templateLoaded = true;
        return true;
    }

    // }}}
    // {{{ boolean touchBlock()

    /**
     * Gives an empty set of data to the block.
     *
     * @param  mixed name of the block or array of blocks
     *
     * @return boolean success
     * @access public
     */
    function touchBlock($in_blockName = null)
    {
        if (is_array($in_blockName)) {
            $trouble = false;
            foreach($in_blockName as $blockName) {
                if (FF_Template::isError($result = $this->assignBlockData(array(), $blockName))) {
                    $trouble = true;
                    $this->errorStack[] = $result;
                }
            }
        }
        else {
            $trouble = $this->assignBlockData(array(), $in_blockName);
        }

        return $trouble;
    }

    // }}}
    // {{{ boolean assignBlockData()

    /**
     * Sets values of template variables
     *
     * Taking an associative array of variable names and values, this function will
     * assign the variables to the appropriate block, the current one if one is not
     * provided.  Variables are avaiable to all child blocks and will only be cleared
     * if belonging to the current block.
     *
     * @param    array variable names and values
     * @param    boolean success
     * @access   public
     */
    function assignBlockData($in_variableMap, $in_blockName = null, $in_advance = true)
    {
        if (FF_Template::isError($blockName = $this->_validate_block($in_blockName, $this->currentBlockName))) {
            return $blockName;
        }

        if ($blockName != $this->globalBlockName) {
            $blockReference =& $this->blockList[$this->blockList[$blockName]['parent']]['children'][$blockName];
            // if we have had no previous loop requests on this block, then we
            // now need to replace the includes
            if (array_sum($blockReference) == 0) {
                $this->_replace_includes($blockName);
            }

            // increment the loops for this child for this set
            // [!] this is causing a problem if the parent was never cycled
            if ($in_advance) {
                end($blockReference);
                if (!is_int($last = key($blockReference))) {
                    $blockReference[0] = 1;
                }
                else {
                    $blockReference[$last]++;
                }
            }
        }

        // [!] we can't allow overwritting variables from parent blocks [!]
        if ($in_advance) {
            $this->blockList[$blockName]['data'][] = $in_variableMap;
        }
        // if we are merging with the previous data, do it here
        else {
            $dataSets =& $this->blockList[$blockName]['data'];
            end($dataSets);
            $lastDataSet =& $dataSets[key($dataSets)];
            $lastDataSet = array_merge($lastDataSet, $in_variableMap);
        }
        return true;
    }

    // }}}
    // {{{ boolean cycleBlock()

    /**
     * Add a cycle to a block for the iteration of the parent.
     *
     * Blocks are sections of code that are optional and which can be repeated
     * a multitude of times.  This function registers a placeholder for a block.
     * Regardless of whether the block will be set for parsing (touched) or not,
     * this function makes the parser aware that there should be a placeholder
     * held one way or another specifing that this block in fact exists and the
     * status should be checked.  This is important to keep the data sets in order.
     *
     * @param  string $in_blockName name of the block to be cycled
     *
     * @return boolean success
     * @access public
     */
    function cycleBlock($in_blockName = null)
    {
        if (is_array($in_blockName)) {
            $trouble = false;
            foreach($in_blockName as $in_blockNameCurrent) {
                if (FF_Template::isError($result = $this->cycleBlock($in_blockNameCurrent))) {
                    $trouble = true;
                    $this->errorStack[] = $result;
                }
            }
            
            return $trouble;
        }

        if (FF_Template::isError($blockName = $this->_validate_block($in_blockName, $this->currentBlockName))) {
            return $blockName;
        }
        
        // get the reference to this block in the parent
        if ($blockName == $this->globalBlockName) {
            return PEAR::raiseError(null, FASTFRAME_TEMPLATE_CYCLE_NOT_ALLOWED, null, E_USER_NOTICE, null, 'FF_Template_Error', true); 
        }
        else {
            $blockReference =& $this->blockList[$this->blockList[$blockName]['parent']]['children'][$blockName];
        }

        // add an iteration to this blockname, which is stored in the children array in the parent
        $blockReference[] = 0;

        return true;
    }

    // }}}
    // {{{ boolean assignBlockCallback()

    /**
     * Registers a callback function to be used on parsed data.
     *
     * Once a block has been parsed it is possible to run a callback function
     * on the data.  This can be useful if you need to pass the result of a parse
     * to another function for evaluation and then put the result back into its
     * place within the document.
     *
     * @param  string $in_function
     * @param  array  $in_args
     * @param  string (optional) name of the block
     * @param  int (optional) index that the block data will be placed in function call
     *
     * @return boolean success
     * @access public
     */
    function assignBlockCallback($in_function, $in_args = array(), $in_blockName = null, $in_contentsIndex = 0)
    {
        if (FF_Template::isError($blockName = $this->_validate_block($in_blockName, $this->currentBlockName))) {
            return $blockName;
        }
        
        $this->blockList[$blockName]['callback'] = array('function' => $in_function, 'args' => $in_args, 'index' => $in_contentsIndex);
        return true;
    }

    // }}}
    // {{{ void    makeBlockPersistent()

    function makeBlockPersistent($in_blockName, $in_flag = true)
    {
        if (is_array($in_blockName)) {
            foreach($in_blockName as $blockStatus => $blockName) {
                $this->makeBlockPersistent($blockName, $blockStatus);
            }
        }
        else {
            $this->persistentBlocks[$in_blockName] = $in_flag;
            $blockReference =& $this->blockList[$in_blockName];
            // I can't handle namespaces here, you have to mark a block as persistent either by direct name
            // or before parsing of the branch begins
            if (isset($blockReference)) {
                $blockReference['persistent'] = $in_flag;
            }
        }
    }

    // }}}
    // {{{ string  render()

    /**
     * Parses the block if not already parsed and then returns parsed data.
     *
     * If the block has been touched, the function first calls parse() on the block
     * and stores the data to an internal result variable.  Then the function clears
     * the result unless otherwise specified and returns that result.
     *
     * @param  string (optional) name of the block, global block if none specified
     * @param  boolean (optional) clear the parsed result
     *
     * @return string parsed result
     * @access public
     * @see    prender(), parse()
     */
    function render($in_blockName = null, $in_clearResult = true) 
    {
        if (FF_Template::isError($blockName = $this->_validate_block($in_blockName, $this->globalBlockName))) {
            return $blockName;
        }

        // if this is the global block, parse it once
        if ($blockName == $this->globalBlockName) {
            $this->_parse($blockName, 1); 
        }
        // if this block has been touched or assigned block data, parse it
        elseif ($iterations = array_shift($this->blockList[$this->blockList[$blockName]['parent']]['children'][$blockName])) {
            $this->_parse($blockName, $iterations);
        }
        // if this block is persistent (auto-touch) then parse it once (but only if not used)
        elseif ($this->blockList[$blockName]['persistent']) {
            $this->_parse($blockName, 1);
        }

        $result =& $this->blockList[$blockName]['result'];
        if ($in_clearResult) {
            $return = $result;
            $result = '';
            return $return;
        }
        else {
            return $result;
        }
    }

    // }}}
    // {{{ void    prender()

    /**
     * Print a certain block with all replacements done.
     */
    function prender($in_blockName = null, $in_clearResult = true) 
    {
        echo $this->render($in_blockName, $in_clearResult);
    }

    // }}}
    // {{{ boolean parse()

    /**
     * Parse the global block.
     *
     * @access public
     * @see _parse()
     */
    function parse()
    {
        $this->_parse($this->globalBlockName, 1);
        return true;
    }

    // }}}
    // {{{ boolean setBlock()

    /**
     * Set the block as the current block.
     *
     * This function will set the block scope.  This is a convience function so that
     * the functions pertaining to block manipulation can be used without a reference
     * to a block.
     *
     * @param   string name of the block
     *
     * @return  boolean success
     * @access  public
     */
    function setBlock($in_blockName = null)
    {
        if (FF_Template::isError($blockName = $this->_validate_block($in_blockName, $this->globalBlockName))) {
            return $blockName;
        }

        $this->currentBlockName = $blockName;
        return true;
    }

    // }}}
    // {{{ string  parseBlock()

    /**
     * Parses the specified block, defaults to current block.
     *
     * Only differs from the parse() call by calling parse() with the current block as the
     * default and not the global block
     *
     * @return string result of parsed block
     * @access public
     * @see    parse(), setBlock(), $currentBlockName, _parse()
     */
    function parseBlock() 
    {
        if ($iterations = array_shift($this->blockList[$this->blockList[$this->currentBlockName]['parent']]['children'][$this->currentBlockName])) {
            return $this->_parse($this->currentBlockName, $iterations);
        }
        
        return true;
    }

    // }}}
    // {{{ boolean branchBlock()

    function branchBlock($in_replaceVariable, $in_blockName, $in_template, $in_type = 'file', $in_delimiter = null)
    {
        return FF_Template::isError($result = $this->branchBlockNS($in_replaceVariable, $in_blockName, $in_template, $in_type, $in_delimiter, false)) ? $result : true;
    }

    // }}}
    // {{{ string  branchBlockNS()

    /**
     * Adds a block to the template changing a variable placeholder to a block placeholder.
     *
     * @param   string unique blockname
     * @param   string template data
     * @param   string (optional) parent
     * @param   string (optional) type
     *
     * @return string namespace to be added to all sub-block sets
     * @throws  IT_Error
     * @access  public
     */    
    function branchBlockNS($in_replaceVariable, $in_blockName, $in_template, $in_type = 'file', $in_delimiter = null, $in_useNamespace = true)
    {
        // can't cache this because we have to add the prefix, and that is a problem
        if ($in_type == 'file') {
            $template = $this->_get_file($in_template); 
        }
        else {
            $template = $in_template;
        }

        // make sure this new block has a valid name
        if (!preg_match($this->blockValidatePattern, $in_blockName)) {
            return PEAR::raiseError(null, FASTFRAME_TEMPLATE_INVALID_BLOCK_NAME, null, E_USER_WARNING, $in_blockName, 'FF_Template_Error', true);
        } 

        // we add a fixed length namespace so it does not interfere with other branched blocks
        $namespace = $in_useNamespace ? 'branch_' . sprintf('%0' . strlen(mt_getrandmax()) . 'd', mt_rand()) . ':' : '';

        // we are going to be given a variable to insert this block into...we need to
        // find the owner of that variable, and put the block there...then, for every
        // other instance of that block, we need to replace it with blockname[]...then we
        // are done...voila
        foreach(array_keys($this->blockList) as $blockName) {
            $variableReference =& $this->blockList[$blockName]['variables'][$in_replaceVariable];
            if (isset($variableReference) && $variableReference == 'owns') {
                $parentBlockName = $blockName;
                // have to reference the full path because cannot use unset on reference
                unset($this->blockList[$blockName]['variables'][$in_replaceVariable]);
                break;
            }
        }

        $this->ownedVariables = array_intersect($this->blockList[$parentBlockName]['variables'], array('owns'));
        list($contents, $children, $variables) = $this->_build_block_tree($template, $in_blockName, $namespace);
        // I realize this is repetitive code from what the _build_block_tree() does and
        // we could just wrap the template passed in in block tags and let the function parse it
        // but it slows it down and we already know the information, so why make it do the work?
        $this->blockList[$in_blockName] = array(
            'contents'  => $contents,
            'data'      => array(),
            'result'    => '',
            'delimiter' => $in_delimiter,
            'parent'    => $parentBlockName,
            'parsed'    => 0,
            'children'  => $children,
            'variables' => $variables,
            'persistent'=> !empty($this->persistentBlocks[$in_blockName]),
            'callback'  => false,
        );
      
        // we now have the owner block, now let's get this block in here...
        $ownerBlockReference =& $this->blockList[$parentBlockName];
        $ownerBlockReference['contents'] = str_replace($this->variableWrapper[0] . $in_replaceVariable . $this->variableWrapper[1], $this->variableWrapper[0] . $in_blockName . '[]' . $this->variableWrapper[1], $this->blockList[$parentBlockName]['contents']);
        $ownerBlockReference['children'][$in_blockName] = array();
        unset($ownerBlockReference['variables'][$in_replaceVariable]);

        // update the reference to the new blockname that was passed in
        return $namespace;
    }
    
    // }}}
    // {{{ boolean clearResult()

    /**
     * Remove the result data.
     *
     * Effectively what this function does in simulate a parse/render but does
     * not return the data.  It is the easiest way to clear all the results.
     *
     * @param  string (optional) name of block from which to clear the results
     *
     * @return boolean success
     */
    function clearResult($in_blockName = null)
    {
        if (FF_Template::isError($error = $this->render($in_blockName))) {
            return $error;
        }

        return true;
    }
    
    // }}}
    // {{{ boolean blockExists()

    /**
     * Checks wheter a block exists.
     *
     * Does the specified block exist in the template.  Note that blocks are
     * case sensitive at the moment.
     *
     * @param  string name of the block
     *
     * @return boolean exists
     * @access public
     */
    function blockExists($in_blockName) 
    {
        return isset($this->blockList[$in_blockName]);
    }
    
    // }}}
    // {{{ void    reset()

    /**
     * Prepares the class to accept a new template.
     *
     * This file is required if a second template is to be loaded over the first.
     * The function removes the template data structure but keeps relevant caching 
     * information which can prove to be useful when reloading templates again.
     *
     * @access public
     * @return boolean success
     */
    function reset()
    {
        $this->errorStack = array();
        $this->currentBlockName = $this->globalBlockName;
        $this->blockList = array();
        $this->flagBlockTrouble = false;
        $this->templateLoaded = false;
    }

    // }}}
    // {{{ _build_block_tree()

    /**
     * Recusively builds a list of all blocks within the template.
     *
     * @param  string (optional) string that gets scanned
     * @param  string (optional) name of the parent block, used when recursing (could use static or class property for this...hmmmm
     *
     * @return array of child blocks found
     * @access private
     * @see $blockList
     */
    function _build_block_tree($in_string = null, $in_parent = null, $in_namespace = null)
    {
        // use the current template if one is not provided
        $string = is_null($in_string) ? $this->template : $in_string;

        $blockList = array();
        $blockVariables = array();

        // if there are any child blocks, replace them with a reference and store the code
        // in $blockMatches to be used after we look for variables
        if ($hasChildBlocks = preg_match_all($this->blockLocatorPattern, $string, $blockMatches, PREG_SET_ORDER)) {
            // walk the matches and prepare arrays of block containers and placeholders
            foreach ($blockMatches as $blockMatch) {
                list($blockContainer, $blockName, $null, $blockDelimiter, $blockContent) = $blockMatch;
                if (!is_null($in_namespace)) {
                    $blockName = $in_namespace . $blockName;
                }

                $blockContainers[] = $blockContainer;
                $blockNames[] = $this->variableWrapper[0] . $blockName . '[]' . $this->variableWrapper[1];
            }

            // replace the child blocks with the placeholders
            $string = str_replace($blockContainers, $blockNames, $string); 
        }

        // now that there are no sub-blocks, (or there never were because we skipped that if 
        // statement) we can figure out which variables are in the current block 
        if (preg_match_all($this->variableLocatorPattern, $string, $variableMatches)) {
            foreach($variableMatches[1] as $variableMatch) {
                $ownedVariableReference =& $this->ownedVariables[$variableMatch];
                $blockVariables[$variableMatch] = isset($ownedVariableReference) ? 'inherited' : $ownedVariableReference = 'owns';
            }
        }
        else {
            $blockVariables = array();
        }

        if ($hasChildBlocks) {
            // now run through the matches again and build the template tree
            foreach ($blockMatches as $blockMatch) {
                list($blockContainer, $blockName, $null, $blockDelimiter, $blockContent) = $blockMatch;
                $blockIsPersistent = false;
                if (!is_null($in_namespace)) {
                    // if the block without the namespace was made persistent, update the inheritance
                    // we don't record the namespaced block in the persistent block list, only the root names
                    $blockIsPersistent = !empty($this->persistentBlocks[$blockName]);
                    $blockName = $in_namespace . $blockName;
                }

                // for building the child array, we want an empty array since this
                // hash reference will be a list of iterations for this block
                $blockList[$blockName] = array();

                // we have encountered a duplicate block, record error and skip to next iteration
                // maybe we should remove it? not sure
                if (isset($this->blockList[$blockName])) {
                    $this->errorStack[] = PEAR::raiseError(null, FASTFRAME_TEMPLATE_DUPLICATE_BLOCK, null, E_USER_NOTICE, $blockName, 'FF_Template_Error', true);
                    $this->flagBlockTrouble = true;
                    continue;
                }
                
                // this puts are array in order
                if (!is_null($in_parent)) {
                    $parentReference =& $this->blockList[$in_parent];
                    if (!isset($parentReference)) {
                        $parentReference = array();
                    }
                }

                list($contents, $children, $variables) = $this->_build_block_tree($blockContent, $blockName, $in_namespace);
                $this->blockList[$blockName] = array(
                    'contents'  => $contents,
                    'data'      => array(),
                    'result'    => '',
                    'delimiter' => $blockDelimiter,
                    'children'  => $children,
                    'parent'    => $in_parent,
                    'parsed'    => 0,
                    'variables' => $variables,
                    'persistent'=> $blockIsPersistent,
                    'callback'  => false,
                );
            }
        }

        // removed the owned variables of this block as we recurse out
        $this->ownedVariables = array_intersect($this->ownedVariables, array('inherited'));

        return array($string, $blockList, $blockVariables);
    }

    // }}}
    // {{{ _parse()

    /**
     * Parses the given block.
     *
     * @param  string name of the block to be parsed
     * @param  array (optional) variable cache
     * @param  int (optional) level of recursion
     *
     * @return string contents of current parsed block
     * @access private
     */
    function _parse($in_blockName, $in_iterations, $in_variableCache = array(), $in_level = 0)
    {
        static $parsed;

        $outer = '';
        $this->blockList[$in_blockName]['parsed']++;

        for ($iteration = 1; $iteration <= $in_iterations; $iteration++) {
            $contents = $this->blockList[$in_blockName]['contents'];
            // add the variables that this block owns
            $variableCache = array_merge($in_variableCache, $variableData = (array) array_shift($this->blockList[$in_blockName]['data']));

            // replace all the variables contained in this block with the value from the cache
            foreach(array_keys($this->blockList[$in_blockName]['variables']) as $variableName) {
                $cacheReference =& $variableCache[$variableName];
                $variableReplacement = isset($cacheReference) ? $cacheReference : '';
                $contents = str_replace($this->variableWrapper[0] . $variableName . $this->variableWrapper[1], $variableReplacement, $contents);
            }

            // parse all the child references
            foreach(array_keys($this->blockList[$in_blockName]['children']) as $childBlockName) {
                $cache =& $this->blockList[$childBlockName]['result'];
                $childBlockPlaceHolder = $this->variableWrapper[0] . $childBlockName . '[]' . $this->variableWrapper[1];
                $childIterations = array_shift($this->blockList[$in_blockName]['children'][$childBlockName]);
                // if this block was not touched, but it is a persistent block, parse it once
                if (!$childIterations && $this->blockList[$childBlockName]['persistent']) {
                    $childIterations = 1;
                }

                if ($childIterations) {
                    $contents = str_replace($childBlockPlaceHolder, $cache . $this->_parse($childBlockName, $childIterations, $variableCache, $in_level + 1), $contents);
                }
                else {
                    $contents = str_replace($childBlockPlaceHolder, $cache, $contents);
                }
                $cache = '';
            }

            // remove the variables that this block owns by returns the values that are
            // present in the variableCache that are not present in the variableData
            $variableCache = array_diff($variableCache, $variableData);

            // run the callback function on the block
            if (!empty($this->blockList[$in_blockName]['callback'])) {
                extract($this->blockList[$in_blockName]['callback']);
                array_splice($args, $index, $index, $contents);
                $contents = call_user_func_array($function, $args);
            }

            // insert the delimiter if this is not the last iteration
            if ($iteration < $in_iterations) {
                $contents .= $this->blockList[$in_blockName]['delimiter'];
            }
            // if this is the last time, trim extra space from the end that we don't want
            else {
                $contents = rtrim($contents);
            }

            $outer .= $contents;
        }

        // perhaps we could determine if we are on the last iteration tree...might not
        // be possible...this seems simple enough
        if ($in_level == 0) {
            $this->blockList[$in_blockName]['result'] = $outer;
        }

        return $outer;
    }

    // }}}
    // {{{ _rebuild()

    /**
     * Rebuilds the template data structure.
     *
     * This function is the core of the initialization, since it first clears
     * out any previous template data by calling reset() and then calls _build_block_tree()
     * which builds the internal structure.
     *
     * @access private
     * @see    reset(), _build_block_tree()
     */
    function _rebuild() 
    {
        $this->ownedVariables = array();
        $this->_build_block_tree();
        // clear the template since we no longer need it
        $this->template = '';
        $this->ownedVariables = array();
    }

    // }}}
    // {{{ _get_file()

    /**
     * Reads a file from disk and returns its content
     *
     * @param  string name of file
     *
     * @return string contents of file
     */
    function _get_file($in_filename)
    {
        // append file root if not an absolute path
        $filename = strpos($in_filename, '/') === 0 || strpos($in_filename, './') === 0 ? $in_filename : $this->fileRoot . $in_filename;

        if (!($fp = @fopen($filename, 'r'))) {
            return PEAR::raiseError(null, FASTFRAME_TEMPLATE_FILE_NOT_FOUND, null, E_USER_ERROR, $filename, 'FF_Template_Error', true);
        }

        $content = fread($fp, filesize($filename));
        fclose($fp);

        return $content;
    }

    // }}}
    // {{{ _validate_block()

    function _validate_block($in_blockName, $in_default) {
        if (is_null($in_blockName)) {
            return $in_default;
        }
        elseif (!isset($this->blockList[$in_blockName])) {
            return PEAR::raiseError(null, FASTFRAME_TEMPLATE_BLOCK_NOT_FOUND, null, E_USER_WARNING, $in_blockName, 'FF_Template_Error', true);
        }
        else {
            return $in_blockName;
        }
    }

    // }}}
    // {{{ _replace_includes()

    /**
     * Replace include instructions in a block.
     *
     * When a block has been touched, we begin replacing the include instructions
     * and then finding any new blocks introduced and merging those into the tree.
     * We have to do a bit of merge work here however for the current block.
     *
     * @param  string block name
     *
     * @return boolean success
     */
    function _replace_includes($in_blockName)
    {
        $contentsReference =& $this->blockList[$in_blockName]['contents'];
        $contents = preg_replace($this->includeLocatorPattern, '$this->_get_file("\\1")', $contentsReference);
        // if there were include files, then update the block tree now
        if ($contents != $contentsReference) {
            $this->ownedVariables = array_intersect($this->blockList[$in_blockName]['variables'], array('owns'));
            list($contents, $children, $variables) = $this->_build_block_tree($contents, $in_blockName);
            $blockReference =& $this->blockList[$in_blockName];
            $contentsReference = $contents;
            // += operator will add data with new keys
            $blockReference['children'] += $children;
            $blockReference['variables'] += $variables;
        }

        return true;
    }

    // }}}
}
// {{{ class FF_Template_Error

/**
 * Error class for the FF_Template interface, which just prepares some variables and spawns PEAR_Error
 *
 * @version  Revision: 1.0
 * @author   Dan Allen <dan@mojavelinux.com>
 * @access   public
 * @since    PHP 4.2.1
 * @package  FF_Template
 */

// }}}
class FF_Template_Error extends PEAR_Error {
    // {{{ properties 

    /**
     * Message in front of the error message
     * @var string $error_message_prefix
     */
    var $error_message_prefix = 'FF_Template Error: ';
    
    // }}}
    // {{{ constructor

    /**
     * Creates an FF_Template error object, extending the PEAR_Error class
     *
     * @param int   $code the xpath error code
     * @param int   $mode the reaction to the error, either return, die or trigger/callback
     * @param int   $level intensity of the error (PHP error code)
     * @param mixed $debuginfo any information that can inform user as to nature of the error
     *
     * @access private
     */
    function FF_Template_Error($code = FASTFRAME_TEMPLATE_ERROR, $mode = PEAR_ERROR_RETURN, 
                         $level = E_USER_NOTICE, $debuginfo = null) 
    {
        if (is_int($code)) {
            $this->PEAR_Error(FF_Template::errorMessage($code), $code, $mode, $level, $debuginfo);
        } 
        else {
            $this->PEAR_Error("Invalid error code: $code", FASTFRAME_TEMPLATE_ERROR, $mode, $level, $debuginfo);
        }
    }
    
    // }}}
}
?>
