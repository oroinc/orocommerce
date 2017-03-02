<?php

namespace Oro\Bundle\DPDBundle\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Form\Type\DPDShippingMethodOptionsType;
use Oro\Bundle\DPDBundle\Provider\DPDTransport as DPDTransportProvider;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\DPDBundle\Provider\RateProvider;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class DPDShippingMethodType implements ShippingMethodTypeInterface
{
    /** @var string */
    protected $identifier;

    /** @var string */
    protected $label;

    /**
     * @var string
     */
    protected $methodId;

    /**
     * @var DPDSettings
     */
    protected $transport;

    /**
     * @var DPDTransportProvider
     */
    protected $transportProvider;

    /**
     * @var ShippingService
     */
    protected $shippingService;

    /**
     * @var PackageProvider
     */
    protected $packageProvider;

    /**
     * @var RateProvider
     */
    protected $rateProvider;

    /**
     * @param $identifier
     * @param $label
     * @param string               $methodId
     * @param ShippingService      $shippingService
     * @param DPDSettings          $transport
     * @param DPDTransportProvider $transportProvider
     * @param PackageProvider      $packageProvider
     * @param RateProvider         $rateProvider
     */
    public function __construct(
        $identifier,
        $label,
        $methodId,
        ShippingService $shippingService,
        DPDSettings $transport,
        DPDTransportProvider $transportProvider,
        PackageProvider $packageProvider,
        RateProvider $rateProvider
    ) {
        $this->identifier = $identifier;
        $this->label = $label;
        $this->methodId = $methodId;
        $this->shippingService = $shippingService;
        $this->transport = $transport;
        $this->transportProvider = $transportProvider;
        $this->packageProvider = $packageProvider;
        $this->rateProvider = $rateProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfigurationFormType()
    {
        return DPDShippingMethodOptionsType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function calculatePrice(ShippingContextInterface $context, array $methodOptions, array $typeOptions)
    {
        if (!$context->getShippingAddress()) {
            return null;
        }

        $packageList = $this->packageProvider->createPackages($context->getLineItems());
        if (!$packageList || count($packageList) !== 1) { //TODO: implement multi package support
            return null;
        }

        $rateValue = $this->rateProvider->getRateValue(
            $this->transport,
            $this->shippingService,
            $context->getShippingAddress()
        );

        if ($rateValue === null) {
            return null;
        }

        $optionsDefaults = [
            DPDShippingMethod::HANDLING_FEE_OPTION => 0,
        ];
        $methodOptions = array_merge($optionsDefaults, $methodOptions);
        $typeOptions = array_merge($optionsDefaults, $typeOptions);

        $handlingFee =
            $methodOptions[DPDShippingMethod::HANDLING_FEE_OPTION] +
            $typeOptions[DPDShippingMethod::HANDLING_FEE_OPTION];

        return Price::create((float) $rateValue + (float) $handlingFee, $context->getCurrency());
    }
}
