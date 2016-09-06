<?php

namespace Oro\Bundle\ShippingBundle\Method\UPS;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Factory\UPSShippingMethodTypeFactory;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class UPSShippingMethod implements ShippingMethodInterface, PricesAwareShippingMethodInterface
{
    const IDENTIFIER = 'ups';

    /** @var EntityRepository */
    protected $transportRepository;

    /** @var UPSShippingMethodTypeFactory */
    protected $methodTypeFactory;

    /**
     * @param EntityRepository $transportRepository
     * @param UPSShippingMethodTypeFactory $methodTypeFactory
     */
    public function __construct(EntityRepository $transportRepository, UPSShippingMethodTypeFactory $methodTypeFactory)
    {
        $this->transportRepository = $transportRepository;
        $this->methodTypeFactory = $methodTypeFactory;
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
        return static::IDENTIFIER;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.shipping.method.ups.label';
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
        return HiddenType::class;
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
        $transports = $this->transportRepository->findAll();
        if (count($transports) > 0) {
            /** @var UPSTransport $transport */
            foreach ($transports as $transport) {
                /** @var Collection $shippingServices */
                $shippingServices = $transport->getApplicableShippingServices();
                if (count($shippingServices) > 0) {
                    /** @var ShippingService $shippingService */
                    foreach ($shippingServices as $shippingService) {
                        $types[] = $this->methodTypeFactory->create(
                            $shippingService->getCode(),
                            $shippingService->getDescription()
                        );
                    }
                }
            }
        }

        return $types;
    }
}
