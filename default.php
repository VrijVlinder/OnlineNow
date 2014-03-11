<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['OnlineNow'] = array(
   'Name' => 'OnlineNow',
   'Description' => "Lists the users and their avatar who are currently online browsing the forum.",
   'Version' => '1.0',
   'Author' => "Gary Mardell then modified to have user avatar by peregrine",
   'RegisterPermissions' => array('Plugins.OnlineNow.ViewHidden', 'Plugins.OnlineNow.Manage'),
   'SettingsPermission' => array('Plugins.OnlineNow.Manage')
);

/**
 * TODO:
 * Admin option to allow users it hide the module
 * User Meta table to store if they are hidden or not
 */

class OnlineNowPlugin extends Gdn_Plugin {
   
   public function PluginController_OnlineNow_Create($Sender) {
      $Sender->Permission('Plugins.OnlineNow.Manage');
      $Sender->AddSideMenu('plugin/OnlineNow');
      $Sender->Form = new Gdn_Form();
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array('OnlineNow.Location.Show', 'OnlineNow.Frequency', 'OnlineNow.Hide'));
      $Sender->Form->SetModel($ConfigurationModel);
            
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {    
         $Sender->Form->SetData($ConfigurationModel->Data);    
      } else {
         $Data = $Sender->Form->FormValues();
         $ConfigurationModel->Validation->ApplyRule('OnlineNow.Frequency', array('Required', 'Integer'));
         $ConfigurationModel->Validation->ApplyRule('OnlineNow.Location.Show', 'Required');
         if ($Sender->Form->Save() !== FALSE)
            $Sender->StatusMessage = T("Your settings have been saved.");
      }
      
      // creates the page for the plugin options such as display options
      $Sender->Render($this->GetView('onlinenow.php'));
   }

   public function PluginController_ImOnline_Create($Sender) {
      
      $Session = Gdn::Session();
      $UserMetaData = $this->GetUserMeta($Session->UserID, '%'); 
      
      // render new block and replace whole thing opposed to just the data
      include_once(PATH_PLUGINS.DS.'OnlineNow'.DS.'class.onlinenowmodule.php');
      $OnlineNowModule = new OnlineNowModule($Sender);
      $OnlineNowModule->GetData(ArrayValue('Plugin.OnlineNow.Invisible', $UserMetaData));
      echo $OnlineNowModule->ToString();

   }
   
   public function Base_Render_Before($Sender) {
      $Sender->AddCssFile('onlinenow.css', 'plugins/OnlineNow');
      $ConfigItem = C('OnlineNow.Location.Show', 'every');
      $Controller = $Sender->ControllerName;
      $Application = $Sender->ApplicationFolder;
      $Session = Gdn::Session();     

		// Check if its visible to users
		if (C('OnlineNow.Hide', TRUE) && !$Session->IsValid()) {
			return;
		}
		
		$ShowOnController = array();		
		switch($ConfigItem) {
			case 'every':
				$ShowOnController = array(
					'discussioncontroller',
					'categoriescontroller',
					'discussionscontroller',
					'profilecontroller',
					'activitycontroller'
				);
				break;
			case 'discussion':
			default:
				$ShowOnController = array(
					'discussioncontroller',
					'discussionscontroller',
					'categoriescontroller'
				);				
		}
		
      if (!InArrayI($Controller, $ShowOnController)) return; 

	   $UserMetaData = $this->GetUserMeta($Session->UserID, '%');     
	   include_once(PATH_PLUGINS.DS.'OnlineNow'.DS.'class.onlinenowmodule.php');
	   $OnlineNowModule = new OnlineNowModule($Sender);
	   $OnlineNowModule->GetData(ArrayValue('Plugin.OnlineNow.Invisible', $UserMetaData));
	   $Sender->AddModule($OnlineNowModule);

	   $Sender->AddJsFile('/plugins/OnlineNow/onlinenow.js');
	   $Frequency = C('OnlineNow.Frequency', 4);
	   if (!is_numeric($Frequency))
	      $Frequency = 4;
      
	   $Sender->AddDefinition('OnlineNowFrequency', $Frequency);
      
   }

   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Add-ons', 'OnlineNow', 'plugin/OnlineNow', 'Garden.Themes.Manage');
   }
   
   // User Settings
   public function ProfileController_AfterAddSideMenu_Handler($Sender) {
      $SideMenu = $Sender->EventArguments['SideMenu'];
      $Session = Gdn::Session();
      $ViewingUserID = $Session->UserID;
      
      if ($Sender->User->UserID == $ViewingUserID) {
         $SideMenu->AddLink('Options', T('Online Settings'), '/profile/OnlineNow', FALSE, array('class' => 'Popup'));
      }
   }
   
   public function ProfileController_OnlineNow_Create($Sender) {
      
      $Session = Gdn::Session();
      $UserID = $Session->IsValid() ? $Session->UserID : 0;
      
      // Get the data
      $UserMetaData = $this->GetUserMeta($UserID, '%');
      $ConfigArray = array(
            'Plugin.OnlineNow.Invisible' => NULL
         );
      
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         // Convert to using arrays if more options are added.
         $ConfigArray = array_merge($ConfigArray, $UserMetaData);
         $Sender->Form->SetData($ConfigArray);
      }
      else {
         $Values = $Sender->Form->FormValues();
         $FrmValues = array_intersect_key($Values, $ConfigArray);
         
         foreach($FrmValues as $MetaKey => $MetaValue) {
            $this->SetUserMeta($UserID, $this->TrimMetaKey($MetaKey), $MetaValue); 
         }

         $Sender->StatusMessage = T("Your changes have been saved.");
      }

      $Sender->Render($this->GetView('settings.php'));
   }
   

   public function Setup() { 
      $Structure = Gdn::Structure();
      $Structure->Table('OnlineNow')
			->Column('UserID', 'int(11)', FALSE, 'primary')
       	->Column('Timestamp', 'datetime')
         ->Column('Photo','varchar(255)', NULL)
			->Column('Invisible', 'int(1)', 0)
         ->Set(FALSE, FALSE); 
   }
}
