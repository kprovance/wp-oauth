<?php
  $config = array(
        "base_url" => "https://stg.redux.io/wp-content/plugins/wp-oauth/inc/hybridauth/",
        "providers" => array(
                "Twitter" => array(
                        "enabled" => true,
                        "keys" => array("key" => "DpKhTUFbzqtVifldu6fB8bxgT", "secret" => "bYjxnYL5TGvgVCt4HHMqlsi6TvTNekvXSkqhHiJl$"),
                        "includeEmail" => true,
                ),
        ),
        "debug_mode" => false,
        "debug_file" => "",
  );
Hybrid_Provider_Adapter::logout();
           require_once (dirname(__FILE__) . "/../hybridauth/Hybrid/Auth.php");

           try{
               $hybridauth = new Hybrid_Auth( $config );

               $twitter = $hybridauth->authenticate( "Twitter" );

               $user_profile = $twitter->getUserProfile();
		print_r($user_profile);
               echo "Hi there! " . $user_profile->displayName;

               $twitter->setUserStatus( "Hello world!" );

               $user_contacts = $twitter->getUserContacts();
           }
           catch( Exception $e ){
               echo "Ooophs, we got an error: " . $e->getMessage();
           }
exit();
