<?php
Yii::import("ext.xupload.models.XUploadForm");

class ImportController extends GxController {
  /**
   * Full path of the main uploading folder.
   * @var string
   */
  public $path;
  
  /**
   * Subfolder in which files will be stored
   * @var string
   */
  public $subfolder="images";
  
  public function filters() {
    return array(
      'IPBlock',
      'accessControl', 
      );
  }
  
  public function accessRules() {
    return array(
        array('allow',
          'actions'=>array('view'),
          'roles'=>array('*'),
          ),
        array('allow', 
          'actions'=>array('index', 'uploadfromlocal', 'queueprocess', 'uploadzip', 'uploadftp', 'uploadprocess', 'xuploadimage', 'batch', 'delete'),
          'roles'=>array('editor', 'xxx'),
          ),
        array('deny', 
          'users'=>array('*'),
          ),
        );
  }

  public function actionIndex() {
    $this->layout='//layouts/column1';
    
    if (Yii::app()->user->checkAccess('editor')) {
      $tools = array();
      
      $tools["import-local"] = array(
                              "name" => Yii::t('app', "Import images from your computer"),
                              "description" => Yii::t('app', "Some short description"),
                              "url" => $this->createUrl('/admin/import/uploadfromlocal'),
                           );
      
      $tools["import-zip"] = array(
                              "name" => Yii::t('app', "Import images in a ZIP file from your computer"),
                              "description" => Yii::t('app', "Some short description"),
                              "url" => $this->createUrl('/admin/import/uploadzip'),
                           );
      
      $tools["import-ftp"] = array(
                              "name" => Yii::t('app', "Import images that can be found on in the server's '/uploads/ftp' folder"),
                              "description" => Yii::t('app', "Some short description"),
                              "url" => $this->createUrl('/admin/import/uploadftp'),
                           );                           
      
      $tools["process"] = array(
                              "name" => Yii::t('app', "Process uploaded images"),
                              "description" => Yii::t('app', "Some short description"),
                              "url" => $this->createUrl('/admin/import/uploadprocess'),
                           );  
                         
      $this->render('index',
        array (
          'tools' => $tools 
        )
      );  
    } else {
      throw new CHttpException(403, Yii::t('app', 'Access Denied.'));
    }
  }
  
  public function actionImportSettings() {
    $this->layout='//layouts/column1';
    $this->render('processimportedimages', array());
  }
  
  public function actionUploadFromLocal() {
    $this->layout='//layouts/column1';  
    
    $model = new XUploadForm;
    $this->render('uploadfromlocal', array(
      'model' => $model,
    ));
  }
  
