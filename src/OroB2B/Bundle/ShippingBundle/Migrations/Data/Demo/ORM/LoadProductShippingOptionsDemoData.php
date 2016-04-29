<?php

namespace OroB2B\Bundle\ShippingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;
use OroB2B\Bundle\ShippingBundle\Model\Dimensions;
use OroB2B\Bundle\ShippingBundle\Model\Weight;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class LoadProductShippingOptionsDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var  DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @var array
     */
    protected $products = [];

    /**
     * @var array
     */
    protected $productUnits = [];

    /**
     * @var array|WeightUnit[]
     */
    protected $weightUnits = [];

    /**
     * @var array|LengthUnit[]
     */
    protected $lengthUnits = [];

    /**
     * @var array|FreightClass[]
     */
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
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData'
        ];
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BProductBundle/Migrations/Data/Demo/ORM/data/products.csv');
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
            if (in_array($currentPair, $processedPairs)) {
                continue;
            }
            $precessedPairs[] = $currentPair;

            $productShippingOptions = new ProductShippingOptions();
            $productShippingOptions
                ->setProduct($product)
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
            $this->products[$sku] = $manager->getRepository('OroB2BProductBundle:Product')->findOneBy(['sku' => $sku]);
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
            $this->productUnits[$code] = $manager->getRepository('OroB2BProductBundle:ProductUnit')->find($code);
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
        $model = new Dimensions();
        $model->setUnit($this->getRandomLengthUnit($manager))
            ->setLength(mt_rand(1, 10))
            ->setHeight(mt_rand(1, 10))
            ->setWidth(mt_rand(1, 10));
        $productShippingOptions->setDimensions($model);
    }

    /**
     * @param ObjectManager $manager
     *
     * @return WeightUnit
     */
    protected function getRandomWeightUnit(ObjectManager $manager)
    {

        if (count($this->weightUnits)) {
            return $this->weightUnits[mt_rand(0, count($this->weightUnits) - 1)];
        }

        $this->weightUnits = $manager->getRepository('OroB2BShippingBundle:WeightUnit')->findAll();

        if (!count($this->weightUnits)) {
            $this->weightUnits[] = (new WeightUnit())->setCode('demo_weight');
        }

        return $this->getRandomWeightUnit($manager);
    }

    /**
     * @param ObjectManager $manager
     *
     * @return LengthUnit
     */
    protected function getRandomLengthUnit(ObjectManager $manager)
    {
        if (count($this->lengthUnits)) {
            return $this->lengthUnits[mt_rand(0, count($this->lengthUnits) - 1)];
        }

        $this->lengthUnits = $manager->getRepository('OroB2BShippingBundle:LengthUnit')->findAll();

        if (!count($this->lengthUnits)) {
            $unit = (new LengthUnit())->setCode('demo_length');
            $manager->persist($unit);
            $this->lengthUnits[] = $unit;
        }

        return $this->getRandomLengthUnit($manager);
    }

    /**
     * @param ObjectManager $manager
     *
     * @return FreightClass
     */
    protected function getRandomFreightClass(ObjectManager $manager)
    {
        if (count($this->freightClasses)) {
            return $this->freightClasses[mt_rand(0, count($this->freightClasses) - 1)];
        }

        $this->freightClasses = $manager->getRepository('OroB2BShippingBundle:FreightClass')->findAll();

        if (!count($this->freightClasses)) {
            $freight = (new FreightClass())->setCode('demo_freight');
            $manager->persist($freight);
            $this->freightClasses[] = $freight;
        }

        return $this->getRandomFreightClass($manager);
    }
}
