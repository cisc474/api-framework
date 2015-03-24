<?php 

class ControllerException extends Exception
{
	private $nRc;
	private $sRc;
	public function __construct($nRc, $sRc)
	{
		parent::__construct($sRc);
		$this->nRc = $nRc;
	}
	
	public function errorNo()
	{
		return $this->nRc;
	}
}


?>