  public function actionUploadZip() {
    $this->layout='//layouts/column1';  
    $this->checkUploadFolder();
    
    $model = new ImportZipForm;
    
    if (isset($_POST['ImportZipForm'])) {
      $model->setAttributes($_POST['ImportZipForm']);
      
      if ($model->validate()) {
        $file_image = CUploadedFile::getInstance($model,'zipfile');
      
        if ( (is_object($file_image) && get_class($file_image)==='CUploadedFile')) {
          $pclzip = $this->module->zip;  
          
          $tmp_path = sys_get_temp_dir() . "/MG" . date('YmdHis');
          if (!is_dir($tmp_path)) {
            mkdir($tmp_path);
            chmod($tmp_path, 0777);
          }
          
          if (is_dir($tmp_path)) {
            $list = $pclzip->extractZip($file_image->tempName , $tmp_path);
            if ($list) {
              $cnt_added = 0;
              $cnt_skipped = 0;
              
              $path = $this->path . "/" . $this->subfolder."/";
              if(!is_dir($path)){
                mkdir($path);
                chmod($path, 0777);
              }

              foreach ($list as $file) {
                $file_info = pathinfo($file['stored_filename']);
                
                if (!$file["folder"] && strpos($file['stored_filename'], "__MACOSX") === false) { // we don't want to process folder and MACOSX meta data file mirrors as the mirrored files also return the image/jpg mime type
                  $mime_type = CFileHelper::getMimeType($file['filename']);
                  $file_ok = $this->_checkImage($file['filename']);
                  if ($mime_type == "image/jpeg" && $file_ok) {
                    $cnt_added++;
                    
                    $file['stored_filename'] = $this->checkFileName($path, $file_info["basename"]);
                    rename($file['filename'], $path . $file['stored_filename']);
                    $this->createImage($file['stored_filename'], $file['size'], $_POST['ImportZipForm']["batch_id"], $mime_type);
                  
                  } else {
                    if (!$file_ok)
                      Flash::add('error', Yii::t('app', 'The file {file} is corrupt and could therefore not be imported.', array('{file}' => $file['filename'])), true);
                    $cnt_skipped++;
                  }
                }
              }
              Flash::add("success", Yii::t('app', '{total} files found, {num} images imported, {num_skipped} other files skipped', array("{num}" => $cnt_added, "{total}" => $cnt_added + $cnt_skipped, "{num_skipped}" => $cnt_skipped)));
              $this->redirect("uploadprocess");
            }
          }
          
        } else {
          $model->addError("zipfile", Yii::t('app', 'Please choose a zip file'));
        }  
      }
    }
    
    if (!Yii::app()->getRequest()->getIsPostRequest()) 
      $model->batch_id = "B-" . date('Y-m-d-H:i:s');
    
    if (Yii::app()->getRequest()->getIsPostRequest() && !$model->hasErrors()) {
      $model->addError("zipfile", Yii::t('app', 'Please make sure to keep the file smaller than %dB', array('%d' => ini_get('upload_max_filesize'))));
      $model->batch_id = "B-" . date('Y-m-d-H:i:s');
      $model->addError("batch_id", Yii::t('app', 'Please check your upload batch id'));
    }
    
    $this->render('uploadzip', array(
      'model' => $model,
    ));
  }

  public function actionUploadFtp() {
    Yii::app()->clientScript->registerCoreScript('jquery');
    Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/mg.api.js', CClientScript::POS_END);
    Yii::app()->clientScript->registerCssFile(Yii::app()->baseUrl . '/css/jquery.fancybox-1.3.4.css');
    Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/jquery.fancybox-1.3.4.pack.js', CClientScript::POS_END);
    
    // add page resubmit if script time out is close. 
    $this->layout='//layouts/column1';  
    $this->checkUploadFolder();
    
    $path = $this->path . "/" . $this->subfolder."/";
    if(!is_dir($path)){
      mkdir($path);
      chmod($path, 0777);
    }
    
    $model = new ImportFtpForm;
    $count_files = 0;
    
    $ftp_path = $this->path . "/ftp/";
    if (is_dir($ftp_path)) {
      $list = CFileHelper::findFiles($ftp_path);
      
      foreach ($list as $file) {
        $file_info = pathinfo($file);
        if ($file_info['basename'] != '.gitignore') {
          $count_files++;
        }
      }
      
    } 
    if (!Yii::app()->getRequest()->getIsPostRequest()) 
      $model->batch_id = "B-" . date('Y-m-d-H:i:s');
    
    $this->render('uploadftp', array(
      'model' => $model,
      'count_files' => $count_files,
    ));
  }
  
