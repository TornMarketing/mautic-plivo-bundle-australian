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

class MtalkzTransport extends AbstractSmsApi
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
                throw new \Exception("Mtalkz SMS is not configured properly.");
            }
            if (empty($content)) {
                throw new \Exception('Message content is Empty.');
            }

            $response = $this->send($number, $content);
            $this->logger->addInfo("Mtalkz SMS request succeeded. ", ['response' => $response]);
            return true;
        } catch (\Exception $e) {
            $this->logger->addError("Mtalkz SMS request failed. ", ['exception' => $e]);
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
        $params = array(
            'apikey' => $this->api_key,
            'senderid' => $this->sender_id,
            'number' => $number,
            'message' => $content,
            'format' => 'json',
        );
        $url = 'https://msgn.mtalkz.com/api' . http_build_query($params);
        $headers = ['Accept' => 'application/json'];

        $this->logger->addInfo("Mtalkz SMS API request intiated. ", ['url' => $url]);

        $response = $this->client->get($url, [
            'headers' => $headers,            
        ]);

        return $response->getBody();
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
        $parsed = $util->parse($number, 'IN');

        return $util->format($parsed, PhoneNumberFormat::E164);
    }

    /**
     * @return bool
     */
    protected function configureConnection()
    {
        $integration = $this->integrationHelper->getIntegrationObject('Mtalkz');
        if ($integration && $integration->getIntegrationSettings()->getIsPublished()) {
            $keys = $integration->getDecryptedApiKeys();
            if (empty($keys['api_key']) || empty($keys['sender_id'])) {
                return false;
            }
            $this->api_key = $keys['api_key'];
            $this->sender_id = $keys['sender_id'];
            $this->connected = true;
        }
        return $this->connected;
    }
}
