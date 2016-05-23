<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Resolver;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

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
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPricesForCombination',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists'
            ]
        );
        $this->resolver = $this->getContainer()->get('orob2b_pricing.resolver.combined_product_price_resolver');
    }

    /**
     * @dataProvider combinePricesDataProvider
     * @param string $combinedPriceList
     * @param array $expectedPrices
     * @param array $expectedMinPrices
     */
    public function testCombinePrices($combinedPriceList, array $expectedPrices, array $expectedMinPrices)
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $this->resolver->combinePrices($combinedPriceList);

        $actualPrices = $this->getCombinedPrices($combinedPriceList);
        $this->assertEquals($expectedPrices, $actualPrices);
        $actualMinimalPrices = $this->getMinimalPrices($combinedPriceList);
        $this->assertEquals($expectedMinPrices, $actualMinimalPrices);
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
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter'
                    ],
                    'product.2' => [
                        '1 USD/1 bottle',
                        '10 USD/10 bottle'
                    ]
                ],
                [
                    'product.1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                    ],
                    'product.2' => [
                        '1 USD/1 bottle',
                    ]
                ],
            ],
            [
                '2t_3f_1t',
                [
                    'product.1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                    ],
                    'product.2' => [
                        '10 USD/10 bottle'
                    ]
                ],
                [
                    'product.1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                    ],
                    'product.2' => [
                        '10 USD/10 bottle',
                    ]
                ],
            ],
            [
                '2f_1t_3t',
                [
                    'product.1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                    ],
                    'product.2' => [
                        '1 USD/1 bottle',
                        '10 USD/10 bottle'
                    ]
                ],
                [
                    'product.1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                    ],
                    'product.2' => [
                        '1 USD/1 bottle',
                    ]
                ],
            ]
        ];
    }

    /**
     * @depends testCombinePrices
     * @dataProvider addedCombinePricesDataProvider
     * @param string $combinedPriceList
     * @param array $expectedPrices
     */
    public function testCombinePricesByProductPriceAdded($combinedPriceList, array $expectedPrices)
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $this->resolver->combinePrices($combinedPriceList);
        $this->assertTrue($combinedPriceList->isPricesCalculated());
        $this->assertNotEmpty($this->getCombinedPrices($combinedPriceList));

        /** @var Product $product */
        $product = $this->getReference('product.2');
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_2');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');
        $price = Price::create(42, 'EUR');
        $productPrice = new ProductPrice();
        $productPrice->setProduct($product)
            ->setPriceList($priceList)
            ->setPrice($price)
            ->setQuantity(1)
            ->setUnit($unit);
        $this->getEntityManager()->persist($productPrice);
        $this->getEntityManager()->flush($productPrice);

        $combinedPriceList->setPricesCalculated(false);
        $this->resolver->combinePrices($combinedPriceList, $product);
        $this->assertFalse($combinedPriceList->isPricesCalculated());
        $actualPrices = $this->getCombinedPrices($combinedPriceList);

        $this->getEntityManager()->remove($productPrice);
        $this->getEntityManager()->flush($productPrice);

        $this->assertEquals($expectedPrices, $actualPrices);
    }

    /**
     * @return array
     */
    public function addedCombinePricesDataProvider()
    {
        return [
            [
                '1t_2t_3t',
                [
                    'product.1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter'
                    ],
                    'product.2' => [
                        '1 USD/1 bottle',
                        '42 EUR/1 liter',
                        '10 USD/10 bottle',
                    ]
                ]
            ],
            [
                '2t_3f_1t',
                [
                    'product.1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                    ],
                    'product.2' => [
                        '1 USD/1 bottle',
                        '42 EUR/1 liter',
                    ]
                ]
            ],
            [
                '2f_1t_3t',
                [
                    'product.1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                    ],
                    'product.2' => [
                        '42 EUR/1 liter',
                    ]
                ]
            ]
        ];
    }

    /**
     * @depends testCombinePricesByProductPriceAdded
     * @dataProvider updatedCombinePricesDataProvider
     * @param string $combinedPriceList
     * @param array $expectedPrices
     */
    public function testCombinePricesByProductPriceUpdate($combinedPriceList, array $expectedPrices)
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $this->resolver->combinePrices($combinedPriceList);
        $this->assertNotEmpty($this->getCombinedPrices($combinedPriceList));

        /** @var ProductPrice $price */
        $price = $this->getReference('product_price.7');
        $price->setQuantity(20);
        $this->getEntityManager()->persist($price);
        $this->getEntityManager()->flush($price);

        $this->resolver->combinePrices($combinedPriceList, $price->getProduct());

        $actualPrices = $this->getCombinedPrices($combinedPriceList);
        $this->assertEquals($expectedPrices, $actualPrices);
    }

    /**
     * @return array
     */
    public function updatedCombinePricesDataProvider()
    {
        return [
            [
                '1t_2t_3t',
                [
                    'product.1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter'
                    ],
                    'product.2' => [
                        '1 USD/1 bottle',
                        '10 USD/20 bottle'
                    ]
                ]
            ],
            [
                '2t_3f_1t',
                [
                    'product.1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                    ],
                    'product.2' => [
                        '10 USD/20 bottle'
                    ]
                ]
            ],
            [
                '2f_1t_3t',
                [
                    'product.1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                    ],
                    'product.2' => [
                        '1 USD/1 bottle',
                        '10 USD/20 bottle'
                    ]
                ]
            ]
        ];
    }

    /**
     * @depends testCombinePricesByProductPriceUpdate
     * @dataProvider removedCombinePricesDataProvider
     * @param string $combinedPriceList
     * @param array $expectedPrices
     */
    public function testCombinePricesByProductPriceRemoved($combinedPriceList, array $expectedPrices)
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $this->resolver->combinePrices($combinedPriceList);
        $this->assertNotEmpty($this->getCombinedPrices($combinedPriceList));

        /** @var Product $product */
        $product = $this->getReference('product.2');
        /** @var ProductPrice $price */
        $price = $this->getReference('product_price.7');
        $this->getEntityManager()->remove($price);
        $this->getEntityManager()->flush($price);

        $this->resolver->combinePrices($combinedPriceList, $product);

        $actualPrices = $this->getCombinedPrices($combinedPriceList);
        $this->assertEquals($expectedPrices, $actualPrices);

        //recreate price for next test
        $this->getEntityManager()->persist($price);
        $this->getEntityManager()->flush($price);
    }

    /**
     * @return array
     */
    public function removedCombinePricesDataProvider()
    {
        return [
            [
                '1t_2t_3t',
                [
                    'product.1' => [
                        '1 USD/1 liter',
                        '2 EUR/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                        '15 USD/10 liter'
                    ],
                    'product.2' => [
                        '1 USD/1 bottle'
                    ]
                ]
            ],
            [
                '2t_3f_1t',
                [
                    'product.1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                        '10 USD/9 liter',
                    ],
                    'product.2' => [
                        '1 USD/1 bottle'
                    ]
                ]
            ],
            [
                '2f_1t_3t',
                [
                    'product.1' => [
                        '2 EUR/1 liter',
                        '2 USD/1 liter',
                        '3 USD/1 bottle',
                    ],
                    'product.2' => [
                        '1 USD/1 bottle',
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
        /** @var CombinedProductPriceRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:CombinedProductPrice')
            ->getRepository('OroB2BPricingBundle:CombinedProductPrice');

        /** @var CombinedProductPrice[] $prices */
        $prices = $repository->findBy(
            ['priceList' => $combinedPriceList],
            ['product' => 'ASC', 'quantity' => 'ASC', 'value' => 'ASC', 'currency' => 'ASC']
        );

        return $this->formatPrices($prices);
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @return array
     */
    protected function getMinimalPrices(CombinedPriceList $combinedPriceList)
    {
        /** @var CombinedProductPriceRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:MinimalProductPrice')
            ->getRepository('OroB2BPricingBundle:MinimalProductPrice');

        /** @var CombinedProductPrice[] $prices */
        $prices = $repository->findBy(
            ['priceList' => $combinedPriceList],
            ['product' => 'ASC', 'quantity' => 'ASC', 'value' => 'ASC', 'currency' => 'ASC']
        );

        return $this->formatPrices($prices);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @param array|ProductPrice[] $prices
     * @return array
     */
    protected function formatPrices(array $prices)
    {
        $actualPrices = [];
        foreach ($prices as $price) {
            $actualPrices[$price->getProduct()->getSku()][] = sprintf(
                '%d %s/%d %s',
                $price->getPrice()->getValue(),
                $price->getPrice()->getCurrency(),
                $price->getQuantity(),
                $price->getProductUnitCode()
            );
        }

        return $actualPrices;
    }
}
