<?php
namespace meDIC\SwisslabImport;

class SwisslabImport extends \ExternalModules\AbstractExternalModule {
	public function __construct() {
		parent::__construct();
		// Other code to run when object is instantiated
        
        // config API
        $this->configAPI = $this->replaceHost($this->getUrl('swisslab_config_api.php', true, true));        

	}
	
    /**
    * http basic authorization for Swisslab API
    *
    * @author  Christian Erhardt
    * @access  public
    * @return void
    */
    public function authorize() {
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            // If no username provided, present the auth challenge.
            header('WWW-Authenticate: Basic realm="Swisslab Import"');
            header('HTTP/1.0 401 Unauthorized');
            // User will be presented with the username/password prompt
            // If they hit cancel, they will see this access denied message.
            exit; // Be safe and ensure no other content is returned.
        }
        
        // user/pw set in config?
        if (strlen($this->getSystemSetting('password')) == 0 || strlen($this->getSystemSetting('user')) == 0) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }
        // If we get here, username was provided. Check password.
        if ($_SERVER['PHP_AUTH_PW'] !== $this->getSystemSetting('password') || $_SERVER['PHP_AUTH_USER'] !== $this->getSystemSetting('user')) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }
        $ip = \System::clientIpAddress();
        if (strlen($this->getSystemSetting('valid_ips')) > 0) {
            $aIPs = explode(",",$this->getSystemSetting('valid_ips'));
            $aIPs[] = $_SERVER['SERVER_ADDR'];
            if (!in_array($ip, $aIPs,true)) {
                header('HTTP/1.1 403 Forbidden');
                exit;
            }
        }
    } 

    /**
    * replace host in URLs if "allowed_domain" differs from redcap_base_url in REDCap settings
    *
    * @author  Christian Erhardt
    * @param string $sUrl
    * @access  private
    * @return string 
    */
    private function replaceHost($sUrl) {
        if (strlen($this->getSystemSetting("allowed_domain")) > 0) {
            if (strpos($sUrl, $this->getSystemSetting("allowed_domain")) === false) {
                $host = parse_url($GLOBALS['redcap_base_url'], PHP_URL_HOST);
                $sUrl = str_replace($host, $this->getSystemSetting("allowed_domain"),$sUrl);
            }
        }
        return $sUrl;    
    }

}