<?php

class PluginsModule extends CWebModule
{
	public function init()
	{
		// this method is called when the module is being created
		// you may place code here to customize the module or the application

		// import the module-level models and components
		$this->setImport(array(
			'plugins.models.*',
			'plugins.components.*',
			'plugins.modules.dictionary.components.*',
			'plugins.modules.dictionary.models.*',
			'plugins.modules.import.components.*',
			'plugins.modules.import.models.*',
			'plugins.modules.export.components.*',
			'plugins.modules.export.models.*',
			'plugins.modules.weighting.components.*',
			'plugins.modules.weighting.models.*',
		));
    
    // loop through all active plugins and generate the list
    $this->setModules(array(
      'dictionary',
      'import',
      'export',
      'weighting',
    ));
	}

	public function beforeControllerAction($controller, $action)
	{
		if(parent::beforeControllerAction($controller, $action))
		{
			// this method is called before any module controller action is performed
			// you may place customized code here
			return true;
		}
		else
			return false;
	}
  
  public static function pluginAdminLink($uid, $label="") {
    try {
      $component = Yii::createComponent(self::getPluginClassName($uid));
      if ($component->hasAdmin) {
        if ($component->adminPath != "") {
          $url = $component->adminPath;
        } else {
          $info = explode("-", $uid);
          $type = $info[0];
          $class = $info[1];
          $controller = str_replace("Plugin", "", $class);
          $url = array("/plugins/$type/$controller");  
        }
        return CHtml::link(Yii::t('app','Manage') . ' '  . (($label != "")? $label : $controller . " Plugin"), $url);          
      }
    } catch (Exception $e) {}
    return "";
  }
  
  public static function getPluginClassName($uid) {
    $info = explode("-", $uid);
    return $info[1];
  }
  
  /**
   * This method returns all active plugins of a plugin category. This method does not regard the 
   * plugins $accessRole settings
   * 
   * @param string $type the plugin type that should be retrieved
   * @return array all active plugins of that category
   */
  public static function getActivePlugins($type) {
    static $plugin_list;
    
    $list = array();
    
    if (!isset($plugin_list)) {
      $plugin_list = array();
      $plugins = Plugin::model()->findAll('active=1');
      
      foreach ($plugins as $plugin) {
        try {
        
          $info = explode("-", $plugin->unique_id);
          $plugin_type = $info[0];
          $plugin_class = $info[1];
          
          Yii::import("plugins.modules.$plugin_type.components.*");
          Yii::import("plugins.modules.$plugin_type.models.*");
          
          $component_name = str_replace("Plugin", "", $plugin_class);
          $component = Yii::createComponent($plugin_class);
          
          $plugin_list[$plugin_type][] = (object) array(
            'id' => $plugin->id, 
            'type' => $plugin_type, 
            'name' => $component_name, 
            'link' => self::pluginAdminLink($plugin->unique_id),
            'class' => $plugin_class,
            'component' => $component
          );
        } catch (Exception $e) {}
      }
    }
    if (array_key_exists($type, $plugin_list)) {
      $list = $plugin_list[$type];
    }
    return $list;
  }
  
  
  /**
   * This method returns all active plugins that have been activated for the current game of a plugin category. 
   * This method does not regard the plugins $accessRole settings
   * 
   * @param int $gid the game_id in the database
   * @param string $type the plugin type that should be retrieved
   * @return array all active plugins of that category
   */
  public static function getActiveGamePlugins($gid, $type) {
    static $game_plugin_list;
    
    $list = array();
    
    if (!isset($game_plugin_list)) {
      $game_plugin_list = array();
      
      $tags = Yii::app()->db->createCommand()
                  ->select('p.id, p.unique_id')
                  ->from('{{plugin}} p')
                  ->join('{{game_to_plugin}} gp', 'gp.plugin_id=p.id')
                  ->where(array('and', 'gp.game_id = :gameID', 'p.type=:type'), array(":gameID" => $gid, ":type" => $type))
                  ->queryAll();
      
      $plugins = Plugin::model()->findAll('active=1');
      
      foreach ($plugins as $plugin) {
        try {
        
          $info = explode("-", $plugin["unique_id"]);
          $plugin_type = $info[0];
          $plugin_class = $info[1];
          
          Yii::import("plugins.modules.$plugin_type.components.*");
          Yii::import("plugins.modules.$plugin_type.models.*");
          
          $component_name = str_replace("Plugin", "", $plugin_class);
          $component = Yii::createComponent($plugin_class);
          
          $game_plugin_list[$plugin_type][] = (object) array(
            'id' => $plugin["id"], 
            'type' => $plugin_type, 
            'name' => $component_name, 
            'link' => self::pluginAdminLink($plugin["unique_id"]),
            'class' => $plugin_class,
            'component' => $component
          );
        } catch (Exception $e) {}
      }
    }
    if (array_key_exists($type, $game_plugin_list)) {
      $list = $game_plugin_list[$type];
    }
    return $list;
  }
  
  /**
   * This method lists all active plug-ins the current user has got access to.
   */
  public static function getAccessiblePlugins($type=null, $active=1) {
    $plugins = Plugin::model()->findAll('active=:a', array(':a'=>$active));
    $list = array();
    foreach ($plugins as $plugin) {
      try {
      
        $info = explode("-", $plugin->unique_id);
        $plugin_type = $info[0];
        $plugin_class = $info[1];
        
        Yii::import("plugins.modules.$plugin_type.components.*");
        Yii::import("plugins.modules.$plugin_type.models.*");
        
        $component_name = str_replace("Plugin", "", $plugin_class);
        $component = Yii::createComponent($plugin_class);
        
        if (Yii::app()->user->checkAccess($component->accessRole)) {
          if (is_null($type) || $type == $plugin_type) {
            $list[] = (object) array(
              'id' => $plugin->id, 
              'type' => $plugin_type, 
              'name' => $component_name, 
              'link' => self::pluginAdminLink($plugin->unique_id),
              'class' => $plugin_class,
              'component' => $component
            );
          }
        }
      } catch (Exception $e) {}
    }
    
    return $list;
  }
}
