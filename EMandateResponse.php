<?php 

/**
 * 	EMandateResponse
 */
class EMandateResponse extends SimpleXMLElement
{
	/**
	 * Return if the response is a successfull one, in boolean
	 */
	public function Status() : Bool
	{
		if(isset($this->EMandateErrorResponse)) 
		{
			return false;
		}
		return true;
	}
	
	/**
	 * Return the error message, if there is one. Else return null
	 */
	public function Error()
	{
		if(isset($this->EMandateErrorResponse))
		{

			return $this->EMandateErrorResponse->Error;
		}
		return null;
	}

}