  private function _processFTPQueue() {
    $data = array();
    $data['status'] = 'ok';
    
    $this->checkUploadFolder();
    
    $path = $this->path . "/" . $this->subfolder."/";
    if(!is_dir($path)){
      mkdir($path);
      chmod($path, 0777);
    }
    
    $model = new ImportFtpForm;
    $count_files = 0;
    
    $ftp_path = $this->path . "/ftp/";
    if (is_dir($ftp_path)) {
      if (isset($_POST['ImportFtpForm'])) {
        $model->setAttributes($_POST['ImportFtpForm']);
        
        if ($model->validate()) {
          $cnt_added = 0;
          $cnt_skipped = 0;
          
          $list = CFileHelper::findFiles($ftp_path);
          foreach ($list as $file) {
            $file_info = pathinfo($file);
            if ($file_info['basename'] != '.gitignore') {
              $count_files++;
            }
          }
          
          if ($count_files > 0) {
            $import_per_request = $model->import_per_request;
            $model->import_skipped = 0;
            
            foreach ($list as $file) {
              if ($import_per_request > 0) {
                $file_info = pathinfo($file);
                $mime_type = CFileHelper::getMimeType($file);
                $file_ok = $this->_checkImage($file);
                
                if ($file_info['basename'] != '.gitignore') {
                  if ($mime_type == "image/jpeg" && $file_ok) {
                    $model->import_processed++;
                    $file_name = $this->checkFileName($path, $file_info["basename"]);
                    rename(str_replace('//', '/', $file), $path . $file_name);
                    $this->createImage($file_name, filesize($path . $file_name), $_POST['ImportFtpForm']["batch_id"], $mime_type);
                    $import_per_request--;
                  } else {
                    if (!$file_ok)
                      Flash::add('error', Yii::t('app', 'The file {file} is corrupt and could therefore not be imported.', array('{file}' => $file)), true);
                    $model->import_skipped++;
                  }
                  $count_files--;
                }
              }
            }
            
            if ($count_files == 0) {
              $this->_finishFTPQueue($model->import_processed, $model->import_skipped);
            } else {
              $data['status'] = 'retry';
              $data['files_left'] = $count_files;
              $data['ImportFtpForm'] = $model;
            }
          } else {
            $this->_finishFTPQueue($model->import_processed, $model->import_skipped);
          }
        } 
      }
    } 
    $this->jsonResponse($data);
  }
  
  private function _finishFTPQueue($added, $skipped) {
    $data['status'] = 'done';
    $data['redirect'] = Yii::app()->createUrl('admin/import/uploadprocess');
    
    Flash::add("success", Yii::t('app', '{total} files found in \'/uploads/ftp\' folder, {num} images imported, {num_skipped} other files skipped', array("{total}" => $added + $skipped, "{num}" => $added , "{num_skipped}" => $skipped)));
    if ($skipped > 0)
      Flash::add("warning", Yii::t('app', 'The {num_skipped} files that are still in the \'/uploads/ftp\' folder cannot be imported and should therfore be manually removed!', array("{total}" => $added + $skipped, "{num}" => $added , "{num_skipped}" => $skipped)), true);
    
    $this->jsonResponse($data);
  }
  
  public function actionQueueProcess($action) {
    switch ($action) {
      case 'ftp':
        $this->_processFTPQueue();
        break; 
    }
  }
  
  public function actionUploadProcess() {
    $this->layout='//layouts/column1';  
    
    $model = new Image('search');
    $model->unsetAttributes();

    if (isset($_GET['Image']))
      $model->setAttributes($_GET['Image']);

    $this->render('uploadprocess', array(
      'model' => $model,
    ));
  }
  
