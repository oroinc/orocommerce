<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;

class ProductShippingOptionsRepositoryTest extends WebTestCase
{
    /** @var ProductShippingOptionsRepository */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
                ''
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

        $this->assertEquals(
            $this->getIds($expected),
            $this->getIds($this->repository->getShippingOptionsByProduct($product))
        );
    }

    /**
     * @return array
     */
    public function getShippingOptionsByProductProvider()
    {
        return [
            'first product' => [
                'productReference' => 'product.1',
                'optionsReferences' => [

                ],
            ],
            'second product' => [
                'productReference' => 'product.2',
                'optionsReferences' => [

                ],
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
