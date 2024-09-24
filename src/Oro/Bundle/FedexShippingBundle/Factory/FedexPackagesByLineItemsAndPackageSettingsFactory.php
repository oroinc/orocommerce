<?php

namespace Oro\Bundle\FedexShippingBundle\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\FedexShippingBundle\Builder\ShippingPackagesByLineItemBuilderInterface;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptionsInterface;

/**
 * Creates FedEx packages by collection of {@see ShippingLineItem} and {@see FedexPackageSettingsInterface}.
 */
class FedexPackagesByLineItemsAndPackageSettingsFactory implements
    FedexPackagesByLineItemsAndPackageSettingsFactoryInterface
{
    private ShippingPackagesByLineItemBuilderInterface $packagesBuilder;

    private FedexPackageByShippingPackageOptionsFactoryInterface $fedexPackageFactory;

    public function __construct(
        ShippingPackagesByLineItemBuilderInterface $packagesBuilder,
        FedexPackageByShippingPackageOptionsFactoryInterface $fedexPackageFactory
    ) {
        $this->packagesBuilder = $packagesBuilder;
        $this->fedexPackageFactory = $fedexPackageFactory;
    }

    #[\Override]
    public function create(
        Collection $lineItemCollection,
        FedexPackageSettingsInterface $packageSettings
    ): array {
        $this->packagesBuilder->init($packageSettings);

        /** @var ShippingLineItem $item */
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
