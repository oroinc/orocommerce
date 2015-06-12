<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class LoadProductData extends AbstractFixture
{
    const PRODUCT1  = 'rfpadmin-sku1';
    const PRODUCT2  = 'rfpadmin-sku2';
    const PRODUCT3  = 'rfpadmin-sku3';

    const UNIT1 = 'rfpadmin-unit1';
    const UNIT2 = 'rfpadmin-unit2';
    const UNIT3 = 'rfpadmin-unit3';

    const CURRENCY1 = 'USD';
    const CURRENCY2 = 'EUR';

    /**
     * @var array
     */
    protected $products = [
        [
            'sku' => self::PRODUCT1,
            'precisions' => [
                self::UNIT1 => 0,
                self::UNIT2 => 1
            ]
        ],
        [
            'sku' => self::PRODUCT2,
            'precisions' => [
                self::UNIT1 => 2,
                self::UNIT3 => 3
            ]
        ],
        [
            'sku' => self::PRODUCT3,
            'precisions' => [
                self::UNIT2 => 4,
                self::UNIT3 => 5
            ]
        ],
    ];

    /**
     * @var array
     */
    protected $units = [
        self::UNIT1 => 1,
        self::UNIT2 => 2,
        self::UNIT3 => 3,
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->products as $item) {
            $product = $this->createProduct($item, $manager);

            $manager->persist($product);
        }

        $manager->flush();
    }

    /**
     * @param array $data
     * @param ObjectManager $manager
     * @return Product
     */
    protected function createProduct(array $data, ObjectManager $manager)
    {
        /* @var $product Product */
        $product = new Product();

        $product
            ->setSku($data['sku'])
        ;

        foreach ($this->getUnitPrecisions($data['precisions'], $manager) as $precision) {
            /* @var $precision ProductUnitPrecision */
            $product
                ->addUnitPrecision($precision)
            ;
        }

        $this->setReference($product->getSku(), $product);

        return $product;
    }

    /**
     * @param array $data
     * @param ObjectManager $manager
     * @return array|ProductUnitPrecision[]
     */
    protected function getUnitPrecisions($data, ObjectManager $manager)
    {
        $precisions = [];
        foreach ($data as $code => $item) {
            $precision = new ProductUnitPrecision();

            $precision
                ->setUnit($this->getProductUnit($code, $manager))
                ->setPrecision($item)
            ;

            $precisions[] = $precision;

            $manager->persist($precision);
        }
        $manager->flush();

        return $precisions;
    }

    /**
     * @param string $code
     * @param ObjectManager $manager
     * @throws \Exception
     * @return ProductUnit
     */
    protected function getProductUnit($code, ObjectManager $manager)
    {
        if (!isset($this->units[$code])) {
            throw new \Exception(sprintf('Unit "%s" not found', $code));
        }

        $unit = $manager->getRepository('OroB2BProductBundle:ProductUnit')->findOneBy(['code' => $code]);

        if (!$unit) {
            $unit = new ProductUnit();
            $unit
                ->setCode($code)
                ->setDefaultPrecision($this->units[$code])
            ;

            $manager->persist($unit);
            $manager->flush();

            $this->setReference($code, $unit);
        }

        return $unit;
    }
}
