<?php

namespace Oro\Bundle\DPDBundle\Method\Factory;

use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\DPDBundle\Provider\RateProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Method\Identifier\DPDMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodType;
use Oro\Bundle\DPDBundle\Provider\DPDTransport;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;

class DPDShippingMethodTypeFactory implements DPDShippingMethodTypeFactoryInterface
{
    /**
     * @var DPDMethodTypeIdentifierGeneratorInterface
     */
    private $typeIdentifierGenerator;

    /**
     * @var IntegrationIdentifierGeneratorInterface
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
     * @param DPDMethodTypeIdentifierGeneratorInterface     $typeIdentifierGenerator
     * @param IntegrationIdentifierGeneratorInterface $methodIdentifierGenerator
     * @param DPDTransport                                  $transport
     * @param PackageProvider                               $packageProvider
     * @param RateProvider                                  $rateProvider
     */
    public function __construct(
        DPDMethodTypeIdentifierGeneratorInterface $typeIdentifierGenerator,
        IntegrationIdentifierGeneratorInterface $methodIdentifierGenerator,
        DPDTransport $transport,
        PackageProvider $packageProvider,
        RateProvider $rateProvider
    ) {
        $this->typeIdentifierGenerator = $typeIdentifierGenerator;
        $this->methodIdentifierGenerator = $methodIdentifierGenerator;
        $this->transport = $transport;
        $this->packageProvider = $packageProvider;
        $this->rateProvider = $rateProvider;
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
            $this->rateProvider
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
