<?php

/* 
Plugin Name: SMS Envoi 
Plugin URI: http://www.smsenvoi.com/api-sms/envoi-sms-wordpress/
Description: Ajoute des fonctionnalités d'envoi de SMS à votre site Wordpress. Envoyez des SMS à vos utilisateurs . Permet également de recevoir un SMS lorsqu'un commentaire est posté ou un article est publié
Version: 1.1.0 
Author: <a href="http://www.smsenvoi.com/">SMSENVOI.com</a> 
Author URI: http://www.smsenvoi.com 
*/  

require_once dirname( __FILE__ ) . '/php/api.php';



if (!class_exists("smsenvoi")) {  

	class smsenvoi
	{  
		
		public $apilasterrormessage=null;
		/** 
		* Constructor 
		*/ 
		function __construct()  
		{  
			
			
			
			
			register_activation_hook( __FILE__, array($this,'activate') );
			register_deactivation_hook( __FILE__, array($this,'deactivate') );

			add_action('admin_menu', array($this,'add_options_page'));
			add_action('admin_menu', array($this,'add_menu_page'));

			
			
			add_action('comment_post',array($this,'notify_comment_posted'));
			add_action('publish_post',array($this,'notify_save_post'));
			
			add_action('admin_init', array($this,'admin_init') );            
			
			add_action('edit_user_profile', array($this,'user_profile_sms_fields') );
			add_action('profile_personal_options', array($this,'user_profile_sms_fields') );
			add_action('register_form', array($this,'user_profile_register_sms_fields'));
			add_filter( 'user_row_actions', array($this, 'add_row_actions'), 10, 2 );
			add_action('admin_footer-users.php', array($this,'add_bulk_action'));
			add_action('load-users.php', array($this,'custom_bulk_action'));



			
			add_action( 'personal_options_update', array($this,'save_user_profile_sms_fields') );
			add_action( 'edit_user_profile_update',  array($this,'save_user_profile_sms_fields') );
			add_action( 'edit_user_profile_update',  array($this,'save_user_profile_sms_fields') );





			$options=get_option('smsenvoi_options');
			
			if(isset($options['email'])){
				define('SMSENVOI_EMAIL',$options['email']);
				define('SMSENVOI_APIKEY',$options['apikey']);
			}
		}  
		
		
		/**
		* Adds pages in the backoffice menu
		*
		**/
		function add_menu_page(){
			
			
			add_menu_page("SMS Envoi", "SMS Envoi", 'read', 'smsenvoi_envoimembres', array($this,'display_page_envoimembres'), null, 58.210391290 ); 
			add_submenu_page('smsenvoi_envoimembres',"Envoi aux membres", "Envoi aux utilisateurs", 'read', 'smsenvoi_envoimembres',array($this,'display_page_envoimembres'));
			
			add_submenu_page('smsenvoi_envoimembres',"Envoi manuel", "Envoi manuel", 'read', 'smsenvoi_envoimanuel',array($this,'display_page_envoimanuel'));
			add_submenu_page('smsenvoi_envoimembres',"Crédits restants", "Crédits restants", 'read', 'smsenvoi_creditsrestants',array($this,'display_page_creditsrestants'));
			add_submenu_page('smsenvoi_envoimembres',"Historique", "Historique", 'manage_options', 'smsenvoi_historique',array($this,'display_page_historique'));
			add_submenu_page('smsenvoi_envoimembres',"Acheter des crédits", "Acheter des crédits", 'manage_options', 'smsenvoi_achetercredits',array($this,'display_page_achetercredits'));
			
			
			
		}
		
		
		/**
		* Sets new option fields related to the plugin
		**/
		function admin_init(){
			
			register_setting( 'smsenvoi_options', 'smsenvoi_options', array($this,'plugin_settings_validate') );
			add_settings_section('plugin_section_compte', 'Compte SMS ENVOI', array($this,'plugin_section_compte'), 'smsenvoi_options');
			add_settings_section('plugin_section_wordpress', 'Réglages SMS Wordpress', array($this,'plugin_section_wordpress'), 'smsenvoi_options');
			
			add_settings_field('smsenvoi_email', 'Adresse E-mail associée à votre compte SMS ENVOI', array($this,'plugin_setting_email'), 'smsenvoi_options', 'plugin_section_compte');
			add_settings_field('smsenvoi_apikey', 'API KEY de votre compte SMS ENVOI', array($this,'plugin_setting_apikey'), 'smsenvoi_options', 'plugin_section_compte');
			add_settings_field('smsenvoi_subtype', 'Gamme à utiliser pour les envois ', array($this,'plugin_setting_subtype'), 'smsenvoi_options', 'plugin_section_compte');
			add_settings_field('smsenvoi_senderlabel', 'Nom d\'expéditeur à afficher ', array($this,'plugin_setting_senderlabel'), 'smsenvoi_options', 'plugin_section_compte');
			add_settings_field('smsenvoi_role', 'Niveau de compte nécessaire pour envoyer un SMS ', array($this,'plugin_setting_role'), 'smsenvoi_options', 'plugin_section_wordpress');
			add_settings_field('smsenvoi_adminphonenumber', 'Numéro de téléphone du responsable de ce site ', array($this,'plugin_setting_adminphonenumber'), 'smsenvoi_options', 'plugin_section_wordpress');
			add_settings_field('smsenvoi_notifynewcomment', 'Recevoir un SMS lorsqu\'un nouveau commentaire est posté ', array($this,'plugin_setting_notifynewcomment'), 'smsenvoi_options', 'plugin_section_wordpress');
			add_settings_field('smsenvoi_notifysavepost', 'Recevoir un SMS lorsqu\'un POST est créé/modifié ', array($this,'plugin_setting_notifysavepost'), 'smsenvoi_options', 'plugin_section_wordpress');

			
			
			
			
			
			$options = get_option('smsenvoi_options');
			$min_role = $options ? $options['role'] : 'administrator' ;
			$roles = array('Administrator'=>'administrator', 'Editor'=>'editor', 'Author'=>'author', 'Contributor'=>'contributor');

			foreach($roles as $role=>$val)
			{
				$role = get_role($val);
				$role->add_cap( 'send_smsenvoi' );

				if($val == $min_role)
				break;
			}
			
			
			
			
			
			
		}
		function add_bulk_action($actions){ 	?>
			
			<script type="text/javascript">
			jQuery(document).ready(function(){
				
				jQuery('<option>').val('smsenvoi_envoimembres').text('<?php _e('Envoyer un SMS')?>').appendTo("select[name='action']");
				jQuery('<option>').val('smsenvoi_envoimembres').text('<?php _e('Envoyer un SMS')?>').appendTo("select[name='action2']")
			});
			</script>
			<?php
			
		}
		
		
		
		
		function custom_bulk_action() {
			// ...

			$wp_list_table = _get_list_table('WP_Users_List_Table');
			$action = $wp_list_table->current_action();
			if($action=='smsenvoi_envoimembres'){

				$users['users']=$_GET["users"];
				wp_redirect('admin.php?page=smsenvoi_envoimembres&'.http_build_query($users));
			}


		}
		function add_row_actions($actions,$user_object){ 
			
			
			
			$actions['smsenvoi_envoimembres']="<a href='" . admin_url("admin.php?page=smsenvoi_envoimembres&users[0]=".$user_object->ID)."'>Envoyer un SMS</a>";
			
			return $actions;
			
		}
		function save_user_profile_sms_fields($user_id){
			
			if(isset($_POST["smsenvoi_phonenumber"])){
				
				update_usermeta( $user_id, 'smsenvoi_phonenumber', $_POST['smsenvoi_phonenumber'] );
				
			}
			
			
			if(isset($_POST["smsenvoi_acceptesms"])){
				
				update_usermeta( $user_id, 'smsenvoi_acceptesms', $_POST['smsenvoi_acceptesms'] );
				
			}
		}
		
		function user_profile_register_sms_fields($user) {
			if(isset($user->ID)){
				$smsenvoi_phonenumber = get_user_meta($user->ID,'smsenvoi_phonenumber',true); // $user contains WP_User Class 
			}else{$smsenvoi_phonenumber='';}
			// do something with it.
			// if you echo anything, remember to check if it is set. eg:
			?>
			<h3>Envoi de SMS</h3>

			<label>Numéro de téléphone :</label><br><input type="text" value="<?php  echo $smsenvoi_phonenumber;  ?>" name="smsenvoi_phonenumber"/>
			<br>
			<?php
			
			
			if(isset($user->ID)){
				$acceptesms = get_user_meta($user->ID, 'smsenvoi_acceptesms',true); // $user contains WP_User Class
			}
			// do something with it.
			// if you echo anything, remember to check if it is set. eg:
			

			
			?>
			
			<label>J'accepte de recevoir des SMS </label></label><br><input type="radio" value="1" name="smsenvoi_acceptesms" <?php if(isset($acceptesms)&&($acceptesms=='1')){ echo "checked='yes'"; } ?>
	id="accepte_sms_oui"> <label for="accepte_sms_oui">Oui</label>

<input type="radio" value="0" name="smsenvoi_acceptesms" <?php if(!isset($acceptesms)||($acceptesms!='1')){ echo "checked='yes'"; } ?>
	id="accepte_sms_non"> <label for="accepte_sms_non">Non</label>
<br><br>
<?php
}

		function user_profile_sms_fields($user) {
if(isset($user->ID)){
	$smsenvoi_phonenumber = get_user_meta($user->ID,'smsenvoi_phonenumber',true); // $user contains WP_User Class 
}else{$smsenvoi_phonenumber='';}
	// do something with it.
	// if you echo anything, remember to check if it is set. eg:
	?>
	<h3>Envoi de SMS</h3>

<table class="form-table">
	<tr><th><label>Numéro de téléphone :</label></th><td><input type="text" value="<?php  echo $smsenvoi_phonenumber;  ?>" name="smsenvoi_phonenumber"/></td></tr>
	<?php
	
	
	if(isset($user->ID)){
	$acceptesms = get_user_meta($user->ID, 'smsenvoi_acceptesms',true); // $user contains WP_User Class
	}
	// do something with it.
	// if you echo anything, remember to check if it is set. eg:
	

	
	?>
	
<tr><th><label>J'accepte de recevoir des SMS </label></th><td><input type="radio" value="1" name="smsenvoi_acceptesms" <?php if(isset($acceptesms)&&($acceptesms=='1')){ echo "checked='yes'"; } ?>
			id="accepte_sms_oui"> <label for="accepte_sms_oui">Oui</label>

			<input type="radio" value="0" name="smsenvoi_acceptesms" <?php if(!isset($acceptesms)||($acceptesms!='1')){ echo "checked='yes'"; } ?>
			id="accepte_sms_non"> <label for="accepte_sms_non">Non</label>
			</td></tr></table>
			<?php
		}


		function plugin_settings_validate($settings){
			

			$apismsenvoi=new apismsenvoi($settings['email'],$settings['apikey']);
			$apismsenvoi->checkCredits();

			if($apismsenvoi->result->success!=1|| $settings['email']==''){
				
				add_settings_error( 'smsenvoi_options','smsenvoi_options_wrongapikey','Veuillez vérifier votre adresse e-mail et votre clef API KEY ');
				
				
			}else{
				add_settings_error( 'smsenvoi_options','smsenvoi_options_success','* Vos paramètres ont été mis à jour<br>Félicitations, votre compte est reconnu par SMS ENVOI ','updated');
				
				
			}
			return $settings;
			
			
		}
		
		function plugin_section_compte() {
			echo '<p>Paramètres de votre compte SMS ENVOI</p>';
		} 
		
		function plugin_section_wordpress() {
			echo '<p>Paramétrez les fonctionnalités de votre plugin SMS ENVOI pour ce site</p>';
		} 		
		function plugin_setting_email() { 
			$options = get_option('smsenvoi_options');

			echo "<input id='smsenvoi_options_email' name='smsenvoi_options[email]' size='40' type='text' value='".$options['email']."' />";
		}
		function plugin_setting_apikey() {
			$options = get_option('smsenvoi_options');
			echo "<input id='smsenvoi_options_apikey' name='smsenvoi_options[apikey]' size='40' type='text' value='".$options['apikey']."' />  (vous trouverez votre APIKEY dans la page \"Modifier mon compte\" du site <a href=\"http://www.smsenvoi.com\" target=\"_blank\">SMS ENVOI</a>)";
		}


		function plugin_setting_subtype() {
			$options = get_option('smsenvoi_options');

			echo "<select id='smsenvoi_options_subtype' name='smsenvoi_options[subtype]' >

<option value='LOWCOST' "; if($options['subtype']=='LOWCOST'){ echo " selected='yes' "; } echo ">Low cost</option>
<option value='STANDARD' "; if($options['subtype']=='STANDARD'){ echo " selected='yes' "; } echo ">Standard</option>
<option value='PREMIUM' "; if($options['subtype']=='PREMIUM'){ echo " selected='yes' "; } echo ">Premium</option>
<option value='STOP' "; if($options['subtype']=='STOP'){ echo " selected='yes' "; } echo ">Stop</option>

</select>";

		}

		
		
		
		function plugin_setting_role() {
			$options = get_option('smsenvoi_options');

			echo "<select id='smsenvoi_options_role' name='smsenvoi_options[role]' >

<option value='administrator' "; if($options['role']=='administrator'){ echo " selected='yes' "; } echo ">Administrateur</option>
<option value='author' "; if($options['role']=='author'){ echo " selected='yes' "; } echo ">Auteur</option>
<option value='contributor' "; if($options['role']=='contributor'){ echo " selected='yes' "; } echo ">Contributeur</option>
<option value='subscriber' "; if($options['role']=='subscriber'){ echo " selected='yes' "; } echo ">Abonné</option>

</select>";

		}

		
		
		
		function plugin_setting_senderlabel() {
			$options = get_option('smsenvoi_options');
			echo "<input id='smsenvoi_options_senderlabel' name='smsenvoi_options[senderlabel]' size='40' type='text' value='".$options['senderlabel']."' /> (11 caractères alphanumériques max. , uniquement en Premium)";
		}

		
		
		function plugin_setting_adminphonenumber() {
			$options = get_option('smsenvoi_options');
			echo "<input id='smsenvoi_options_adminphonenumber' name='smsenvoi_options[adminphonenumber]' size='40' type='text' value='".$options['adminphonenumber']."' />";
		}

		
		
		function plugin_setting_notifynewcomment() {
			$options = get_option('smsenvoi_options');
			echo "<select id='smsenvoi_options_notifynewcomment' name='smsenvoi_options[notifynewcomment]' >
<option	value='0'>Non</option> 

			<option	value='1' ";  if($options['notifynewcomment']=='1'){echo "selected='yes' "; } echo ">Oui</option> 

</select>";
		}




		function plugin_setting_notifysavepost() {
			$options = get_option('smsenvoi_options');
			echo "<select id='smsenvoi_options_notifysavepost' name='smsenvoi_options[notifysavepost]' >
<option	value='0'>Non</option> 

			<option	value='1' ";  if($options['notifysavepost']=='1'){echo "selected='yes' "; } echo ">Oui</option> 

</select>";
		}


		
		function add_options_page() {
			add_options_page('SMS Envoi', 'SMS Envoi', 'manage_options', 'smsenvoi_options', array($this,'display_options_page'));
		}
		
		function notify_comment_posted(){
			$options=get_option('smsenvoi_options');
			
			if(($options['adminphonenumber']!='')&&($options['notifynewcomment']=='1')){
				
				$this->api_sendsms($options['adminphonenumber'],'Un nouveau commentaire vient d\'être posté sur '.get_bloginfo('name') );
			}
			
		}
		
		
		
		function notify_save_post(){
			$options=get_option('smsenvoi_options');
			
			if(($options['adminphonenumber']!='')&&($options['notifysavepost']=='1')){
				
				$this->api_sendsms($options['adminphonenumber'],'Un POST vient d\'être créé ou modifié sur '.get_bloginfo('name') );
			}
			
		}
		
		/**
		*	Send SMS through SMS ENVOI API
		**/
		function api_sendsms($recipient,$content){
			
			$options=get_option('smsenvoi_options');
			$apismsenvoi=new apismsenvoi($options["email"],$options["apikey"]);
			
			$apismsenvoi->sendSMS($recipient,$content,$options["subtype"],$options["senderlabel"]);
			
			$this->apilasterrormessage="";
			
			if($apismsenvoi->success){ return true; }else{

				if(preg_match("/YOU DONT HAVE ENOUGH CREDITS/",$apismsenvoi->result->message)){ $this->apilasterrormessage="Nombre de crédits SMS ENVOI insuffisant. <a href='http://www.smsenvoi.com' target='_blank'>Achetez des crédits</a>"; }
				if(preg_match("/RECIPIENTS NOT DEFINED/",$apismsenvoi->result->message)){ $this->apilasterrormessage="Aucun destinataire valide trouvé"; }


				if($this->apilasterrormessage==''){$this->apilasterrormessage=$apismsenvoi->result->message; }
				return false;}
		}
		
		
		
		/**
		*	Send SMS to users
		**/
		function display_page_envoimembres(){
			$recipientlist=array();
			if(!isset($_GET["users"])){
				
				$users=get_users(array('meta_key'=>'smsenvoi_acceptesms','meta_value'=>'1'));
				
				foreach($users as $user){
					$user_id=$user->ID;
					
					$user_info=get_userdata($user_id);
					
					
					$user_smsnumber=get_user_meta($user_id, "smsenvoi_phonenumber", true);
					$user_acceptesms=get_user_meta($user_id, "smsenvoi_acceptesms", true);
					
					if(($user_smsnumber!='')&&($user_acceptesms=='1')){ $recipientlist[]=$user_smsnumber; }
					
					
				}

				echo "<h2>Envoi d'un SMS à tous les utilisateurs acceptant les SMS :</h2><br><br>";
			}else{
				if(sizeof($_GET["users"])==1){
					
					
					$user_info=get_userdata($_GET["users"][0]);
					
					
					$user_smsnumber=get_user_meta($_GET["users"][0], "smsenvoi_phonenumber", true);
					$user_acceptesms=get_user_meta($_GET["users"][0], "smsenvoi_acceptesms", true);
					
					if(($user_smsnumber!='')&&($user_acceptesms=='1')){ $recipientlist[]=$user_smsnumber; }
					
					echo "<h2>Envoi d'un SMS à ".$user_info->user_login." :</h2><br><br>";
					
					
				}else{
					
					$nb_users=sizeof($_GET["users"]);
					echo "<h2>Envoi d'un SMS aux ".$nb_users." utilisateurs selectionnés :</h2><br><br>";
					
					
					foreach($_GET["users"] as $k=>$v){
						$user_info=get_userdata($_GET["users"][$k]);
						
						
						$user_smsnumber=get_user_meta($_GET["users"][$k], "smsenvoi_phonenumber", true);
						$user_acceptesms=get_user_meta($_GET["users"][$k], "smsenvoi_acceptesms", true);
						
						if(($user_smsnumber!='')&&($user_acceptesms=='1')){ $recipientlist[]=$user_smsnumber; }
					}
					
					
					
					
					
					
					
					
					
					
				}
				
				
			}
			
			if(current_user_can('send_smsenvoi')){
				
				if(sizeof($recipientlist)==0){
					
					
					?>
					<h2>Désolé, aucun destinataire acceptant les SMS n'a été trouvé</h2>
			<?php }else{
			
			
	
		if(isset($_POST["smsenvoi_content"])){
		
		
		if($this->api_sendsms($_POST["smsenvoi_recipient"],$_POST["smsenvoi_content"])){
		
		echo "<b>SMS ENVOYE AVEC SUCCES</b>";
		}else{ echo "<b>ECHEC LORS DE L'ENVOI :</b><br>".$this->apilasterrormessage;
		
		}}else{
		
		
		
			?>Nombre total de destinataires : <?php echo sizeof($recipientlist); ?><br>
					<form method="post">
		<input type="hidden" name="smsenvoi_recipient" value="<?php echo implode(",",$recipientlist); ?>">
		
		<p>
		<label>Texte du message : (160 caractères maximum par SMS) </label><br>
		
		<textarea name="smsenvoi_content" cols="80" rows="3"></textarea>
		
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Envoyer le SMS') ?>" />
			</p>

		</form>
		
		<?php }
			}
			}else{ echo "Désolé, vous n'avez pas accès à cette fonctionnalité"; }
			}
			function display_page_historique(){
			
				$options=get_option('smsenvoi_options');
				$key=md5($options["apikey"].date("Y").$_SERVER["REMOTE_ADDR"]);
		
			echo "<iframe style='width:100%;height:100%;min-height:530px;' src='http://www.smsenvoi.com/membres/apisiteidentification/?redir=http://www.smsenvoi.com/historique/messages/&email=".$options["email"]."&key=".$key."&origin=wordpress'></iframe>";
					
				}
				
				/**
				*	Link to buy credits page
				**/
				function display_page_achetercredits(){
					
					
					$options=get_option('smsenvoi_options');
					$key=md5($options["apikey"].date("Y").$_SERVER["REMOTE_ADDR"]);
					
					echo "<h2>Commander des crédits SMS</h2><br><br><a href='http://www.smsenvoi.com/membres/apisiteidentification/?redir=http://www.smsenvoi.com/forfaits/commander/sms/1/&email=".$options["email"]."&key=".$key."&exitapimode=1&origin=wordpress' target='_blank'>Cliquez-ici</a> pour accéder à notre formulaire de commande de crédits SMS";
					
				}
				
				
				/**
				* Shows number of SMS ENVOI Credits Left
				**/
				function display_page_creditsrestants(){
					
					$options=get_option('smsenvoi_options');
					$apismsenvoi=new apismsenvoi($options["email"],$options["apikey"]);

					$apismsenvoi->checkCredits();
					
					
					$tmpcreditsrestants=get_object_vars($apismsenvoi->result->creditsremaining->sms);
					
					switch($options['subtype']){
						
					case 'LOWCOST' : $creditsrestants= $tmpcreditsrestants["1"];   
						break;
					case 'STANDARD' : $creditsrestants= $tmpcreditsrestants["2"];
						break;
					case 'PREMIUM' : $creditsrestants= $tmpcreditsrestants["3"];
						break;
					case 'STOP' : $creditsrestants= $tmpcreditsrestants["7"];
						break;
						
						
						
						
						
						
					}
					
					
					echo "<h2>Crédits SMS ENVOI restants :</h2><br><br>";
					
					echo "<p><b>Il vous reste ".$creditsrestants." crédits ".$options['subtype']."</b></p>";
					echo "<br><br>Vous pouvez acheter de nouveaux crédits à tout moment en vous connectant à <a href='http://www.smsenvoi.com/forfaits/commander/sms/1/'>SMS ENVOI</a>";
					
				}
				
				/**
				*	Manual phone number list SMS
				**/
				function display_page_envoimanuel(){
					
					if(current_user_can('send_smsenvoi')){
						?><h2>Envoyer un SMS à une liste manuelle de numéros</h2>
						<br><br>
						
						<?php
						if(isset($_POST["smsenvoi_content"])){
							
							
							if($this->api_sendsms($_POST["smsenvoi_recipient"],$_POST["smsenvoi_content"])){
								
								echo "<b>SMS ENVOYE AVEC SUCCES</b>";
							}else{ echo "<b>ECHEC LORS DE L'ENVOI :</b><br>".$this->apilasterrormessage;
								
							}}
						?>
						<form method="post">
						<p>
						<label>Numéro de téléphone ou liste de numéros de téléphone séparés par des virgules :</label><br>
						<textarea name="smsenvoi_recipient" cols="80"></textarea>
						</p>
						<p>
						<label>Texte du message : (160 caractères maximum par SMS) </label><br>
						
						<textarea name="smsenvoi_content" cols="80" rows="3"></textarea>
						
						<p class="submit">
						<input type="submit" class="button-primary" value="<?php _e('Envoyer le SMS') ?>" />
						</p>

						</form>
						
						<?php
					}else{ echo "Désolé, vous n'avez pas accès à cette fonctionnalité"; }
				}
				
				
				/**
				* Configuration page
				**/
				function display_options_page(){
					
					
					?><div>
					<h2>Configuration SMS ENVOI</h2>
					Configuration du plugin SMS ENVOI
					<form action="options.php" method="post">
					<?php settings_fields('smsenvoi_options'); ?>
					<?php do_settings_sections('smsenvoi_options'); ?>
					
					<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
					</p>

					</form></div>
					<?php
					
					
					
					
					
					
				}
				
				function activate(){
				
					
					
					
				}
				
				function deactivate(){
					
				}
			}  
		}  
		if (class_exists("smsenvoi"))  
		{  
			$wpsmsenvoi = new smsenvoi();  
		}  



		?>
