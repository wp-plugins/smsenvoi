<?php

define('SMSENVOI_VERSION','3.0.1 | MOD WORDPRESS 1.0');



function iscurlinstalled() {
	if  (in_array  ('curl', get_loaded_extensions())) {
		return true;
	}
	else{
		return false;
	}
}

if(!iscurlinstalled()){ die("L'API SMSENVOI NECESSITE L'INSTALLATION DE CURL"); }

?>