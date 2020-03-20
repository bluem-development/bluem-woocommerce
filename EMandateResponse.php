<?php 

/**
 * 	EMandateResponse
 */
class EMandateResponse extends SimpleXMLElement
{

	public function Status() : Bool
	{
		if(isset($this->EMandateErrorResponse)) 
		{
			return false;
		}
		return true;
	}
	
	public function Error()
	{
		if(isset($this->EMandateErrorResponse))
		{

			return $this->EMandateErrorResponse->Error;
		}
		return null;
	}

}