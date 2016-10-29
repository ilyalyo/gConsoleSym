<?php
namespace AppBundle\Utils;

class GoogleUtils{
    
    public static function getOAuthCredentialsFile()
    {
        // oauth2 creds
        $oauth_creds = __DIR__ . '/../../../oauth-credentials.json';
    
        if (file_exists($oauth_creds)) {
            return $oauth_creds;
        }
    
        return false;
    }
}
