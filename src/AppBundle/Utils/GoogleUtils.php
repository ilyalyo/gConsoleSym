<?php
namespace AppBundle\Utils;

use Google_Client;

class GoogleUtils{

    /**
     * @return bool|string
     */
    public static function getOAuthCredentialsFile()
    {
        // oauth2 creds
        $oauth_creds = __DIR__ . '/../../../oauth-credentials.json';
    
        if (file_exists($oauth_creds)) {
            return $oauth_creds;
        }
    
        return false;
    }

    /**
     * @param $redirect_uri
     * @return Google_Client
     */
    public static function getGoogleClient($redirect_uri){
        if (!$oauth_credentials = GoogleUtils::getOAuthCredentialsFile()){
            echo "missing oauth file";
            die();
        }

        $client = new Google_Client();

        $client->setAuthConfig($oauth_credentials);
        $client->setRedirectUri($redirect_uri);
        $client->addScope("https://www.googleapis.com/auth/webmasters");
        $client->addScope("https://www.googleapis.com/auth/userinfo.email");
        
        return $client;
    }

}
