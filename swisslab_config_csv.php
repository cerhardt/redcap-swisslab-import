<?php
namespace meDIC\SwisslabImport;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;


// mode: search / create / export / import / delete: POST overwrites GET
$sMode = $_GET['mode'];
if (isset($_POST['mode'])) {
    $sMode = $_POST['mode'];
}
$bShowImportedMsg = false;

if ($sMode == 'import') {

    if (is_array($_FILES['file_upload']) && $_FILES['file_upload']['error'] == UPLOAD_ERR_OK) {
        $aUploadedFile = $_FILES['file_upload'];
      
        if (is_uploaded_file($aUploadedFile['tmp_name'])) {
            $aImport = SwisslabImport::csv_to_array($aUploadedFile['tmp_name'], ';');    

            $settings = array();
            foreach($aImport as $idx => $aRow) {
                if (!isset($aRow['labcode']) || !isset($aRow['redcap_field'])) {
                    continue;
                }
                $settings['field-list'][$idx] = 'true';
                // Pflichtfelder
                $settings['labcode'][$idx] = $aRow['labcode'];
                $settings['redcap_field'][$idx] = $aRow['redcap_field'];

                // optionale Felder bei import_mode = match
                if (!isset($aRow['redcap_lab_date'])) {
                    $aRow['redcap_lab_date'] = '';
                }
                if (!isset($aRow['redcap_visit_date'])) {
                    $aRow['redcap_visit_date'] = '';
                }
                if (!isset($aRow['redcap_event'])) {
                    $aRow['redcap_event'] = '';
                }
                if (!isset($aRow['select'])) {
                    $aRow['select'] = '';
                }
                $settings['redcap_lab_date'][$idx] = $aRow['redcap_lab_date'];
                $settings['redcap_visit_date'][$idx] = $aRow['redcap_visit_date'];
                $settings['redcap_event'][$idx] = $aRow['redcap_event'];
                $settings['select'][$idx] = $aRow['select'];
            }
            foreach($settings as $key => $value) {
                $bShowImportedMsg = true;
                ExternalModules::setProjectSetting($module->PREFIX, $project_id, $key, $value);
            }
        }
    }
}

if ($sMode == 'export') {
    // csv array
    $aCSV = array();

    // header variables
    $aHeader = array();
    $aHeader['labcode'] = true;
    $aHeader['redcap_field'] = true;
    $aHeader['redcap_lab_date'] = true;
    $aHeader['redcap_visit_date'] = true;
    $aHeader['redcap_event'] = true;
    $aHeader['select'] = true;
    
    $settings = $module->getProjectSettings();

    if (is_array($settings['field-list'])) {
        foreach($settings['field-list'] as $idx => $foo) {
            $aCSV[$idx]['labcode'] = $settings['labcode'][$idx];
            $aCSV[$idx]['redcap_field'] = $settings['redcap_field'][$idx];
            $aCSV[$idx]['redcap_lab_date'] = $settings['redcap_lab_date'][$idx];
            $aCSV[$idx]['redcap_visit_date'] = $settings['redcap_visit_date'][$idx];
            $aCSV[$idx]['redcap_event'] = $settings['redcap_event'][$idx];
            $aCSV[$idx]['select'] = $settings['select'][$idx];
        }
    } else {
        $aCSV[0]['labcode'] = '';
        $aCSV[0]['redcap_field'] = '';
        $aCSV[0]['redcap_lab_date'] = '';
        $aCSV[0]['redcap_visit_date'] = '';
        $aCSV[0]['redcap_event'] = '';
        $aCSV[0]['select'] = '';
    }
    
    // output csv file
    $output = fopen("php://output",'w') or die("Can't open php://output");
    fwrite($output, "\xEF\xBB\xBF");
    header("Content-Type:application/csv"); 
    header("Content-Disposition:attachment;filename=swisslab_config_".$project_id."_".date("Ymd_His").".csv"); 
    fputcsv($output, array_keys($aHeader), ";");
    foreach($aCSV as $row) {
      $Vals = array();
      foreach($aHeader as $field => $foo) {
          if (!isset($row[$field])) {
              $Vals[$field] = '';    
          } else {
              $Vals[$field] = $row[$field]."\t";
          }
      } 
      fputcsv($output,$Vals, ";");
    }
    fclose($output) or die("Can't close php://output");
    exit;
}
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

?>
<p>Hier kann die Konfiguration der Felder für den Swisslab Import ex- und importiert werden. Die allgemeine Konfiguration des Moduls muss in der Modulkonfiguration eingestellt werden.</p>
<h5>Swisslab Konfiguration der Felder exportieren</h5>
<form style="max-width:700px;" method="post">
<div class="form-group row">
  <div class="col-sm-offset-2 col-sm-5">
    <button type="submit" class="btn btn-secondary" name="submit">Export</button>
  </div>
</div>
<input type="hidden" name="mode" value="export">
</form>

<?php 
if ($bShowImportedMsg) {
    echo '<h5 style="color:#800000;">Swisslab Konfiguration wurde importiert!</h5>';
} else {
?>
<h5>Swisslab Konfiguration der Felder importieren</h5>
<form style="max-width:700px;" enctype="multipart/form-data" method="post">
<div class="form-group row">
  <div class="col-sm-5">
      <label for="file_upload">CSV-Datei (mit ";" getrennt)</label>
      <input type="file" id="file_upload" name="file_upload"> 
  </div>
</div>
<div class="form-group row">
  <div class="col-sm-offset-2 col-sm-5">
    <button type="submit" class="btn btn-secondary" name="submit">Import</button>
  </div>
</div>
<input type="hidden" name="mode" value="import">
<input type="hidden" name="MAX_FILE_SIZE" value="3000000" />
</form>
<?php 
}
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
?>