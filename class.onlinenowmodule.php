<?php if (!defined('APPLICATION')) exit();
/**
* Renders a list of users who are taking part in a particular discussion.
*/
class OnlineNowModule extends Gdn_Module {

	protected $_OnlineUsers;

	public function __construct($Sender = '') {
		parent::__construct($Sender);
	}

	public function GetData($Invisible = FALSE) {
		$SQL = Gdn::SQL();
		// $this->_OnlineUsers = $SQL
		// insert or update entry into table
		$Session = Gdn::Session();
        $Photo = $Session->User->Photo;
		$Invisible = ($Invisible ? 1 : 0);

		if ($Session->UserID)
			$SQL->Replace('OnlineNow', array(
				'UserID' => $Session->UserID,
				'Timestamp' => Gdn_Format::ToDateTime(),
				'Photo'=> $Session->User->Photo,
				'Invisible' => $Invisible),
				array('UserID' => $Session->UserID)
			);     

		$Frequency = C('OnlineNow.Frequency', 4);
		$History = time() - $Frequency;

		$SQL
			->Select('u.UserID, u.Name, u.Photo, w.Timestamp, w.Invisible')
			->From('OnlineNow w')
			->Join('User u', 'w.UserID = u.UserID')
			->Where('w.Timestamp >=', date('Y-m-d H:i:s', $History))
			->OrderBy('u.Name');

		if (!$Session->CheckPermission('Plugins.OnlineNow.ViewHidden'))
			$SQL->Where('w.Invisible', 0);

		$this->_OnlineUsers = $SQL->Get();
	}
    
function GetPhoto($User, $Options = array()) {
   $Session = Gdn::Session();
 
       $Photo = $Session->User->Photo;
       if (!$Photo && function_exists('UserPhotoDefaultUrl'))
          $Photo = UserPhotoDefaultUrl($User);
 
       if ($Photo) {
          if (!preg_match('`^https?://`i', $Photo)) {
             $PhotoUrl = Gdn_Upload::Url(ChangeBasename($Photo, 'n%s'));
          } else {
             $PhotoUrl = $Photo;
          }
          $Href = Url(UserUrl($User));
          return '<a title="'.htmlspecialchars($User->Name).'" href="'.$Href.'"'.$LinkClass.'>'
             .Img($Session->User->PhotoUrl, array('alt' => htmlspecialchars($User->Name),'class' => 'ProfilePhotoSmall'))
             .'</a>';
       } else {
         return '';
       }
    }
 

       	public function AssetTarget() {
		//return 'Foot';
		return 'Panel';
	}

	public function ToString() {
		$String = '';
		$Session = Gdn::Session();
		ob_start();
		?>
			<div id="OnlineNow" class="Box">
				<h4><?php echo T("Online Now"); ?> (<?php echo $this->_OnlineUsers->NumRows(); ?>)</h4>
				<ul class="PanelInfo">
				<?php
				if ($this->_OnlineUsers->NumRows() > 0) { 
					foreach($this->_OnlineUsers->Result() as $User) {
				?>
					<li>
		 				<strong <?php echo ($User->Invisible == 1 ? 'class="Invisible"' : '')?>>
		    				<?php
                          if(!$User->Photo) {
                           echo UserAnchor($User); 
                           } else {
                           echo UserPhoto($User, array( 'ImageClass' => 'onimage'));
                           }
                         ?>   
		 				</strong>
		 				<?php echo Gdn_Format::Date($User->Timestamp); ?>
					</li>
				<?php
					}
				}
				?>
			</ul>
		</div>
		<?php
		$String = ob_get_contents();
		@ob_end_clean();
		return $String;
	}
}
