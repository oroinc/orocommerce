<?php

namespace Oro\Bundle\UPSBundle\Method\UPS;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;
use Oro\Bundle\UPSBundle\Provider\UPSTransport as UPSTransportProvider;

class UPSShippingMethod implements ShippingMethodInterface, PricesAwareShippingMethodInterface
{
    const IDENTIFIER = 'ups';

    /** @var UPSTransportProvider */
    protected $transportProvider;

    /** @var Channel */
    protected $channel;

    /**
     * @param UPSTransportProvider $transportProvider
     * @param Channel $channel
     */
    public function __construct(UPSTransportProvider $transportProvider, Channel $channel)
    {
        $this->transportProvider = $transportProvider;
        $this->channel = $channel;
    }

    /**
     * {@inheritdoc}
     */
    public function isGrouped()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return static::IDENTIFIER . '_' . $this->channel->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->channel->getName();
    }

    /**
     * @return ShippingMethodTypeInterface[]|null
     */
    public function getTypes()
    {
        return $this->getApplicableMethodTypes();
    }

    /**
     * @param string $identifier
     * @return ShippingMethodTypeInterface|null
     */
    public function getType($identifier)
    {
        $methodTypes = $this->getApplicableMethodTypes();
        if ($methodTypes !== null) {
            foreach ($methodTypes as $methodType) {
                if ($methodType->getIdentifier() === $identifier) {
                    return $methodType;
                }
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getOptionsConfigurationFormType()
    {
        return UPSShippingMethodOptionsType::class;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return 20;
    }

    /**
     * @param ShippingContextInterface $context
     * @param array $methodOptions
     * @param array $optionsByTypes
     * @return array
     */
    public function calculatePrices(ShippingContextInterface $context, array $methodOptions, array $optionsByTypes)
    {
        // TODO: Implement calculatePrices() method.
    }

    /**
     * @return ShippingMethodTypeInterface[]|null
     */
    protected function getApplicableMethodTypes()
    {
        $types = null;

        /** @var UPSTransport $transport */
        $transport = $this->channel->getTransport();
        /** @var ShippingService[] $shippingServices */
        $shippingServices = $transport->getApplicableShippingServices();
        if (count($shippingServices) > 0) {
            foreach ($shippingServices as $shippingService) {
                $types[] = new UPSShippingMethodType($transport, $this->transportProvider, $shippingService);
            }
        }

        return $types;
    }
}
