<?php
// Return caseIDs / labcodes for Swisslab Connector

namespace meDIC\SwisslabImport;
use \REDCap as REDCap;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$oSwisslabImport = new SwisslabImport();

// authorize
$oSwisslabImport->authorize();


// $sMode: 'cases' / 'codes'
$sMode = $_GET['mode'];

// fetch project configs
$aConfig = json_decode(http_get($module->configAPI,10,$module->getSystemSetting('user').":".$module->getSystemSetting('password')), true);
//print_r($aConfig);

$aCaseIDsComplete = $aCodesComplete = array();

if (is_array($aConfig)) {
    foreach($aConfig as $iProj => $aProjConfig) {
        
        // fetch case IDs
        if ($sMode == 'cases') {
            if (isset($aProjConfig['case_id']) && strlen($aProjConfig['case_id']) > 0) {
                $aCases = json_decode(REDCap::getData($iProj, 'json', array(), $aProjConfig['case_id']),true);        

                if (is_array($aCases)) {
                    foreach($aCases as $aCaseTmp) {
                        if (strlen($aCaseTmp['case_id']) > 0) {
                            $aCaseIDsComplete[trim($aCaseTmp['case_id'])] = true;
                        }
                    }
                }
            }
        }
        
        // fetch labcodes
        if ($sMode == 'codes') {
            // Labcodes
            foreach($aProjConfig['labcodes'] as $aLab) {
                foreach($aLab as $sKey => $foo) {
                    $aLabCodes = explode("|",$sKey);
                    foreach($aLabCodes as $sLabCode) {
                        $aCodesComplete[trim($sLabCode)] = true;
                    }
                }
            }
        }
    }

}

if ($sMode == 'cases') {
    $aRet = array (
        'caseIDs' => array_keys($aCaseIDsComplete)
    );
}
if ($sMode == 'codes') {
    $aRet = array (
        'labcodes' => array_keys($aCodesComplete)
    );
}

header("Content-Type: application/json");
echo json_encode($aRet,JSON_UNESCAPED_SLASHES); //format the array into json data
?>                                    