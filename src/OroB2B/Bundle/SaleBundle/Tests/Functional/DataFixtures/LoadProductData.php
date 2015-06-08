<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class LoadProductData extends AbstractFixture
{
    const PRODUCT1  = 'sale-sku1';
    const PRODUCT2  = 'sale-sku2';
    const PRODUCT3  = 'sale-sku3';

    const UNIT1 = 'sale-unit1';
    const UNIT2 = 'sale-unit2';
    const UNIT3 = 'sale-unit3';

    const CURRENCY1 = 'sale-USD';
    const CURRENCY2 = 'sale-EUR';

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
        $em = $this->entityManager;

        foreach ($this->products as $item) {
            $product = $this->createProduct($item);

            $em->persist($product);
        }

        $em->flush();
    }

    /**
     * @param array $data
     * @return Product
     */
    protected function createProduct(array $data)
    {
        /* @var $product Product */
        $product = new Product();

        $product
            ->setSku($data['sku'])
        ;

        foreach ($this->getUnitPrecisions($data['precisions']) as $precision) {
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
     * @return array|ProductUnitPrecision[]
     */
    protected function getUnitPrecisions($data)
    {
        $em = $this->entityManager;

        $precisions = [];
        foreach ($data as $code => $item) {
            $precision = new ProductUnitPrecision();

            $precision
                ->setUnit($this->getProductUnit($code))
                ->setPrecision($item)
            ;

            $precisions[] = $precision;

            $em->persist($precision);
        }
        $em->flush();

        return $precisions;
    }

    /**
     * @param string $code
     * @return ProductUnit
     * @throws \Exception
     */
    protected function getProductUnit($code)
    {
        if (!isset($this->units[$code])) {
            throw new \Exception(sprintf('Unit "%s" not found', $code));
        }

        $em = $this->entityManager;

        $unit = $em->getRepository('OroB2BProductBundle:ProductUnit')->findOneBy(['code' => $code]);

        if (!$unit) {
            $unit = new ProductUnit();
            $unit
                ->setCode($code)
                ->setDefaultPrecision($this->units[$code])
            ;

            $em->persist($unit);
            $em->flush();

            $this->setReference($code, $unit);
        }

        return $unit;
    }
}
