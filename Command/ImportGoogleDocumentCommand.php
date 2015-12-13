<?php
namespace BD\GoogleDocImportBundle\Command;

use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_Files_Resource;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportGoogleDocumentCommand extends ContainerAwareCommand
{
    /** @var Google_Service_Drive_Files_Resource */
    private $filesService;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('ez:gdoc:import')
            ->setDescription('Imports a google document')
            ->addArgument('id', InputArgument::REQUIRED, "Google doc id");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Print the names and IDs for up to 10 files.
//        $optParams = array(
//            'maxResults' => 10,
//        );
        // $results = $this->filesService->listFiles( $optParams );

        define('APPLICATION_NAME', 'Drive API PHP Quickstart');
        define('CREDENTIALS_PATH', '~/.credentials/drive-php-quickstart.json');
        define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
        define('SCOPES', implode(' ', [Google_Service_Drive::DRIVE_METADATA, Google_Service_Drive::DRIVE_FILE]));


        $this->filesService = (new Google_Service_Drive($this->getClient()))->files;

        $file = $this->filesService->get($input->getArgument('id'));
        $output->writeln("Title: " . $file->getTitle());
        dump($file);
    }

    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    private function getClient()
    {
        $client = new Google_Client();
        $client->setApplicationName(APPLICATION_NAME);
        $client->setScopes(SCOPES);
        $client->setAuthConfigFile(CLIENT_SECRET_PATH);
        $client->setAccessType('offline');

        // Load previously authorized credentials from a file.
        $credentialsPath = $this->expandHomeDirectory(CREDENTIALS_PATH);
        if (file_exists($credentialsPath)) {
            $accessToken = file_get_contents($credentialsPath);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->authenticate($authCode);

            // Store the credentials to disk.
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, $accessToken);
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->refreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, $client->getAccessToken());
        }

        return $client;
    }

    /**
     * Expands the home directory alias '~' to the full path.
     *
     * @param string $path the path to expand.
     *
     * @return string the expanded path.
     */
    private function expandHomeDirectory($path)
    {
        $homeDirectory = getenv('HOME');
        if (empty( $homeDirectory )) {
            $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
        }

        return str_replace('~', realpath($homeDirectory), $path);
    }
}
