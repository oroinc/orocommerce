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

    /**
     * @param ShippingPackageOptionsFactoryInterface $packageOptionsFactory
     * @param ExpressionLanguage                     $expressionLanguage
     */
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

    /**
     * @param ShippingPackageOptionsInterface $itemOptions
     */
    private function addItemToCurrentPackage(ShippingPackageOptionsInterface $itemOptions)
    {
        $weight = $this->currentPackage->getWeight() + $itemOptions->getWeight();
        $length = $this->currentPackage->getLength() + $itemOptions->getLength();
        $width = $this->currentPackage->getWidth() + $itemOptions->getWidth();
        $height = $this->currentPackage->getHeight() + $itemOptions->getHeight();

        $this->currentPackage = $this->createPackageOptions($weight, $length, $width, $height);
    }

    /**
     * @param ShippingPackageOptionsInterface $itemOptions
     *
     * @return bool
     */
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

    /**
     * @param ShippingPackageOptionsInterface $itemOptions
     *
     * @return bool
     */
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
        $this->currentPackage = $this->createPackageOptions(0, 0, 0, 0);
    }

    /**
     * @param float $weight
     * @param float $length
     * @param float $width
     * @param float $height
     *
     * @return ShippingPackageOptionsInterface
     */
    private function createPackageOptions(
        float $weight,
        float $length,
        float $width,
        float $height
    ): ShippingPackageOptionsInterface {
        return $this->packageOptionsFactory->create(
            Dimensions::create(
                $length,
                $width,
                $height,
                (new LengthUnit())->setCode($this->settings->getDimensionsUnit())
            ),
            Weight::create(
                $weight,
                (new WeightUnit())->setCode($this->settings->getUnitOfWeight())
            )
        );
    }
}
