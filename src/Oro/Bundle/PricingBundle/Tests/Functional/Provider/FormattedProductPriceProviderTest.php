<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Provider;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\PricingBundle\Provider\FormattedProductPriceProvider;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

class FormattedProductPriceProviderTest extends FrontendWebTestCase
{
    private FormattedProductPriceProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([LoadCombinedProductPrices::class]);

        $container = self::getContainer();
        $user = $container->get('oro_customer_user.manager')->findUserByEmail(LoadCustomerUserData::AUTH_USER);
        $container->get('security.token_storage')->setToken(new UsernamePasswordOrganizationToken(
            $user,
            'main',
            $user->getOrganization()
        ));
        $container->get('oro_frontend.request.frontend_helper')->emulateFrontendRequest();

        $this->provider = self::getContainer()->get('oro_pricing.provider.formatted_product_price');
    }

    private function getProduct(string $reference): Product
    {
        return $this->getReference($reference);
    }

    public function testGetFormattedProductPricesForNotExistingProducts()
    {
        self::assertSame([], $this->provider->getFormattedProductPrices([9999999]));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetFormattedProductPricesReturnsProductsWithPrices()
    {
        $product1 = $this->getProduct('product-1');
        $product2 = $this->getProduct('product-2');
        $productWithoutPrices = $this->getProduct('product-8');

        $formattedProductPrices = $this->provider->getFormattedProductPrices([
            $product1->getId(),
            $product2->getId(),
            $productWithoutPrices->getId()
        ]);

        self::assertEquals(
            [
                $product1->getId() => [
                    'prices' => [
                        'milliliter' => [
                            [
                                'price' => 0.0,
                                'currency' => 'USD',
                                'quantity' => 1.0,
                                'unit' => 'milliliter',
                                'formatted_price' => '$0.00',
                                'formatted_unit' => 'milliliter',
                                'quantity_with_unit' => '1 milliliter'
                            ]
                        ],
                        'liter' => [
                            [
                                'price' => 10.0,
                                'currency' => 'USD',
                                'quantity' => 1.0,
                                'unit' => 'liter',
                                'formatted_price' => '$10.00',
                                'formatted_unit' => 'liter',
                                'quantity_with_unit' => '1 liter'
                            ],
                            [
                                'price' => 12.2,
                                'currency' => 'USD',
                                'quantity' => 10.0,
                                'unit' => 'liter',
                                'formatted_price' => '$12.20',
                                'formatted_unit' => 'liter',
                                'quantity_with_unit' => '10 liters'
                            ]
                        ],
                        'bottle' => [
                            [
                                'price' => 13.1,
                                'currency' => 'USD',
                                'quantity' => 1.0,
                                'unit' => 'bottle',
                                'formatted_price' => '$13.10',
                                'formatted_unit' => 'bottle',
                                'quantity_with_unit' => '1 bottle'
                            ]
                        ]
                    ],
                    'units' => ['milliliter' => 0, 'liter' => 3, 'bottle' => 2]
                ],
                $product2->getId() => [
                    'prices' => [
                        'milliliter' => [
                            [
                                'price' => 0.0,
                                'currency' => 'USD',
                                'quantity' => 1.0,
                                'unit' => 'milliliter',
                                'formatted_price' => '$0.00',
                                'formatted_unit' => 'milliliter',
                                'quantity_with_unit' => '1 milliliter'
                            ]
                        ],
                        'liter' => [
                            [
                                'price' => 20.0,
                                'currency' => 'USD',
                                'quantity' => 1.0,
                                'unit' => 'liter',
                                'formatted_price' => '$20.00',
                                'formatted_unit' => 'liter',
                                'quantity_with_unit' => '1 liter'
                            ],
                            [
                                'price' => 12.2,
                                'currency' => 'USD',
                                'quantity' => 12.0,
                                'unit' => 'liter',
                                'formatted_price' => '$12.20',
                                'formatted_unit' => 'liter',
                                'quantity_with_unit' => '12 liters'
                            ]
                        ]
                    ],
                    'units' => ['milliliter' => 0, 'liter' => 3, 'bottle' => 1, 'box' => 1]
                ],
                $productWithoutPrices->getId() => [
                    'prices' => [],
                    'units' => ['milliliter' => 0]
                ]
            ],
            $formattedProductPrices
        );
    }
}
