<?php

namespace Xajax\Request;

/*
	File: Request.php

	Contains the Request class

	Title: Request class

	Please see <copyright.php> for a detailed description, copyright
	and license information.
*/

/*
	@package Xajax
	@version $Id: Request.php 362 2007-05-29 15:32:24Z calltoconstruct $
	@copyright Copyright (c) 2005-2007 by Jared White & J. Max Wilson
	@copyright Copyright (c) 2008-2010 by Joseph Woolley, Steffen Konerow, Jared White  & J. Max Wilson
	@license http://www.xajaxproject.org/bsd_license.txt BSD License
*/

/*
	Constant: XAJAX_FORM_VALUES
		Specifies that the parameter will consist of an array of form values.
*/
if(!defined ('XAJAX_FORM_VALUES')) define ('XAJAX_FORM_VALUES', 'get form values');
/*		
	Constant: XAJAX_INPUT_VALUE
		Specifies that the parameter will contain the value of an input control.
*/
if(!defined ('XAJAX_INPUT_VALUE')) define ('XAJAX_INPUT_VALUE', 'get input value');
/*		
	Constant: XAJAX_CHECKED_VALUE
		Specifies that the parameter will consist of a boolean value of a checkbox.
*/
if(!defined ('XAJAX_CHECKED_VALUE')) define ('XAJAX_CHECKED_VALUE', 'get checked value');
/*		
	Constant: XAJAX_ELEMENT_INNERHTML
		Specifies that the parameter value will be the innerHTML value of the element.
*/
if(!defined ('XAJAX_ELEMENT_INNERHTML')) define ('XAJAX_ELEMENT_INNERHTML', 'get element innerHTML');
/*		
	Constant: XAJAX_QUOTED_VALUE
		Specifies that the parameter will be a quoted value (string).
*/
if(!defined ('XAJAX_QUOTED_VALUE')) define ('XAJAX_QUOTED_VALUE', 'quoted value');
/*		
	Constant: XAJAX_NUMERIC_VALUE
		Specifies that the parameter will be a numeric, non-quoted value.
*/
if(!defined ('XAJAX_NUMERIC_VALUE')) define ('XAJAX_NUMERIC_VALUE', 'numeric value');
/*		
	Constant: XAJAX_JS_VALUE
		Specifies that the parameter will be a non-quoted value (evaluated by the 
		browsers javascript engine at run time.
*/
if(!defined ('XAJAX_JS_VALUE')) define ('XAJAX_JS_VALUE', 'unquoted value');
/*
 Constant: XAJAX_PAGE_NUMBER
 Specifies that the parameter will be an integer used to generate pagination links.
 */
if(!defined ('XAJAX_PAGE_NUMBER')) define ('XAJAX_PAGE_NUMBER', 'page number');

/*
	Class: Request
	
	Used to store and generate the client script necessary to invoke
	a xajax request from the browser to the server script.
	
	This object is typically generated by the <xajax->register> method
	and can be used to quickly generate the javascript that is used
	to initiate a xajax request to the registered function, object, event
	or other xajax call.
*/
class Request
{
	use \Xajax\Utils\ContainerTrait;

	/*
		String: sName
		
		The name of the function.
	*/
	private $sName;
	
	/*
		String: sType
		
		The type of the request. Can be one of callable or event.
	*/
	private $sType;
	
	/*
		String: sQuoteCharacter
		
		A string containing either a single or a double quote character
		that will be used during the generation of the javascript for
		this function.  This can be set prior to calling <Request->getScript>
	*/
	private $sQuoteCharacter;
	
	/*
		Array: aParameters
	
		An array of parameters that will be used to populate the argument list
		for this function when the javascript is output in <Request->getScript>	
	*/
	private $aParameters;
	
	/*
		Integer: nPageNumberIndex
	
		The index of the XAJAX_PAGE_NUMBER parameter in the array.
	*/
	private $nPageNumberIndex;
	
	/*
		Function: Request
		
		Construct and initialize this request.
		
		sName - (string):  The name of this request.
	*/
	public function __construct($sName, $sType)
	{
		$this->aParameters = array();
		$this->nPageNumberIndex = -1;
		$this->sQuoteCharacter = '"';
		$this->sName = $sName;
		$this->sType = $sType;
	}
	
	/*
		Function: useSingleQuote
		
		Call this to instruct the request to use single quotes when generating
		the javascript.
	*/
	public function useSingleQuote()
	{
		$this->sQuoteCharacter = "'";
	}
	
	/*
		Function: useDoubleQuote
		
		Call this to instruct the request to use double quotes while generating
		the javascript.
	*/
	public function useDoubleQuote()
	{
		$this->sQuoteCharacter = '"';
	}
	
