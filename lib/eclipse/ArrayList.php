<?php
class ArrayList
{
	var $elementData;

    function ArrayList()
	{
	    $this->elementData = array();
	}

	function &get($index)
	{
		return $this->elementData[$index];
	}

	function &set($index, &$o)
	{
		$item =& $this->elementData[$index];
		$this->elementData[$index] =& $o;
		return $item;
	}

	function size()
	{
		return count($this->elementData);
	}

	function add(&$o)
	{
		$this->elementData[] = &$o;
	}

	function clear()
	{
		$this->elementData = array();
	}

	function &remove($index)
	{
		$item =& $this->elementData[$index];
		unset($this->elementData[$index]);
		$this->elementData = array_values($this->elementData);
		return $item;
	}

	function indexOf(&$o)
	{
		$index = array_search($o, $this->elementData, true);
		if (is_int($index))
		{
			return $index;
		}

		return -1;
	}

	// NOT YET IMPLEMENTED
	function lastIndexOf(&$o)
	{
	}
}
?>
