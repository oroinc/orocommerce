<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Controller\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @dbIsolationPerTest
 */
class ShoppingListControllerTest extends WebTestCase
{
    private EntityManagerInterface $emProduct;

    private EntityManagerInterface $emFallback;

    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures(
            [
                'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
                'Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists',
                'Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
            ]
        );

        $this->emProduct = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManager(Product::class);
        $this->emFallback = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManager(
            EntityFieldFallbackValue::class
        );
        $this->translator = $this->getContainer()->get('translator');
    }

    /**
     * @dataProvider getShoppingListDataProvider
     */
    public function testQuantitysOnShoppingListView(int $quantity, int $minLimit, int $maxLimit): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $lineItem = $shoppingList->getLineItems()[0];
        $lineItem->setQuantity($quantity);
        $product = $lineItem->getProduct();
        $this->setProductLimits($product, $minLimit, $maxLimit);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_shopping_list_frontend_view',
                ['id' => $shoppingList->getId(), 'layout_block_ids' => ['combined_button_wrapper']]
            ),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $response = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($response, 200);

        $content = \json_decode($response->getContent(), true);
        $crawler = new Crawler($content['combined_button_wrapper']);

        $createOrderLabel = $this->translator->trans('oro.shoppinglist.btn.create_order');
        static::assertStringContainsString($createOrderLabel, $crawler->html());
    }

    public function getShoppingListDataProvider(): array
    {
        return [
            [
                'quantity' => 4,
                'minLimit' => 3,
                'maxLimit' => 5,
            ],
        ];
    }

    /**
     * @dataProvider getShoppingListErrorsDataProvider
     */
    public function testQuantityErrorMessagesOnShoppingListView(
        int $quantity,
        int $minLimit,
        int $maxLimit,
        int $errorLimit,
        string $errorMessage
    ): void {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $lineItem = $shoppingList->getLineItems()[0];
        $lineItem->setQuantity($quantity);
        $product = $lineItem->getProduct();
        $this->setProductLimits($product, $minLimit, $maxLimit);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_shopping_list_frontend_view', ['id' => $shoppingList->getId()])
        );

        $createOrderLabel = $this->translator->trans('oro.shoppinglist.btn.create_order');
        static::assertStringNotContainsString($createOrderLabel, $crawler->html());

        $errorMessage = $this->translator->trans(
            $errorMessage,
            ['%limit%' => $errorLimit, '%sku%' => $product->getSku(), '%product_name%' => $product->getName()]
        );
        static::assertStringContainsString($errorMessage, $this->client->getResponse()->getContent());
    }

    public function getShoppingListErrorsDataProvider(): array
    {
        return [
            [
                'quantity' => 2,
                'minLimit' => 3,
                'maxLimit' => 5,
                'errorLimit' => 3,
                'errorMessage' => 'oro.inventory.product.error.quantity_below_min_limit',
            ],
            [
                'quantity' => 6,
                'minLimit' => 3,
                'maxLimit' => 5,
                'errorLimit' => 5,
                'errorMessage' => 'oro.inventory.product.error.quantity_over_max_limit',
            ],
        ];
    }

    protected function setProductLimits(Product $product, int $minLimit, int $maxLimit): void
    {
        $entityFallback = $this->createFallbackEntity($minLimit);
        $entityFallback2 = $this->createFallbackEntity($maxLimit);
        $product->setMinimumQuantityToOrder($entityFallback);
        $product->setMaximumQuantityToOrder($entityFallback2);
        $this->emProduct->flush();
        $this->emFallback->flush();
    }

    /**
     * @param mixed $scalarValue
     * @return EntityFieldFallbackValue
     */
    protected function createFallbackEntity($scalarValue): EntityFieldFallbackValue
    {
        $entityFallback = new EntityFieldFallbackValue();
        $entityFallback->setScalarValue($scalarValue);
        $this->emFallback->persist($entityFallback);

        return $entityFallback;
    }
}