	/*
		Function: clearParameters
		
		Clears the parameter list associated with this request.
	*/
	public function clearParameters()
	{
		$this->aParameters = array();
	}
	
	/*
		Function: hasPageNumber
		
		Returns true if the request has a parameter of type XAJAX_PAGE_NUMBER.
	*/
	public function hasPageNumber()
	{
		return ($this->nPageNumberIndex >= 0);
	}
	
	/*
		Function: setPageNumber
		
		Set the current value of the XAJAX_PAGE_NUMBER parameter.
	*/
	public function setPageNumber($nPageNumber)
	{
		// Set the value of the XAJAX_PAGE_NUMBER parameter
		$nPageNumber = intval($nPageNumber);
		if($this->nPageNumberIndex >= 0 && $nPageNumber > 0)
		{
			$this->aParameters[$this->nPageNumberIndex] = $nPageNumber;
		}
		return $this;
	}
	
	/*
		Function: addParameter
		
		Adds a parameter value to the parameter list for this request.
		
		sType - (string): The type of the value to be used.
		sValue - (string: The value to be used.
		
		See Also:
		See <Request->setParameter> for details.
	*/
	public function addParameter($sType, $sValue)
	{
		$this->setParameter(count($this->aParameters), $sType, $sValue);
	}
	
	/*
		Function: setParameter
		
		Sets a specific parameter value.
		
		Parameters:
		
		nParameter - (number): The index of the parameter to set
		sType - (string): The type of value
		sValue - (string): The value as it relates to the specified type
		
		Note:
		
		Types should be one of the following <XAJAX_FORM_VALUES>, <XAJAX_QUOTED_VALUE>, <XAJAX_NUMERIC_VALUE>,
		<XAJAX_JS_VALUE>, <XAJAX_INPUT_VALUE>, <XAJAX_CHECKED_VALUE>, <XAJAX_PAGE_NUMBER>.  
		The value should be as follows:
			<XAJAX_FORM_VALUES> - Use the ID of the form you want to process.
			<XAJAX_QUOTED_VALUE> - The string data to be passed.
			<XAJAX_JS_VALUE> - A string containing valid javascript (either a javascript
				variable name that will be in scope at the time of the call or a 
				javascript function call whose return value will become the parameter.
				
	*/
	public function setParameter($nParameter, $sType, $sValue)
	{
		switch($sType)
		{
		case XAJAX_FORM_VALUES:
			$sFormID = $sValue;
			$this->aParameters[$nParameter] = "xajax.getFormValues(" . $this->sQuoteCharacter 
				. $sFormID . $this->sQuoteCharacter . ")";
			break;
		case XAJAX_INPUT_VALUE:
			$sInputID = $sValue;
			$this->aParameters[$nParameter] =  "xajax.$("  . $this->sQuoteCharacter 
				. $sInputID . $this->sQuoteCharacter  . ").value";
			break;
		case XAJAX_CHECKED_VALUE:
			$sCheckedID = $sValue;
			$this->aParameters[$nParameter] =  "xajax.$("  . $this->sQuoteCharacter 
				. $sCheckedID  . $this->sQuoteCharacter . ").checked";
			break;
		case XAJAX_ELEMENT_INNERHTML:
			$sElementID = $sValue;
			$this->aParameters[$nParameter] = "xajax.$(" . $this->sQuoteCharacter 
				. $sElementID . $this->sQuoteCharacter . ").innerHTML";
			break;
		case XAJAX_QUOTED_VALUE:
			$this->aParameters[$nParameter] = $this->sQuoteCharacter . addslashes($sValue) . $this->sQuoteCharacter;
			break;
		case XAJAX_PAGE_NUMBER:
			$this->nPageNumberIndex = $nParameter;
			$this->aParameters[$nParameter] = $sValue;
			break;
		case XAJAX_NUMERIC_VALUE:
		case XAJAX_JS_VALUE:
			$this->aParameters[$nParameter] = $sValue;
			break;
		}
	}

	/*
		Function: getScript
		
		Parameters:

		Returns a string representation of the script output (javascript) from this request object.
	*/
	public function getScript()
	{
		$sXajaxPrefix = $this->getOption('core.prefix.' . $this->sType, '');
		return $sXajaxPrefix . $this->sName . '(' . implode(', ', $this->aParameters) . ')';
	}

	/*
		Function: getScript
		
		Parameters:

		Prints a string representation of the script output (javascript) from this request object.
	*/
	public function printScript()
	{
		print $this->getScript();
	}

	/*
		Function: __toString
		
		Parameters:

		Convert this request object to string.
	*/
	public function __toString()
	{
		return $this->getScript();
	}
}
