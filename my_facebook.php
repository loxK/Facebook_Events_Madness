<?php

require dirname(__FILE__) . '/facebook.php';
class My_Facebook_Exception extends  Exception { }

/**
 * Helper class to easly create facebook events for user profile, pages or app
 */
class My_Facebook {

    private $ApiKey;
	private $Secret;
	private $AppId;
	private $PageId;
	
	private $token;
	private $app_token;
	private $page_tokens = array();
	
	public $Facebook;
	
	public static $CURL_OPTS = array( CURLOPT_CONNECTTIMEOUT => 10,
                                         CURLOPT_RETURNTRANSFER => true,
                                         CURLOPT_TIMEOUT        => 60,
                                         CURLOPT_USERAGENT      => 'facebook-php-2.0');

    public function __construct($ApiKey, $Secret, $AppId, $PageId=null) {
    
        $this->ApiKey = $ApiKey;
        $this->Secret = $Secret;
        $this->AppId  = $AppId;
        $this->PageId = $PageId;
        
        /* init Facebook SDK */
        $this->Facebook = new Facebook(array(   'appId'  => $this->AppId,
                                                  'secret' => $this->Secret,
                                                  'cookie' => true,
                                               )
                                       );
    }
    
    /**
     * Gets proper access tokens for connected user, page or app
     * 
     * @param string|bool $app false for user token, true for configured app token, a page_id for page token
     */
    function getToken($app=false) {
		
    	/* returns cached values if any */
		if($this->token && !$app) return $this->token;
		if($this->app_token && $app===true) return $this->app_token;
		if($app && !empty($this->page_tokens[$app]) ) return $this->page_tokens[$app];
		
		
		// connected user token
		if(!$app){
		
            $this->token = $this->Facebook->getAccessToken();            
            	
			if(!$this->token) throw new My_Facebook_Exception("Can't get user access token");
			return $this->token;
		}
		
		// request a facebook app token
		else if($app===true){
		
			$data=array();
			$data['client_id'] = $this->AppId;
			$data['client_secret'] = $this->Secret;
			$data['grant_type'] = 'client_credentials';
						
			/* we need to use our own function because the PHP sdk only deals with json responses */
			$r = $this->graph('oauth/access_token', null, $data);
			
			if(!is_string($r) || !strpos($r, '=')) return false;

			$token = explode('=', $r);
			return $token[1];
		}
		
		// page token
		else if($app) {
			
			// using curl we get a weird '(#100) The parameter name is required'
			$r = file_get_contents('https://graph.facebook.com/me/accounts?access_token=' . $this->getToken() );
			if(!$r || !$accounts=json_decode($r)) throw new My_Facebook_Exception('Something went wrong getting pages tokens');
			
			/* keeps all pages token */
			foreach ($accounts->data as $account) {
				$page_token[$account->id] = $account->access_token;
			}
			
			if( !$page_token[$app] ) throw new My_Facebook_Exception('No access token found for specified page');
				
			return $page_token[$app];
			
		}
		
	}
	
	/**
	 * Add an event for the configured fanpage
	 * @param array $Data the event's data
	 */
	public function addPageEvent($Data) {
	
	    if(!$this->PageId) throw new My_Facebook_Exception("Cannot add event for the fanpage: Page ID is not set");
		return $this->addEvent($this->PageId, $Data, $this->getToken($this->PageId));
	}
	
	/**
	 * Add an event for the configured app
	 * @param array $Data the event's data
	 */
	public function addAppEvent($Data) {

		return $this->addEvent($this->AppId, $Data, $this->getToken(true));
	}

	/**
	 * Add an event for the connected user
	 * @param array $Data the event's data
	 */
	public function addProfileEvent($Data) {

		return $this->addEvent('me', $Data, $this->getToken());
			
	}
	
	/**
	 * Add an event generic
	 * @param string $id user, app or fanpage id
	 * @param array $Data the event's data
	 * @param string $Token the access token
	 * @param string $File the event image (not implemented)
	 */
	private function addEvent($id, $Data, $Token, $File=null) {

		$r = $this->api($id . '/events', $Token, $Data);
		
		if( $r && !empty($r['id']) ) return $r['id'];
		else var_dump($r);
	
	}
	
	/**
	 * Wrapper to the PHP sdk api
	 * @param string Graph api url
	 * @param string $token access_token
	 * @param array $data the data to send to the api
	 */
	function api($url, $token='', $data=array()){

		$url = "/" . $url;

		if($token) $data['access_token'] = $token;    
		$result = $this->Facebook->api($url, 'POST', $data);

		return $result;
	}
	
	/**
	 * Custom graph api function
	 * @param string Graph api url
	 * @param string $token access_token
	 * @param array $data the data to send to the api
	 */
	function graph($url, $token='', $data=array()){

		$url = "https://graph.facebook.com/" . $url;

		if($token) $data['access_token'] = $token;

		// set the target url
		$ch = curl_init();
		
		$opts = self::$CURL_OPTS;
		$opts[CURLOPT_POSTFIELDS] = $data;
    	$opts[CURLOPT_URL] = $url;		
    	
		// disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
	    // for 2 seconds if the server does not support this header.
	    if (isset($opts[CURLOPT_HTTPHEADER])) {
	      $existing_headers = $opts[CURLOPT_HTTPHEADER];
	      $existing_headers[] = 'Expect:';
	      $opts[CURLOPT_HTTPHEADER] = $existing_headers;
	    } else {
	      $opts[CURLOPT_HTTPHEADER] = array('Expect:');
	    }
    	
    	curl_setopt_array($ch, $opts);
    	$result = curl_exec($ch);
	    if ($result === false) {
	    	  $e = new Exception( curl_error($ch) );
		      curl_close($ch);
		      throw $e;
	    }					
	    curl_close($ch);

		return $result;
	}
}


