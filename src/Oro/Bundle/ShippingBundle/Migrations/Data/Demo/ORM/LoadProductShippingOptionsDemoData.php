<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

use Oro\Bundle\ShippingBundle\Entity\FreightClass;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

class LoadProductShippingOptionsDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var array */
    protected $products = [];

    /** @var array */
    protected $productUnits = [];

    /** @var array|WeightUnit[] */
    protected $weightUnits = [];

    /** @var array|LengthUnit[] */
    protected $lengthUnits = [];

    /** @var array|FreightClass[] */
    protected $freightClasses = [];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData'
        ];
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/products.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        $processedPairs = [];

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $product = $this->getProductBySku($manager, $row['sku']);
            $productUnit = $this->getProductUnit($manager, $row['unit']);

            //Skip missed
            if ((!$product) || (!$productUnit)) {
                continue;
            }

            $currentPair = $product->getId() . '--' . $productUnit->getCode();
            //Skip already processed
            if (in_array($currentPair, $processedPairs, true)) {
                continue;
            }
            $processedPairs[] = $currentPair;

            $productShippingOptions = new ProductShippingOptions();
            $productShippingOptions->setProduct($product)
                ->setProductUnit($productUnit)
                ->setFreightClass($this->getRandomFreightClass($manager));

            $this->setProductDimensions($productShippingOptions, $manager);
            $this->setProductWeight($productShippingOptions, $manager);

            $manager->persist($productShippingOptions);
        }

        fclose($handler);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $sku
     *
     * @return Product|null
     */
    protected function getProductBySku(ObjectManager $manager, $sku)
    {
        if (!array_key_exists($sku, $this->products)) {
            $this->products[$sku] = $manager->getRepository('OroProductBundle:Product')->findOneBy(['sku' => $sku]);
        }

        return $this->products[$sku];
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     *
     * @return ProductUnit|null
     */
    protected function getProductUnit(ObjectManager $manager, $code)
    {
        if (!array_key_exists($code, $this->productUnits)) {
            $this->productUnits[$code] = $manager->getRepository('OroProductBundle:ProductUnit')->find($code);
        }

        return $this->productUnits[$code];
    }

    /**
     * @param ProductShippingOptions $productShippingOptions
     * @param ObjectManager $manager
     */
    protected function setProductWeight(ProductShippingOptions $productShippingOptions, ObjectManager $manager)
    {
        $model = new Weight();
        $model->setUnit($this->getRandomWeightUnit($manager))
            ->setValue(mt_rand(1, 10));
        $productShippingOptions->setWeight($model);
    }

    /**
     * @param ProductShippingOptions $productShippingOptions
     * @param ObjectManager $manager
     */
    protected function setProductDimensions(ProductShippingOptions $productShippingOptions, ObjectManager $manager)
    {
        $model = Dimensions::create(
            mt_rand(1, 10),
            mt_rand(1, 10),
            mt_rand(1, 10),
            $this->getRandomLengthUnit($manager)
        );

        $productShippingOptions->setDimensions($model);
    }

    /**
     * @param ObjectManager $manager
     *
     * @return WeightUnit
     */
    protected function getRandomWeightUnit(ObjectManager $manager)
    {
        if (!$this->weightUnits) {
            $this->weightUnits = $manager->getRepository('OroShippingBundle:WeightUnit')->findAll();

            if (!count($this->weightUnits)) {
                $this->weightUnits[] = (new WeightUnit())->setCode('demo_weight');
            }
        }

        return $this->weightUnits[mt_rand(0, count($this->weightUnits) - 1)];
    }

    /**
     * @param ObjectManager $manager
     *
     * @return LengthUnit
     */
    protected function getRandomLengthUnit(ObjectManager $manager)
    {
        if (!$this->lengthUnits) {
            $this->lengthUnits = $manager->getRepository('OroShippingBundle:LengthUnit')->findAll();

            if (!count($this->lengthUnits)) {
                $unit = (new LengthUnit())->setCode('demo_length');
                $manager->persist($unit);
                $this->lengthUnits[] = $unit;
            }
        }
        
        return $this->lengthUnits[mt_rand(0, count($this->lengthUnits) - 1)];
    }

    /**
     * @param ObjectManager $manager
     *
     * @return FreightClass
     */
    protected function getRandomFreightClass(ObjectManager $manager)
    {
        if (!$this->freightClasses) {
            $this->freightClasses = $manager->getRepository('OroShippingBundle:FreightClass')->findAll();

            if (!count($this->freightClasses)) {
                $freight = (new FreightClass())->setCode('parcel');
                $manager->persist($freight);
                $this->freightClasses[] = $freight;
            }
        }

        return $this->freightClasses[mt_rand(0, count($this->freightClasses) - 1)];
    }
}
