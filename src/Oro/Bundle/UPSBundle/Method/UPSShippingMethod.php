<?php

namespace Oro\Bundle\UPSBundle\Method;

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

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param UPSTransportProvider $transportProvider
     * @param Channel $channel
     * @param ManagerRegistry $registry
     */
    public function __construct(UPSTransportProvider $transportProvider, Channel $channel, ManagerRegistry $registry)
    {
        $this->transportProvider = $transportProvider;
        $this->channel = $channel;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function isGrouped()
    {
        return true;
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
     * @return ShippingMethodTypeInterface[]|array
     */
    public function getTypes()
    {
        $types = [];

        /** @var UPSTransport $transport */
        $transport = $this->channel->getTransport();
        /** @var ShippingService[] $shippingServices */
        $shippingServices = $transport->getApplicableShippingServices();
        if (count($shippingServices) > 0) {
            foreach ($shippingServices as $shippingService) {
                $types[] = new UPSShippingMethodType(
                    $transport,
                    $this->transportProvider,
                    $shippingService,
                    $this->registry
                );
            }
        }

        return $types;
    }

    /**
     * @param string $identifier
     * @return ShippingMethodTypeInterface|null
     */
    public function getType($identifier)
    {
        $methodTypes = $this->getTypes();
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
     * {@inheritdoc}
     */
    public function calculatePrices(ShippingContextInterface $context, array $methodOptions, array $optionsByTypes)
    {
        $prices = [];

        $types = $this->getTypes();
        if (!empty($types)) {
            foreach ($types as $type) {
                $prices[$type->getIdentifier()] = $type->calculatePrice($context, $methodOptions, $optionsByTypes);
            }
        }

        return $prices;
    }
}
