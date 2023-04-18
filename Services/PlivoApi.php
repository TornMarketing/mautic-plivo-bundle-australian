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
    public function __construct(TrackableModel $pageTrackableModel, PhoneNumberHelper $phoneNumberHelper, IntegrationHelper $integrationHelper, Logger $logger, Http $http)
    {
        $this->logger = $logger;
        $this->integrationHelper = $integrationHelper;
        $this->http = $http;
        $this->client = $http;
        $this->connected = false;
        parent::__construct($pageTrackableModel);

    }



    public function sendSms(Lead $contact, $content)
    {
        $number = $contact->getLeadPhoneNumber();
        if (empty($number)) {
            return false;
        }
     try {
        $integration = $this->integrationHelper->getIntegrationObject('Plivo');
        if ($integration && $integration->getIntegrationSettings()->getIsPublished()) {
            $data   = $integration->getDecryptedApiKeys();
            $client = new RestClient($data['AUTH_ID'], $data['AUTH_TOKEN']);
            
            try {
                $number = $this->sanitizeNumber($contact->getLeadPhoneNumber());
            } catch (NumberParseException $exception) {
                return $exception->getMessage();
            }
            
            try {
                $response = $client->messages->create(
                    $data['sender_phone_number'],
                    [$number],
                    $content
                );

                return true;
            } catch (PlivoRestException $ex) {
                if (method_exists($ex, 'getErrorMessage')) {
                    return $ex->getErrorMessage();
                } elseif (!empty($ex->getMessage())) {
                    return $ex->getMessage();
                }

                return false;
            }
        }
     }
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
        $parsed = $util->parse($number, 'AU');

        return $util->format($parsed, PhoneNumberFormat::E164);
    }



}
