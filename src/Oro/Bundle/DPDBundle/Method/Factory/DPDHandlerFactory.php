<?php

namespace Oro\Bundle\DPDBundle\Method\Factory;

use Oro\Bundle\DPDBundle\Cache\ZipCodeRulesCache;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Factory\DPDRequestFactory;
use Oro\Bundle\DPDBundle\Method\DPDHandler;
use Oro\Bundle\DPDBundle\Method\Identifier\DPDMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\DPDBundle\Provider\DPDTransport;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;

class DPDHandlerFactory implements DPDHandlerFactoryInterface
{
    /**
     * @var DPDMethodTypeIdentifierGeneratorInterface
     */
    private $typeIdentifierGenerator;

    /**
     * @var DPDTransport
     */
    private $transport;

    /**
     * @var PackageProvider
     */
    private $packageProvider;

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
     * @param DPDMethodTypeIdentifierGeneratorInterface $typeIdentifierGenerator
     * @param DPDTransport                              $transport
     * @param PackageProvider                           $packageProvider
     * @param DPDRequestFactory                         $dpdRequestFactory
     * @param ZipCodeRulesCache                         $zipCodeRulesCache
     * @param OrderShippingLineItemConverterInterface   $shippingLineItemConverter
     */
    public function __construct(
        DPDMethodTypeIdentifierGeneratorInterface $typeIdentifierGenerator,
        DPDTransport $transport,
        PackageProvider $packageProvider,
        DPDRequestFactory $dpdRequestFactory,
        ZipCodeRulesCache $zipCodeRulesCache,
        OrderShippingLineItemConverterInterface $shippingLineItemConverter
    ) {
        $this->typeIdentifierGenerator = $typeIdentifierGenerator;
        $this->transport = $transport;
        $this->packageProvider = $packageProvider;
        $this->dpdRequestFactory = $dpdRequestFactory;
        $this->zipCodeRulesCache = $zipCodeRulesCache;
        $this->shippingLineItemConverter = $shippingLineItemConverter;
    }

    /**
     * @param Channel         $channel
     * @param ShippingService $service
     *
     * @return DPDHandler
     */
    public function create(Channel $channel, ShippingService $service)
    {
        return new DPDHandler(
            $this->getIdentifier($channel, $service),
            $service,
            $this->getSettings($channel),
            $this->transport,
            $this->packageProvider,
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
     * @param Channel $channel
     *
     * @return \Oro\Bundle\IntegrationBundle\Entity\Transport|DPDSettings
     */
    private function getSettings(Channel $channel)
    {
        return $channel->getTransport();
    }
}
