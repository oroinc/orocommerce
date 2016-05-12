<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadProductShippingOptions;

/**
 * @dbIsolation
 */
class ProductShippingOptionsRepositoryTest extends WebTestCase
{
    /** @var ProductShippingOptionsRepository */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadProductShippingOptions'
            ]
        );

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BShippingBundle:ProductShippingOptions');
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->repository);
    }

    /**
     * @dataProvider getShippingOptionsByProductProvider
     *
     * @param string $productReference
     * @param array $optionsReferences
     */
    public function testGetShippingOptionsByProduct($productReference, array $optionsReferences)
    {
        /** @var Product $product */
        $product = $this->getReference($productReference);

        $expected = [];
        foreach ($optionsReferences as $optionReference) {
            $expected[] = $this->getReference($optionReference);
        }

        $expected = $this->getIds($expected);
        $actual = $this->getIds($this->repository->getShippingOptionsByProduct($product));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getShippingOptionsByProductProvider()
    {
        return [
            'product' => [
                'productReference' => 'product.1',
                'optionsReferences' => [
                    LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_2,
                    LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1
                ],
            ],
            'empty product' => [
                'productReference' => 'product.2',
                'optionsReferences' => [],
            ],
        ];
    }

    /**
     * @param ProductShippingOptions[] $options
     * @return array
     */
    protected function getIds(array $options)
    {
        $ids = [];
        foreach ($options as $option) {
            $ids[] = $option->getId();
        }

        return $ids;
    }
}
