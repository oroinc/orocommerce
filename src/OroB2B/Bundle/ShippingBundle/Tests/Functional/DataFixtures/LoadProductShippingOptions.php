<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;
use OroB2B\Bundle\ShippingBundle\Model\Dimensions;
use OroB2B\Bundle\ShippingBundle\Model\Weight;

class LoadProductShippingOptions extends AbstractFixture implements DependentFixtureInterface
{
    const PRODUCT_SHIPPING_OPTIONS_1 = 'product_shipping_options.1';
    const PRODUCT_SHIPPING_OPTIONS_2 = 'product_shipping_options.2';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
            'OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadShippingOptionUnitsAndClasses'
        ];
    }

    /**
     * @var array
     */
    protected $data = [
        self::PRODUCT_SHIPPING_OPTIONS_1 => [
            'product' => 'product.1',
            'productUnit' => 'product_unit.liter',
            'weightValue' => 42,
            'weightUnit' => 'weight_unit.kilo',
            'dimensionsLength' => 1,
            'dimensionsWidth' => 2,
            'dimensionsHeight' => 3,
            'dimensionsUnit' => 'length_unit.in',
            'freightClass' => 'freight_class.pcl',
        ],
        self::PRODUCT_SHIPPING_OPTIONS_2 => [
            'product' => 'product.1',
            'productUnit' => 'product_unit.bottle',
            'weightValue' => 5,
            'weightUnit' => 'weight_unit.pound',
            'dimensionsLength' => 10,
            'dimensionsWidth' => 10,
            'dimensionsHeight' => 10,
            'dimensionsUnit' => 'length_unit.ft',
            'freightClass' => 'freight_class.pcl',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $reference => $data) {
            /** @var Product $product */
            $product = $this->getReference($data['product']);

            /** @var ProductUnit $unit */
            $unit = $this->getReference($data['productUnit']);

            /** @var WeightUnit $weightUnit */
            $weightUnit = $this->getReference($data['weightUnit']);

            /** @var LengthUnit $dimensionsUnit */
            $dimensionsUnit = $this->getReference($data['dimensionsUnit']);

            /** @var FreightClass $freightClass */
            $freightClass = $this->getReference($data['freightClass']);

            $weight = Weight::create($data['weightValue'], $weightUnit);

            $dimensions = Dimensions::create(
                $data['dimensionsLength'],
                $data['dimensionsWidth'],
                $data['dimensionsHeight'],
                $dimensionsUnit
            );

            $entity = new ProductShippingOptions();
            $entity
                ->setProduct($product)
                ->setProductUnit($unit)
                ->setWeight($weight)
                ->setDimensions($dimensions)
                ->setFreightClass($freightClass);

            $manager->persist($entity);

            $this->setReference($reference, $entity);
        }

        $manager->flush();
    }
}
