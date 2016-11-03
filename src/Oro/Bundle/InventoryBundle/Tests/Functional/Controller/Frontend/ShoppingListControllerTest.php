<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Controller\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ShoppingListControllerTest extends WebTestCase
{
    /**
     * @var EntityManagerInterface
     */
    protected $emProduct;

    /**
     * @var EntityManagerInterface
     */
    protected $emFallback;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
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
     * @param $quantity
     * @param $minLimit
     * @param $maxLimit
     * @param $errorMessage
     * @param $errorLimit
     *
     * @dataProvider getShoppingListTestData
     */
    public function testQuantityErrorMessagesOnShoppingListView(
        $quantity,
        $minLimit,
        $maxLimit,
        $errorLimit,
        $errorMessage = null
    ) {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $lineItem = $shoppingList->getLineItems()[0];
        $lineItem->setQuantity($quantity);
        $product = $lineItem->getProduct();
        $this->setProductLimits($product, $minLimit, $maxLimit);

        $this->client->request(
            'GET',
            $this->getUrl('oro_shopping_list_frontend_view', ['id' => $shoppingList->getId()])
        );

        if ($errorMessage) {
            $errorMessage = $this->translator->trans(
                $errorMessage,
                ['%limit%' => $errorLimit, '%sku%' => $product->getSku(), '%product_name%' => $product->getName()]
            );
            $this->assertContains($errorMessage, $this->client->getResponse()->getContent());
            $this->assertNotContains('Create Order', $this->client->getResponse()->getContent());
        } else {
            $this->assertContains('Create Order', $this->client->getResponse()->getContent());
        }
    }

    /**
     * @return array
     */
    public function getShoppingListTestData()
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
            [
                'quantity' => 4,
                'minLimit' => 3,
                'maxLimit' => 5,
                'errorLimit' => null,
                'errorMessage' => null,
            ],
        ];
    }

    /**
     * @param Product $product
     * @param int $minLimit
     * @param int $maxLimit
     */
    protected function setProductLimits(Product $product, $minLimit, $maxLimit)
    {
        $entityFallback = new EntityFieldFallbackValue();
        $entityFallback->setScalarValue($minLimit);
        $entityFallback2 = new EntityFieldFallbackValue();
        $entityFallback2->setScalarValue($maxLimit);
        $this->emFallback->persist($entityFallback);
        $this->emFallback->persist($entityFallback2);
        $product->setMinimumQuantityToOrder($entityFallback);
        $product->setMaximumQuantityToOrder($entityFallback2);
        $this->emProduct->flush();
        $this->emFallback->flush();
    }
}