  public function actionDelete($id) {
    if (Yii::app()->getRequest()->getIsPostRequest()) {
      $model = $this->loadModel($id, 'Image');
      if ($model->hasAttribute("locked") && $model->locked) {
        throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
      } else {
        $model->delete();
        MGHelper::log('delete', 'Deleted Image with ID(' . $id . ')');
        
        Flash::add('success', Yii::t('app', "Image deleted"));

        if (!Yii::app()->getRequest()->getIsAjaxRequest())
          $this->redirect(array('uploadprocess'));
      }
    } else
      throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));
  }
  
  public function actionBatch($op) {
    $this->layout='//layouts/column1';
    if (Yii::app()->getRequest()->getIsPostRequest()) {
      switch ($op) {
        case "delete":
          $this->_batchDelete();
          break;
          
        case "process":
          $this->_batchProcess();
          break;
      }
      if (!Yii::app()->getRequest()->getIsAjaxRequest())
        $this->actionUploadProcess();
    } else
      throw new CHttpException(400, Yii::t('app', 'Your request is invalid.'));  
    
  }
  
  private function _batchDelete() {
    if (isset($_POST['image-ids'])) {
      $images = Image::model()->findAllByPk($_POST['image-ids']);
      
      if ($images) {  
        foreach ($images as $image) {
          $image->delete();
        }
      }
      MGHelper::log('batch-delete', 'Batch deleted Images with IDs(' . implode(',', $_POST['image-ids']) . ')');
    } 
  }
  
  private function _batchProcess() {
    $errors = array();
    $processedIDs = array();
    
    if (isset($_POST['image-ids']) || isset($_POST['massProcess'])) {
      if (isset($_POST['image-ids'])) {
        $images = Image::model()->findAllByPk($_POST['image-ids']);
      } else {
        $condition = new CDbCriteria;
        $condition->limit = (int)$_POST['massProcess'];
        $condition->order = 'created DESC';
        $images = Image::model()->findAllByAttributes(array('locked' => 0), $condition);
      }
      
      if ($images) {
        $firstModel = $images[0];
        
        $plugins = PluginsModule::getAccessiblePlugins("import");
        if (count($plugins) > 0) {
          foreach ($plugins as $plugin) {
            if (method_exists($plugin->component, "validate")) {
              $plugin->component->validate($firstModel, $errors);
            }
          }  
        }
        
        if (count($errors) == 0) {
          if (count($plugins) > 0) {
            foreach ($plugins as $plugin) {
              if (method_exists($plugin->component, "process")) {
                $plugin->component->process($images);
              }
            }  
          }
          
          foreach ($images as $image) {
            $image->locked = 1;
            $image->save();
            $processedIDs[] = $image->id;
          }
          MGHelper::log('batch-import-process', 'Batch processed Image with IDs(' . implode(',', $processedIDs) . ')');  
          Flash::add('success', Yii::t('app', 'Processed {count} images with the IDs({ids})', array("{count}" => count($processedIDs), "{ids}" => implode(',', $processedIDs))));
        }
      }
    } else {
      $errors["noImages"] = array(Yii::t('ui','Please check at least one image you would like to process!'));
    }

    if (count($errors) > 0) {
      if (Yii::app()->getRequest()->getIsAjaxRequest()) {
        $this->jsonResponse($errors);
      } else {
        $model = new Image('search');
        $model->unsetAttributes();
        
        if (isset($_GET['Image']))
          $model->setAttributes($_GET['Image']);
        
        $model->addErrors($errors);
    
        $this->render('uploadprocess', array(
          'model' => $model,
        ));
      }
      Yii::app()->end();
    }
  }
  
  public function actionXUploadImage() {
    $info = array();  
      
    $this->checkUploadFolder();
    
    
    $model = new XUploadForm;
    $model->file = CUploadedFile::getInstance($model, 'file');
    
    if (isset($model->file) && isset($_POST["batch_id"]) && trim($_POST["batch_id"]) != "") {
      $model->mime_type = $model->file->getType();
      $model->size = $model->file->getSize();
      
      // Remove path information and dots around the filename, to prevent uploading
      // into different directories or replacing hidden system files.
      // Also remove control characters and spaces (\x00..\x20) around the filename:
      $model->name = trim(basename(stripslashes($model->file->getName())), ".\x00..\x20");
  
      if ($model->validate()) {
        $path = $this->path . "/" . $this->subfolder."/";
        if(!is_dir($path)){
          mkdir($path);
          chmod($path, 0777);
        }
        
        $model->name = $this->checkFileName($path, $model->name);
        
        $model->file->saveAs($path . $model->name);
        
        if ($this->_checkImage($path . $model->name)) {
        
          $this->createImage($model->name, $model->size, $_POST["batch_id"], $model->mime_type);
          
          $info[] = array(
            'tmp_name' => $model->file->getName(),
            'name' => $model->name,
            'size' => $model->size,
            'type' => $model->mime_type,
            'thumbnail_url' => Yii::app()->getBaseUrl() . Yii::app()->fbvStorage->get('settings.app_upload_url') . "/thumbs/". $model->name,  
            'error' => null
          );
        } else {
          $info[] = array(
            'tmp_name' => $model->file->getName(),
            'name' => $model->name,
            'size' => $model->size,
            'type' => $model->mime_type,
            'error' => Yii::t('app', 'I/O erroro. Uploaded image file corrupted.')
          );
        }
      } else {
        $info[] = array(
          'tmp_name' => $model->file->getName(),
          'name' => $model->name,
          'size' => $model->size,
          'type' => $model->mime_type,
          'error' => 'acceptFileTypes'
        );
      }
    } else {
      $error = 4;
      
      if (!isset($_POST["batch_id"]) || trim($_POST["batch_id"]) == "")
        $error = Yii::t('app', 'Please specify a batch id');
      
      $info[] = array(
        'tmp_name' => null,
        'name' => null,
        'size' => null,
        'type' => null,
        'error' => $error
      );
    }  
    $this->jsonResponse($info);
  }
  
  /**
   * The method implements a basic functionality to verify if the uploaded image is an image file 
   * and not corrupted
   * 
   * @param string $path the full path to the image
   * @return boolean true if the file is a valid image file
   */
  private function _checkImage($path) {
    // Disable error reporting, to prevent PHP warnings
    $ER = error_reporting(0);

    // Fetch the image size and mime type
    $image_info = getimagesize($path);

    // Turn on error reporting again
    error_reporting($ER);

    // Make sure that the image is readable and valid
    if ( ! is_array($image_info) OR count($image_info) < 3)
      return false;
    else 
      return true;
  }
  
  private function checkFileName($path, $file_name) {
    $replace="_";
    $pattern="/([[:alnum:]_\.-]*)/";
    $file_name=str_replace(str_split(preg_replace($pattern,$replace,$file_name)),$replace,$file_name);
    
    $path_parts = pathinfo($file_name);
    
    if(file_exists($path.$file_name)) {
      $c = 1;
      $name = $path_parts['filename'] . "_" . $c;
      while(file_exists($path.$name . "." . $path_parts['extension'])) {
        $c++;  
        $name = $path_parts['filename'] . "_" . $c;
      }
      $file_name = $name . "." . $path_parts['extension'];
    }
    
    return $file_name;
  }
  
  private function createImage($file_name, $size, $batch_id, $mime_type) {
    $image = new Image;
    $image->name = $file_name;
    $image->size = $size;
    $image->batch_id = $batch_id;
    $image->mime_type = $mime_type;
    $image->created = date('Y-m-d H:i:s'); 
    $image->modified = date('Y-m-d H:i:s');
    $image->locked = 0; 
    
    $relatedData = array(
      'imageSets' => array(1),
    );
    $image->saveWithRelated($relatedData); 
    
    MGHelper::log('import-uploadfromlocal', 'Created Image with ID(' . $image->id . ')');
    
    $format = Yii::app()->fbvStorage->get("image.formats.thumbnail", 
      array (
        "width" => 70,
        "height" => 50,
        "quality" => FALSE, // set to integer 0 ... 100 to activate quality rendering
        "sharpen" => FALSE, // set to integer 0 ... 100 to activate sharpen
      ));
    
    MGHelper::createScaledImage($file_name, $file_name, 'thumbs', $format["width"], $format["height"], $format["quality"], $format["sharpen"]);        
  }
  
  private function checkUploadFolder() {
    if(!isset($this->path)){
      $this->path = realpath(Yii::app()->getBasePath() . Yii::app()->fbvStorage->get("settings.app_upload_path"));
    }
    
    if(!is_dir($this->path)){
      throw new CHttpException(500, "{$this->path} does not exists.");
    }else if(!is_writable($this->path)){
      throw new CHttpException(500, "{$this->path} is not writable.");
    }
  }

}