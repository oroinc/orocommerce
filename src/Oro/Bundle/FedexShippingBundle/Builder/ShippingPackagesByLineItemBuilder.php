<?php

namespace Oro\Bundle\FedexShippingBundle\Builder;

use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Factory\ShippingPackageOptionsFactoryInterface;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptionsInterface;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Build shipping packages based on Line Items
 */
class ShippingPackagesByLineItemBuilder implements ShippingPackagesByLineItemBuilderInterface
{
    /**
     * @var ShippingPackageOptionsFactoryInterface
     */
    private $packageOptionsFactory;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var FedexPackageSettingsInterface
     */
    private $settings;

    /**
     * @var ShippingPackageOptionsInterface[]
     */
    private $packages;

    /**
     * @var ShippingPackageOptionsInterface
     */
    private $currentPackage;

    public function __construct(
        ShippingPackageOptionsFactoryInterface $packageOptionsFactory,
        ExpressionLanguage $expressionLanguage
    ) {
        $this->packageOptionsFactory = $packageOptionsFactory;
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * {@inheritDoc}
     */
    public function init(FedexPackageSettingsInterface $settings)
    {
        $this->settings = $settings;
        $this->packages = [];

        $this->resetCurrentPackage();
    }

    /**
     * {@inheritDoc}
     */
    public function addLineItem(ShippingLineItemInterface $lineItem): bool
    {
        $itemOptions = $this->packageOptionsFactory->create($lineItem->getDimensions(), $lineItem->getWeight());
        if (!$this->itemCanFit($itemOptions)) {
            return false;
        }

        for ($i = 0; $i < $lineItem->getQuantity(); $i++) {
            if (!$this->itemCanFitInCurrentPackage($itemOptions)) {
                $this->packCurrentPackage();
                $this->resetCurrentPackage();
            }

            $this->addItemToCurrentPackage($itemOptions);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult(): array
    {
        $this->packCurrentPackage();

        return $this->packages;
    }

    private function addItemToCurrentPackage(ShippingPackageOptionsInterface $itemOptions)
    {
        $weight = $this->currentPackage->getWeight() + $itemOptions->getWeight();

        if ($this->settings->isDimensionsIgnored()) {
            $dimensions = Dimensions::create(0, 0, 0, null);
        } else {
            $dimensions = $this->getNewPackageDimensions($itemOptions);
        }

        $this->currentPackage = $this->createPackageOptions($weight, $dimensions);
    }

    private function itemCanFitInCurrentPackage(ShippingPackageOptionsInterface $itemOptions): bool
    {
        return $this->expressionLanguage->evaluate(
            $this->settings->getLimitationExpression(),
            [
                'weight' => $itemOptions->getWeight() + $this->currentPackage->getWeight(),
                'length' => $itemOptions->getLength() + $this->currentPackage->getLength(),
                'width' => $itemOptions->getWidth() + $this->currentPackage->getWidth(),
                'height' => $itemOptions->getHeight() + $this->currentPackage->getHeight(),
            ]
        );
    }

    private function itemCanFit(ShippingPackageOptionsInterface $itemOptions): bool
    {
        return $this->expressionLanguage->evaluate(
            $this->settings->getLimitationExpression(),
            [
                'weight' => $itemOptions->getWeight(),
                'length' => $itemOptions->getLength(),
                'width' => $itemOptions->getWidth(),
                'height' => $itemOptions->getHeight(),
            ]
        );
    }

    private function packCurrentPackage()
    {
        if ($this->currentPackage->getWeight() > 0) {
            $this->packages[] = $this->currentPackage;
        }
    }

    private function resetCurrentPackage()
    {
        $this->currentPackage = $this->createPackageOptions(0, Dimensions::create(0, 0, 0, null));
    }

    private function createPackageOptions(
        float $weight,
        Dimensions $dimensions
    ): ShippingPackageOptionsInterface {
        $unit = null;

        return $this->packageOptionsFactory->create(
            $dimensions,
            Weight::create(
                $weight,
                (new WeightUnit())->setCode($this->settings->getUnitOfWeight())
            )
        );
    }

    private function getNewPackageDimensions(ShippingPackageOptionsInterface $itemOptions): Dimensions
    {
        $length = $this->currentPackage->getLength() + $itemOptions->getLength();
        $width = $this->currentPackage->getWidth() + $itemOptions->getWidth();
        $height = $this->currentPackage->getHeight() + $itemOptions->getHeight();
        $unit = (new LengthUnit())->setCode($this->settings->getDimensionsUnit());

        return Dimensions::create($length, $width, $height, $unit);
    }
}
