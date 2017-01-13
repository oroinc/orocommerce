<?php

namespace Oro\Bundle\DPDBundle\Provider;

use Oro\Bundle\DPDBundle\Model\ZipCodeRulesRequest;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesResponse;
use Oro\Bundle\DPDBundle\Model\SetOrderRequest;
use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\AbstractRestTransport;
use Oro\Bundle\DPDBundle\Form\Type\DPDTransportSettingsType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class DPDTransport extends AbstractRestTransport
{
    const API_VERSION = 100;
    const API_DEFAULT_LANGUAGE = 'en_EN';

    const BASE_URL_LIVE = 'https://cloud.dpd.com/api/v1/';
    const BASE_URL_STAGE = 'https://cloud-stage.dpd.com/api/v1/';

    const API_SET_ORDER = 'setOrder';
    const API_GET_ZIPCODE_RULES = 'ZipCodeRules';

    const PARTNER_NAME_LIVE = 'none'; //FIXME: request partner name
    const PARTNER_TOKEN_LIVE = 'none'; //FIXME: request

    const PARTNER_NAME_STAGE = 'DPD Sandbox';
    const PARTNER_TOKEN_STAGE = '06445364853584D75564';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /** @var SymmetricCrypterInterface */
    protected $symmetricCrypter;

    /**
     * @param LoggerInterface $logger
     * @param SymmetricCrypterInterface $symmetricCrypter
     */
    public function __construct(
        LoggerInterface $logger,
        SymmetricCrypterInterface $symmetricCrypter
    ) {
        $this->logger = $logger;
        $this->symmetricCrypter = $symmetricCrypter;
    }

    /**
     * @param ParameterBag $parameterBag
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function getClientBaseUrl(ParameterBag $parameterBag)
    {
        if ($parameterBag->get('live_mode', false)) {
            return static::BASE_URL_LIVE;
        }
        return static::BASE_URL_STAGE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getClientOptions(ParameterBag $parameterBag)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.dpd.transport.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return DPDTransportSettingsType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return 'Oro\Bundle\DPDBundle\Entity\DPDTransport';
    }


    /**
     * @param SetOrderRequest $setOrderRequest
     * @param Transport $transportEntity
     * @return null|SetOrderResponse
     */
    public function getSetOrderResponse(SetOrderRequest $setOrderRequest, Transport $transportEntity)
    {
        try {
            $this->client = $this->createRestClient($transportEntity);
            $headers = $this->getRequestHeaders($transportEntity);
            $data = $this->client->post(static::API_SET_ORDER, $setOrderRequest->toArray(), $headers)->json();

            return (new SetOrderResponse($data));
        } catch (RestException $restException) {
            $this->logger->error(
                sprintf(
                    'setOrder REST request failed for transport #%s. %s',
                    $transportEntity->getId(),
                    $restException->getMessage()
                )
            );
        }

        return null;
    }

    /**
     * @param ZipCodeRulesRequest $zipCodeRulesRequest
     * @param Transport $transportEntity
     * @return null|ZipCodeRulesResponse
     */
    public function getZipCodeRulesResponse(ZipCodeRulesRequest $zipCodeRulesRequest, Transport $transportEntity)
    {
        try {
            $this->client = $this->createRestClient($transportEntity);
            $headers = $this->getRequestHeaders($transportEntity);
            $data = $this->client->get(static::API_GET_ZIPCODE_RULES, [], $headers)->json();

            return (new ZipCodeRulesResponse($data));
        } catch (RestException $restException) {
            $this->logger->error(
                sprintf(
                    'ZipCodeRules REST request failed for transport #%s. %s',
                    $transportEntity->getId(),
                    $restException->getMessage()
                )
            );
        }

        return null;
    }

    protected function getRequestHeaders(Transport $transportEntity)
    {
        $headers = [
            'Version' =>  static::API_VERSION,
            'Language' => static::API_DEFAULT_LANGUAGE
        ];
        $headers += $this->getPartnerCredentialsHeaders($transportEntity->getSettingsBag());
        $headers += $this->getCloudUserCredentialsHeaders($transportEntity->getSettingsBag());
        return $headers;
    }

    protected function getPartnerCredentialsHeaders(ParameterBag $parameterBag)
    {
        if ($parameterBag->get('live_mode', false)) {
            $partnerName = static::PARTNER_NAME_LIVE;
            $partnerToken = static::PARTNER_TOKEN_LIVE;
        } else {
            $partnerName = static::PARTNER_NAME_STAGE;
            $partnerToken = static::PARTNER_TOKEN_STAGE;
        }

        return [
            'PartnerCredentials-Name' => $partnerName,
            'PartnerCredentials-Token' => $partnerToken
        ];
    }

    protected function getCloudUserCredentialsHeaders(ParameterBag $parameterBag)
    {
        $cloudUserId = $parameterBag->get('cloud_user_id');
        $decryptedCloudUserToken = $this->symmetricCrypter->decryptData($parameterBag->get('cloud_user_token'));

        return [
            'UserCredentials-cloudUserID' => $cloudUserId,
            'UserCredentials-Token' => $decryptedCloudUserToken
        ];
    }
}
