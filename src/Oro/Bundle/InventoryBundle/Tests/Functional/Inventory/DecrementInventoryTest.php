<?php

namespace Oro\Bundle\InventoryBundle\Tests\Inventory;

use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadInventoryLevels;
use Symfony\Component\DomCrawler\Crawler;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend\CheckoutControllerTestCase;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolationPerTest
 * @group CommunityEdition
 */
class DecrementInventoryTest extends CheckoutControllerTestCase
{
    const CHECKOUT_STEP_LABEL = "//h2[contains(@class, 'checkout__title')]";
    const PRODUCT_ERROR_TEXT = "There is not enough quantity for this product";

    /**
     * @var EntityManagerInterface
     */
    protected $emFallback;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function setUp()
    {
        parent::setUp();
        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $this->emFallback = $this->doctrineHelper->getEntityManager(EntityFieldFallbackValue::class);
    }

    public function testOrderWithoutDecrement()
    {
        $shoppingList = $this->prepareShoppingList(LoadInventoryLevels::PRECISION_BOTTLE_QTY_99, '0');

        $crawler = $this->navigateThroughCheckout();
        $form = $crawler->selectButton('Submit Order')->form();
        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $form->getPhpValues(),
            $form->getPhpFiles(),
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertArrayHasKey('successUrl', $data['responseData']);
        $this->assertNotEmpty($data['responseData']['successUrl']);
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $data['responseData']['returnUrl']);
        $this->assertContains(self::FINISH_SIGN, $crawler->html());
        $this->assertNull($this->doctrineHelper->getEntityRepositoryForClass(ShoppingList::class)->find($shoppingList));
        $inventoryLevel = $this->getInventoryLevel($shoppingList);
        $initialQuantity = LoadInventoryLevels::PRECISION_BOTTLE_QTY_99;
        $inventoryLevel = $this->doctrineHelper
            ->getEntityRepositoryForClass(InventoryLevel::class)
            ->find($inventoryLevel);
        $this->assertEquals($initialQuantity, $inventoryLevel->getQuantity());
    }

    public function testCheckProductHaveEnoughQuantity()
    {
        $this->prepareShoppingList(LoadInventoryLevels::PRECISION_BOTTLE_QTY_99 + 1);

        $crawler = $this->navigateThroughCheckout();
        $this->assertContains(self::PRODUCT_ERROR_TEXT, $crawler->html());
    }

    public function testProductDecrementWithBackorder()
    {
        $shoppingList = $this->prepareShoppingList(LoadInventoryLevels::PRECISION_BOTTLE_QTY_99 + 1, '1', '1');

        $crawler = $this->navigateThroughCheckout();
        $form = $crawler->selectButton('Submit Order')->form();
        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $form->getPhpValues(),
            $form->getPhpFiles(),
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertArrayHasKey('successUrl', $data['responseData']);
        $this->assertNotEmpty($data['responseData']['successUrl']);
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $data['responseData']['returnUrl']);
        $this->assertContains(self::FINISH_SIGN, $crawler->html());
        $this->assertNull($this->doctrineHelper->getEntityRepositoryForClass(ShoppingList::class)->find($shoppingList));
        $inventoryLevel = $this->getInventoryLevel($shoppingList);
        $this->assertLessThan(0, $inventoryLevel->getQuantity());
    }

    public function testDecrementWithInventoryThreshold()
    {
        $shoppingList = $this->prepareShoppingList(LoadInventoryLevels::PRECISION_BOTTLE_QTY_99);
        $this->initProductInventoryThreshold($shoppingList->getLineItems()[0]->getProduct());

        $crawler = $this->navigateThroughCheckout();
        $this->assertContains(self::PRODUCT_ERROR_TEXT, $crawler->html());
    }

    public function testCreateOrderWithInventoryThreshold()
    {
        $inventoryThreshold = 5;
        $shoppingList = $this->prepareShoppingList(LoadInventoryLevels::PRECISION_BOTTLE_QTY_99 - $inventoryThreshold);
        $this->initProductInventoryThreshold($shoppingList->getLineItems()[0]->getProduct(), $inventoryThreshold);

        $crawler = $this->navigateThroughCheckout();
        $form = $crawler->selectButton('Submit Order')->form();
        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $form->getPhpValues(),
            $form->getPhpFiles(),
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertArrayHasKey('successUrl', $data['responseData']);
        $this->assertNotEmpty($data['responseData']['successUrl']);
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $data['responseData']['returnUrl']);
        $this->assertContains(self::FINISH_SIGN, $crawler->html());
        $this->assertNull($this->doctrineHelper->getEntityRepositoryForClass(ShoppingList::class)->find($shoppingList));
        $inventoryLevel = $this->getInventoryLevel($shoppingList);
        $this->assertEquals($inventoryThreshold, $inventoryLevel->getQuantity());
    }

    /**
     * @param Crawler $crawler
     * @param string $textToCheck
     */
    protected function assertCurrentStep(Crawler $crawler, $textToCheck)
    {
        $stepLabel = $crawler->filterXPath(self::CHECKOUT_STEP_LABEL)->text();
        $this->assertContains($textToCheck, $stepLabel);
    }

    /**
     * @param Product $product
     */
    protected function initProductDecrementFallback(Product $product, $decrementQuantity = '1', $allowBackorder = '0')
    {
        $decrementFallback = new EntityFieldFallbackValue();
        $backorderFallback = new EntityFieldFallbackValue();
        $decrementFallback->setScalarValue($decrementQuantity);
        $backorderFallback->setScalarValue($allowBackorder);
        $this->emFallback->persist($decrementFallback);
        $this->emFallback->persist($backorderFallback);
        $product->setDecrementQuantity($decrementFallback);
        $product->setBackOrder($backorderFallback);
        $this->doctrineHelper->getEntityManager(Product::class)->flush();
        $this->emFallback->flush();
    }

    /**
     * @param Product $product
     * @param string $inventoryThreshold
     */
    protected function initProductInventoryThreshold(Product $product, $inventoryThreshold = '5')
    {
        $inventoryThresholdFallback = new EntityFieldFallbackValue();
        $inventoryThresholdFallback->setScalarValue($inventoryThreshold);
        $this->emFallback->persist($inventoryThresholdFallback);
        $product->setInventoryThreshold($inventoryThresholdFallback);
        $this->doctrineHelper->getEntityManager(Product::class)->flush();
        $this->emFallback->flush();
    }

    /**
     * @param Crawler $crawler
     * @return Crawler
     */
    protected function goToNextStep(Crawler $crawler)
    {
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);

        return $crawler;
    }

    /**
     * @param Crawler $crawler
     * @return Crawler
     */
    protected function submitPaymentTransitionForm(Crawler $crawler)
    {
        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $values[self::ORO_WORKFLOW_TRANSITION]['payment_method'] = 'payment_term';
        $values['_widgetContainer'] = 'ajax';
        $values['_wid'] = 'ajax_checkout';

        return $this->client->request(
            'POST',
            $form->getUri(),
            $values,
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }

    /**
     * @param int $quantityToOrder
     * @param string $decrementQuantity
     * @param string $allowBackorder
     * @return ShoppingList
     */
    protected function prepareShoppingList($quantityToOrder = 5, $decrementQuantity = '1', $allowBackorder = '0')
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $lineItem = $shoppingList->getLineItems()[0];
        $product = $lineItem->getProduct();
        $this->initProductDecrementFallback($product, $decrementQuantity, $allowBackorder);
        $lineItem->setQuantity($quantityToOrder);
        $this->doctrineHelper->getEntityManager(LineItem::class)->flush();

        $this->startCheckout($shoppingList);

        return $shoppingList;
    }

    /**
     * @return null|Crawler
     */
    protected function navigateThroughCheckout()
    {
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $this->assertCurrentStep($crawler, 'Billing Information');
        $crawler = $this->goToNextStep($crawler);
        $this->assertCurrentStep($crawler, 'Shipping Information');
        $crawler = $this->goToNextStep($crawler);
        $this->assertCurrentStep($crawler, 'Shipping Method');
        $crawler = $this->goToNextStep($crawler);
        $this->assertCurrentStep($crawler, 'Payment');
        $crawler = $this->submitPaymentTransitionForm($crawler);
        $this->assertCurrentStep($crawler, 'Order Review');

        return $crawler;
    }

    /**
     * @param ShoppingList $shoppingList
     * @return InventoryLevel
     */
    protected function getInventoryLevel(ShoppingList $shoppingList)
    {
        $lineItem = $shoppingList->getLineItems()[0];
        $productUnitPrecision = $this->doctrineHelper
            ->getEntityRepositoryForClass(ProductUnitPrecision::class)
            ->findoneBy(['product' => $lineItem->getProduct(), 'unit' => $lineItem->getUnit()]);
        $inventoryLevel = $this->doctrineHelper
            ->getEntityRepositoryForClass(InventoryLevel::class)
            ->findOneBy([
                'product' => $lineItem->getProduct(),
                'productUnitPrecision' => $productUnitPrecision
            ]);

        return $inventoryLevel;
    }
}
