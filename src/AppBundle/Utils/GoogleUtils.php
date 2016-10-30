<?php
namespace AppBundle\Utils;

use AppBundle\Entity\Client;
use AppBundle\Entity\Record;
use AppBundle\Entity\Website;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use Google_Client;
use Google_Service_Webmasters;
use Google_Service_Webmasters_SearchAnalyticsQueryRequest;

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


    /**
     * @param Google_Client $googleClient
     * @param EntityManager $em
     * @param Client $client
     */
    public static function updateData($googleClient, $em, $client)
    {
        $service = new Google_Service_Webmasters($googleClient);
        
        $addresses = [];
        $dateFormat = 'Y-m-d';
        
        foreach ($service->sites->listSites()->getSiteEntry() as $siteEntry)
            $addresses [] = $siteEntry['siteUrl'];
    
        foreach ($addresses as $address)
        {
            $website = $em->getRepository('AppBundle:Website')->findOneBy([
                'client' => $client, 'address' => $address]);
            
            if ($website == null){
                $website = new Website();
                $website->setClient($client);
                $website->setAddress($address);
                $em->persist($website);
            }

            $startDate = $em->getRepository('AppBundle:Record')->getLastRecordDateAsString($website);

            if ($startDate == null) {
                $startDate = new DateTime();
                $startDate->modify('-3 month');
            }
            
            else
                $startDate = new DateTime($startDate);
            
            $endDate = new DateTime();
            $endDate->modify('-1 day');
            
            $interval = date_diff($startDate, $endDate);
            $daysBetween = $interval->format('%a');
            
            //don't need to update data
            if ($daysBetween == 0)
                continue;
            
            $tmpSDate = clone $startDate;
            $tmpSDate->modify('+1 day');
            $tmpEDate = clone $startDate;
            
            while ($tmpSDate <= $endDate) {
                $tmpEDate->modify('+7 day');
            
                if ($tmpEDate > $endDate)
                    $tmpEDate = $endDate;
                echo "<p>{$tmpSDate->format($dateFormat)} - {$tmpEDate->format($dateFormat)}</p>";
                self::makeRequest($em, $service, 
                    $tmpSDate->format($dateFormat),
                    $tmpEDate->format($dateFormat),
                    $website);
                usleep(200000);
                $tmpSDate->modify('+7 day');
            }
        }
    }

    /**
     * @param EntityManager $em
     * @param $service
     * @param $startDate
     * @param $endDate
     * @param Website $website
     */
    private static function makeRequest($em, $service, $startDate, $endDate, $website){

        $searchRequest = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();

        $searchRequest->setStartDate($startDate);
        $searchRequest->setEndDate($endDate);

        try {
            $searchRequest->setRowLimit(5000);
            $searchRequest->setDimensions(["date", "country", "device", "query", "page"]);
            $data = $service->searchanalytics->query($website->getAddress(), $searchRequest);
            foreach ($data->getRows() as $row){
                $record = new Record();
                $record->setWebsite($website);
                $record->setDateString($row->keys[0]);
                $record->setCountry($row->keys[1]);
                $record->setDevice($row->keys[2]);
                $record->setQuery($row->keys[3]);
                $record->setPage($row->keys[4]);
                $record->setClicks($row->clicks);
                $record->setImpressions($row->impressions);
                $record->setCtr($row->ctr);
                $record->setPosition($row->position);
                $em->persist($record);
            }
            $em->flush();
        }
        catch (Exception $e){
        }
    }

}

