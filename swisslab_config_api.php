<?php
// Return project configs for Swisslab Import module

namespace meDIC\SwisslabImport;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

$oSwisslabImport = new SwisslabImport();

// authorize
$oSwisslabImport->authorize();

$sql = "SELECT external_module_id FROM `redcap_external_modules` WHERE `directory_prefix` = ?";
$result = $module->query($sql, [$module->PREFIX]);
$row = $result->fetch_assoc();
$iModuleID = $row['external_module_id'];

if ($iModuleID) {
    $sql = "SELECT * FROM `redcap_external_module_settings` WHERE `external_module_id` = ?";
    $result = $module->query($sql, [$iModuleID]);
    
    $aConfigComplete = array();
    $aEnabled = array();
    while($row = $result->fetch_assoc()) {
        if (strlen($row['project_id']) == 0) continue;
        
        // enabled projects
        if (!isset($aEnabled[$row['project_id']]) && $module->isModuleEnabled($module->PREFIX, $row['project_id']) == true) {
            $aEnabled[$row['project_id']] = true;
        }
        // json decode
        if ($row['type'] == 'json-array') {
          $json = json_decode($row['value'], true);
          if ($json !== false) {
              $row['value'] = $json;
          }
        }
        $aConfigComplete[$row['project_id']][$row['key']] = $row['value'];
    }
    
    $aConfig = array();
    foreach($aEnabled as $iProjectID => $foo) {
        $aFields = $aConfigComplete[$iProjectID]['field-list'];
        if (is_array($aFields)) {
            $aLabCodes = $aConfigComplete[$iProjectID]['labcode'];
            foreach($aFields as $i => $bField) {
                if (!$bField) continue;
                foreach($aConfigComplete[$iProjectID] as $key => $val) {
                    if ($key == 'field-list') continue;
                    if (is_array($val)) {
                        $aConfig[$iProjectID]['labcodes'][$aLabCodes[$i]][$key] = $val[$i];
                    } else {
                        $aConfig[$iProjectID][$key] = $val;
                    }
                }
            }
        }
    }
    
    header("Content-Type: application/json");
    echo json_encode($aConfig,JSON_UNESCAPED_SLASHES);//format the array into json data
}
?>                                    