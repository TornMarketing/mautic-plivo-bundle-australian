<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author      Jan Kozak <galvani78@gmail.com>
 */


namespace MauticPlugin\MauticPlivoBundle\Services;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\SmsBundle\Api\AbstractSmsApi;
use Monolog\Logger;
use GuzzleHttp\Client;


use Plivo\Exceptions\PlivoRestException;
use Plivo\RestClient;

class PlivoApi extends AbstractSmsApi
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    private $api_key;

    /**
     * @var string
     */
    private $auth_id;
	
    /**
     * @var string
     */
    private $auth_token;

    /**
     * @var string
     */
    private $user_name;
	
    /**
     * @var string
     */
    private $user_password;

    /**
     * @var string
     */
    private $sender_id;

    /**
     * @var bool
     */
    protected $connected;

    /**
     * @var string
     */
    protected $originator;
    
     /**
     * @var Http
     */
    private $http;
    
    /**
     * @param IntegrationHelper $integrationHelper
     * @param Logger            $logger
     * @param Client            $client
     */
    public function __construct(IntegrationHelper $integrationHelper, Logger $logger, Client $client)
    {
        $this->integrationHelper = $integrationHelper;
        $this->logger = $logger;
        $this->client = $client;
        $this->connected = false;
    }

    /**
     * @param Lead   $contact
     * @param string $content
     *
     * @return bool|string
     */
    public function sendSms(Lead $contact, $content)
    {
        $number = $contact->getLeadPhoneNumber();
        if (empty($number)) {
            return false;
        }

        try {
            $number = substr($this->sanitizeNumber($number), 1);
        } catch (NumberParseException $e) {
            $this->logger->addInfo('Invalid number format. ', ['exception' => $e]);
            return $e->getMessage();
        }
        
        try {
            if (!$this->connected && !$this->configureConnection()) {
                throw new \Exception("Plivo SMS is not configured properly.");
            }
            if (empty($content)) {
                throw new \Exception('Message content is Empty.');
            }

            $response = $this->send($number, $content);
            $this->logger->addInfo("Plivo SMS request succeeded. ", ['response' => $response]);
            return true;
        } catch (\Exception $e) {
            $this->logger->addError("Plivo SMS request failed. ", ['exception' => $e]);
            return $e->getMessage();
        }
    }

    /**
     * @param integer   $number
     * @param string    $content
     * 
     * @return array
     * 
     * @throws \Exception
     */
    protected function send($number, $content)
    {
		
		$user_name = $this->auth_id;
		$user_password = $this->auth_token;
		
        $params = array(
            'src' => $this->sender_phone_number,
            'dst' => $number,
            'text' => $content
        );

        $params = json_encode($params);

		
        $url = 'https://api.plivo.com/v1/Account/' . $user_name .'/Message/';

    //   $url = 'https://webhook.site/9a697cc2-01e5-4e40-a202-7d54144f5716?';

		$ch = curl_init($url);
		$headers = array(
		'Content-Type: application/json',
		'Authorization: Basic '. base64_encode("$user_name:$user_password")
		);

			//Set the headers that we want our cURL client to use.
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			//Set the body params that we want our cURL client to use.
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

			// Set the RETURNTRANSFER as true so that output will come as a string
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			//Execute the cURL request.
			$response = curl_exec($ch);

			//Check if any errors occured.
			if(curl_errno($ch)){
			// throw the an Exception.
			throw new Exception(curl_error($ch));
			}

			curl_close($ch);

			$this->logger->addInfo("Plivo SMS API request intiated. ", ['url' => $url]);

			//get the response.
			return $response;

    }
    
    /**
     * @param string $number
     *
     * @return string
     *
     * @throws NumberParseException
     */
    protected function sanitizeNumber($number)
    {
        $util = PhoneNumberUtil::getInstance();
        $parsed = $util->parse($number, $this->default_country_code);

        return $util->format($parsed, PhoneNumberFormat::E164);
    }

    /**
     * @return bool
     */
    protected function configureConnection()
    {
        $integration = $this->integrationHelper->getIntegrationObject('Plivo');
        if ($integration && $integration->getIntegrationSettings()->getIsPublished()) {
            $keys = $integration->getDecryptedApiKeys();
            if (empty($keys['auth_token']) || empty($keys['auth_id']) || empty($keys['sender_phone_number'])) {
                return false;
            }
            $this->auth_token = $keys['auth_token'];
            $this->auth_id = $keys['auth_id'];
            $this->default_country_code = $keys['default_country_code'];
            $this->alternative_sender_number_contact_field = $keys['alternative_sender_number_contact_field'];
            $this->sender_phone_number = $keys['sender_phone_number'];
            $this->connected = true;
        }
        return $this->connected;
    }
}
