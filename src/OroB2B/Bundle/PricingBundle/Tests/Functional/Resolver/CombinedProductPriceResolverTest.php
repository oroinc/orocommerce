<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Resolver;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedProductPrice;
use OroB2B\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver;

/**
 * @dbIsolation
 */
class CombinedProductPriceResolverTest extends WebTestCase
{
    /**
     * @var CombinedProductPriceResolver
     */
    protected $resolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists'
            ]
        );
        $this->resolver = $this->getContainer()->get('orob2b_pricing.resolver.combined_product_price_resolver');
    }

    /**
     * @dataProvider combinePricesDataProvider
     * @param $combinedPriceList
     * @param array $expectedPrices
     */
    public function testCombinePrices($combinedPriceList, array $expectedPrices)
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $this->resolver->combinePrices($combinedPriceList);

        $actualPrices = $this->getCombinedPrices($combinedPriceList);
        $this->assertEquals($expectedPrices, $actualPrices);
    }

    /**
     * @return array
     */
    public function combinePricesDataProvider()
    {
        return [
            [
                '1t_2t_3t',
                [
                    'product.1' => [
                        '1 USD/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter'
                    ],
                    'product.2' => [
                        '1 USD/1 bottle',
                        '10 USD/10 bottle'
                    ]
                ]
            ],
            [
                '2t_3f_1t',
                [
                    'product.1' => [
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                    ],
                    'product.2' => [
                        '10 USD/10 bottle'
                    ]
                ]
            ],
            [
                '2f_1t_3t',
                [
                    'product.1' => [
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                    ],
                    'product.2' => [
                        '1 USD/1 bottle',
                        '10 USD/10 bottle'
                    ]
                ]
            ]
        ];
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @return array
     */
    protected function getCombinedPrices(CombinedPriceList $combinedPriceList)
    {
        $actualPrices = [];
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:CombinedProductPrice')
            ->getRepository('OroB2BPricingBundle:CombinedProductPrice');

        /** @var CombinedProductPrice[] $prices */
        $prices = $repository->findBy(['priceList' => $combinedPriceList], ['quantity' => 'ASC', 'value' => 'ASC']);
        foreach ($prices as $price) {
            $actualPrices[$price->getProduct()->getSku()] =
                $price->getPrice()->getValue() . ' ' . $price->getPrice()->getCurrency()
                . '/' . $price->getQuantity() . ' ' . $price->getProductUnitCode();
        }

        return $actualPrices;
    }
}
