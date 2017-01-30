<?php

namespace Oro\Bundle\DPDBundle\Method\Factory;

use Oro\Bundle\DPDBundle\Cache\ZipCodeRulesCache;
use Oro\Bundle\DPDBundle\Factory\DPDRequestFactory;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\DPDBundle\Provider\RateProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Method\Identifier\DPDMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodType;
use Oro\Bundle\DPDBundle\Provider\DPDTransport;

class DPDShippingMethodTypeFactory implements DPDShippingMethodTypeFactoryInterface
{
    /**
     * @var DPDMethodTypeIdentifierGeneratorInterface
     */
    private $typeIdentifierGenerator;

    /**
     * @var IntegrationMethodIdentifierGeneratorInterface
     */
    private $methodIdentifierGenerator;

    /**
     * @var DPDTransport
     */
    private $transport;

    /**
     * @var PackageProvider
     */
    private $packageProvider;

    /**
     * @var RateProvider
     */
    private $rateProvider;

    /**
     * @var DPDRequestFactory
     */
    private $dpdRequestFactory;

    /**
     * @var ZipCodeRulesCache
     */
    private $zipCodeRulesCache;

    /**
     * @var OrderShippingLineItemConverterInterface
     */
    private $shippingLineItemConverter;

    /**
     * @param DPDMethodTypeIdentifierGeneratorInterface     $typeIdentifierGenerator
     * @param IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator
     * @param DPDTransport                                  $transport
     * @param PackageProvider                               $packageProvider
     * @param RateProvider                                  $rateProvider
     * @param DPDRequestFactory                             $dpdRequestFactory
     * @param ZipCodeRulesCache                             $zipCodeRulesCache
     * @param OrderShippingLineItemConverterInterface       $shippingLineItemConverter
     */
    public function __construct(
        DPDMethodTypeIdentifierGeneratorInterface $typeIdentifierGenerator,
        IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator,
        DPDTransport $transport,
        PackageProvider $packageProvider,
        RateProvider $rateProvider,
        DPDRequestFactory $dpdRequestFactory,
        ZipCodeRulesCache $zipCodeRulesCache,
        OrderShippingLineItemConverterInterface $shippingLineItemConverter
    ) {
        $this->typeIdentifierGenerator = $typeIdentifierGenerator;
        $this->methodIdentifierGenerator = $methodIdentifierGenerator;
        $this->transport = $transport;
        $this->packageProvider = $packageProvider;
        $this->rateProvider = $rateProvider;
        $this->dpdRequestFactory = $dpdRequestFactory;
        $this->zipCodeRulesCache = $zipCodeRulesCache;
        $this->shippingLineItemConverter = $shippingLineItemConverter;
    }

    /**
     * @param Channel         $channel
     * @param ShippingService $service
     *
     * @return DPDShippingMethodType
     */
    public function create(Channel $channel, ShippingService $service)
    {
        return new DPDShippingMethodType(
            $this->getIdentifier($channel, $service),
            $this->getLabel($service),
            $this->methodIdentifierGenerator->generateIdentifier($channel),
            $service,
            $this->getSettings($channel),
            $this->transport,
            $this->packageProvider,
            $this->rateProvider,
            $this->dpdRequestFactory,
            $this->zipCodeRulesCache,
            $this->shippingLineItemConverter
        );
    }

    /**
     * @param Channel         $channel
     * @param ShippingService $service
     *
     * @return string
     */
    private function getIdentifier(Channel $channel, ShippingService $service)
    {
        return $this->typeIdentifierGenerator->generateIdentifier($channel, $service);
    }

    /**
     * @param ShippingService $service
     *
     * @return string
     */
    private function getLabel(ShippingService $service)
    {
        return $service->getDescription();
    }

    /**
     * @param Channel $channel
     *
     * @return \Oro\Bundle\IntegrationBundle\Entity\Transport|DPDSettings
     */
    private function getSettings(Channel $channel)
    {
        return $channel->getTransport();
    }
}
