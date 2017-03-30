<?php

namespace Oro\Bundle\ApruveBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\IntegrationBundle\Entity\Transport;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\ApruveBundle\Entity\Repository\ApruveSettingsRepository")
 */
class ApruveSettings extends Transport
{
    const MERCHANT_ID_KEY = 'metchant_id';
    const API_KEY_KEY = 'api_key';

    const LEARN_MORE_URL_KEY = 'learn_more_url';
    const WEBHOOK_TOKEN_KEY = 'webhook_token';

    const TEST_MODE_KEY = 'test_mode';

    /**
     * @var ParameterBag
     */
    protected $settings;

    /**
     * @var bool
     *
     * @ORM\Column(name="ups_test_mode", type="boolean", nullable=false, options={"default"=false})
     */
    protected $testMode = false;

    /**
     * @var string
     *
     * @ORM\Column(name="apruve_merchant_id", type="string", length=255, nullable=false)
     */
    protected $merchantId;

    /**
     * @var string
     *
     * @ORM\Column(name="apruve_api_key", type="string", length=255, nullable=false)
     */
    protected $apiKey;

    /**
     * @var string
     *
     * @ORM\Column(name="apruve_learn_more", type="string", length=1023)
     */
    protected $learnMoreUrl;

    /**
     * @var string
     *
     * Used in webhook URL.
     *
     * @ORM\Column(name="apruve_webhook_token", type="string", length=36)
     */
    protected $webhookToken;

    /**
     * @return ParameterBag
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    self::MERCHANT_ID_KEY => $this->getMerchantId(),
                    self::API_KEY_KEY => $this->getApiKey(),
                    self::TEST_MODE_KEY => $this->getTestMode(),
                    self::LEARN_MORE_URL_KEY => $this->getLearnMoreUrl(),
                    self::WEBHOOK_TOKEN_KEY => $this->getWebhookToken(),
                ]
            );
        }

        return $this->settings;
    }

    /**
     * @return bool
     */
    public function getTestMode()
    {
        return $this->testMode;
    }

    /**
     * @param bool $testMode
     *
     * @return ApruveSettings
     */
    public function setTestMode($testMode)
    {
        $this->testMode = $testMode;

        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @param string $merchantId
     *
     * @return ApruveSettings
     */
    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     *
     * @return ApruveSettings
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getLearnMoreUrl()
    {
        return $this->learnMoreUrl;
    }

    /**
     * @param string $learnMoreUrl
     *
     * @return ApruveSettings
     */
    public function setLearnMoreUrl($learnMoreUrl)
    {
        $this->learnMoreUrl = $learnMoreUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebhookToken()
    {
        return $this->webhookToken;
    }

    /**
     * @param string $webhookToken
     *
     * @return ApruveSettings
     */
    public function setWebhookToken($webhookToken)
    {
        $this->webhookToken = $webhookToken;

        return $this;
    }
}
