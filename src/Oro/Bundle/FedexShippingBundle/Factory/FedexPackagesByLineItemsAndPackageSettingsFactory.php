<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Oro\Bundle\FedexShippingBundle\Builder\ShippingPackagesByLineItemBuilderInterface;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptionsInterface;

class FedexPackagesByLineItemsAndPackageSettingsFactory implements
    FedexPackagesByLineItemsAndPackageSettingsFactoryInterface
{
    /**
     * @var ShippingPackagesByLineItemBuilderInterface
     */
    private $packagesBuilder;

    /**
     * @var FedexPackageByShippingPackageOptionsFactoryInterface
     */
    private $fedexPackageFactory;

    public function __construct(
        ShippingPackagesByLineItemBuilderInterface $packagesBuilder,
        FedexPackageByShippingPackageOptionsFactoryInterface $fedexPackageFactory
    ) {
        $this->packagesBuilder = $packagesBuilder;
        $this->fedexPackageFactory = $fedexPackageFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function create(
        ShippingLineItemCollectionInterface $lineItemCollection,
        FedexPackageSettingsInterface $packageSettings
    ): array {
        $this->packagesBuilder->init($packageSettings);

        /** @var ShippingLineItemInterface $item */
        foreach ($lineItemCollection as $item) {
            if (!$item->getWeight() || !$item->getDimensions()) {
                return [];
            }

            if (!$this->packagesBuilder->addLineItem($item)) {
                return [];
            }
        }

        return $this->createFedexPackages($this->packagesBuilder->getResult());
    }

    /**
     * @param ShippingPackageOptionsInterface[] $packageOptions
     *
     * @return array
     */
    private function createFedexPackages(array $packageOptions): array
    {
        $fedexPackages = [];
        foreach ($packageOptions as $option) {
            $fedexPackages[] = $this->fedexPackageFactory->create($option);
        }

        return $fedexPackages;
    }
}
