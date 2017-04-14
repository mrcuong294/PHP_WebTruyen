<?php

/**
 * Created by PhpStorm.
 * User: Mr Cuong
 * Date: 4/13/2017
 * Time: 8:46 PM
 */
class DriveUtils {

    const APPLICATION_NAME = 'Book Libs Drive Api';
    const CREDENTIALS_PATH = '~/.credentials/drive-php-quickstart.json';
    const CLIENT_SECRET_PATH = __DIR__ . '/client_secret.json';
    const FOLDER_ID = '0B-47U73VinI7MVFHRmQwWUpUeW8';
    const SUB_FOLDER_ID = '0B-47U73VinI7eUQ3OWVpYk4zUm8';

    // If modifying these scopes, delete your previously saved credentials
    // at ~/.credentials/drive-php-quickstart.json
    private $client;

    private $service;

    private $folderId;

    function __construct() {
        $this->client = $this->getClient();
        $this->service = new Google_Service_Drive($this->client);
    }

    /**
     * Update file chapter .txt to google drive;
     * @param $fileName
     * @param $fileContent
     * @return mixed
     */
    public function uploadChapterContent($fileName, $fileContent) {
        if ($this->folderId == null) {
            $this->folderId = $this::SUB_FOLDER_ID;
        }

        $fileMetadata = new Google_Service_Drive_DriveFile(array(
            'name' => $fileName . '.txt',
            'parents' => array($this->folderId)
        ));

        $file = $this->service->files->create($fileMetadata, array(
            'data' => $fileContent,
            'mimeType' => 'application/octet-stream',
            'uploadType' => 'multipart',
            'fields' => 'id'));

        return $file->id;
    }

    /**
     * Create new folder in drive;
     * @param $foldername
     */
    public function createFolder($foldername) {
        $fileMetadata = new Google_Service_Drive_DriveFile(array(
            'name' => $foldername,
            'parents' => array($this::FOLDER_ID),
            'mimeType' => 'application/vnd.google-apps.folder'));
        $file = $this->service->files->create($fileMetadata, array(
            'fields' => 'id'));

        $this->folderId = $file->id;
    }

    /**
     * Returns an authorized API client.
     * @return Google_Client|null Google_Client the authorized client object
     */
    private function getClient() {
        $client = new Google_Client();
        $client->setApplicationName(DriveUtils::APPLICATION_NAME);
        $client->setScopes(implode(' ', array(Google_Service_Drive::DRIVE)));
        $client->setAuthConfig(DriveUtils::CLIENT_SECRET_PATH);
        $client->setAccessType('offline');

        // Load previously authorized credentials from a file.
        $credentialsPath = $this->expandHomeDirectory(DriveUtils::CREDENTIALS_PATH);
        if (file_exists($credentialsPath)) {
            $accessToken = json_decode(file_get_contents($credentialsPath), true);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

            // Store the credentials to disk.
            if(!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, json_encode($accessToken));
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }

    /**
     * Expands the home directory alias '~' to the full path.
     * @param string $path the path to expand.
     * @return string the expanded path.
     */
    private function expandHomeDirectory($path) {
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
        }
        return str_replace('~', realpath($homeDirectory), $path);
    }
}