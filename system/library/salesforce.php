<?php
//SALESFORCE CLASS HAS DEPENDENCISES LOCATED IN THE CLIENT FOLDER
    require_once ('./system/clients/salesforce/soapclient/SforcePartnerClient.php');
    require_once ('./system/clients/salesforce/soapclient/SforceHeaderOptions.php');

//--------

class salesforce{

    public $config;
    public $castleford;
    public $brafton;
    
    public function __construct($config){

        $this->config = $config;
        $this->start_brafton();
        $this->start_castleford();

    }
    //---
	//BRAFTON SESSION START
	//---
    public function start_brafton(){
		//DEFINE CREDS
		define("USERNAME", $this->config['saleforce']['brafton']['username']);
		define("PASSWORD", $this->config['saleforce']['brafton']['password']);
		define("SECURITY_TOKEN", $this->config['saleforce']['brafton']['security_token']);

		$wsdl = $this->config['path_to_wsdl'];
		ini_set("soap.wsdl_cache_enabled","0");
		//INITIATE CLIENT SCRIPT
		$sforce = new SforcePartnerClient();
		$soapClient = $sforce->createConnection($wsdl);

		$sforce->login(USERNAME, PASSWORD.SECURITY_TOKEN);
		// // Store SOAP client attributes for later use
		// $_SESSION['sforceLocation'] = $sforce->getLocation();
		// $_SESSION['sforceSessionId'] = $sforce->getSessionId();
		//RETURN INSTANCE
        $this->brafton = $sforce;

	}
    
	//---
	//CASTLEFORD SESSION START
	//---
	public function start_castleford(){ 
		//DEFINE CREDS
		define("USERNAME1", $this->config['saleforce']['castleford']['username']);
		define("PASSWORD1", $this->config['saleforce']['castleford']['password']);
		define("SECURITY_TOKEN1", $this->config['saleforce']['castleford']['security_token']);

		$wsdl = $this->config['path_to_wsdl'];
		ini_set("soap.wsdl_cache_enabled","0");
		//INITIATE CLIENT SCRIPT
		$sforce = new SforcePartnerClient();
		$soapClient = $sforce->createConnection($wsdl);
		//CHECK FOR SESSIONS
		$sforce->login(USERNAME1, PASSWORD1.SECURITY_TOKEN1);
	
		// Store SOAP client attributes for later use
		// $_SESSION['sforceLocation'] = $sforce->getLocation();
		// $_SESSION['sforceSessionId'] = $sforce->getSessionId();
		//RETURN INSTANCE
		$this->castleford = $sforce;
	}
}	