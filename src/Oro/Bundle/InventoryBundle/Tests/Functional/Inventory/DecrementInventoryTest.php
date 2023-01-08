<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Inventory;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend\CheckoutControllerTestCase;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\Traits\EnabledPaymentMethodIdentifierTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @dbIsolationPerTest
 * @group CommunityEdition
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class DecrementInventoryTest extends CheckoutControllerTestCase
{
    use EnabledPaymentMethodIdentifierTrait;
    use ConfigManagerAwareTestTrait;

    private const CHECKOUT_STEP_LABEL = "//h2[contains(@class, 'checkout__title')]";
    private const PRODUCT_ERROR_TEXT = 'There is not enough quantity for this product';

    private EntityManagerInterface $emFallback;
    private DoctrineHelper $doctrineHelper;
    private int $precisionBottleQuantity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctrineHelper = self::getContainer()->get('oro_entity.doctrine_helper');
        $this->emFallback = $this->doctrineHelper->getEntityManager(EntityFieldFallbackValue::class);
        $this->precisionBottleQuantity = self::processTemplateData(
            '@inventory_level.product_unit_precision.product-1.bottle->quantity'
        );
        $configManager = self::getConfigManager();
        $configManager->set('oro_inventory.manage_inventory', true);
        $configManager->flush();
    }

    public function testOrderWithoutDecrement()
    {
        $shoppingList = $this->prepareShoppingList($this->precisionBottleQuantity, '0');

        $crawler = $this->navigateThroughCheckout();
        $form = $crawler->selectButton('Submit Order')->form();
        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $form->getPhpValues(),
            $form->getPhpFiles(),
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $data = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertArrayHasKey('successUrl', $data['responseData']);
        self::assertNotEmpty($data['responseData']['successUrl']);
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $data['responseData']['returnUrl']);
        self::assertStringContainsString(self::FINISH_SIGN, $crawler->html());
        self::assertNull(
            $this->doctrineHelper->getEntityRepositoryForClass(ShoppingList::class)->find($shoppingList)
        );
        $inventoryLevel = $this->getInventoryLevel($shoppingList);
        $initialQuantity = $this->precisionBottleQuantity;
        $inventoryLevel = $this->doctrineHelper
            ->getEntityRepositoryForClass(InventoryLevel::class)
            ->find($inventoryLevel);
        self::assertEquals($initialQuantity, $inventoryLevel->getQuantity());
    }

    public function testCheckProductHaveEnoughQuantity()
    {
        $this->prepareShoppingList($this->precisionBottleQuantity + 1);

        $crawler = $this->navigateThroughCheckout();
        self::assertStringContainsString(self::PRODUCT_ERROR_TEXT, $crawler->html());
    }

    public function testProductDecrementWithBackorder()
    {
        $shoppingList = $this->prepareShoppingList($this->precisionBottleQuantity + 1, '1', '1');

        $crawler = $this->navigateThroughCheckout();
        $form = $crawler->selectButton('Submit Order')->form();
        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $form->getPhpValues(),
            $form->getPhpFiles(),
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $data = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertArrayHasKey('successUrl', $data['responseData']);
        self::assertNotEmpty($data['responseData']['successUrl']);
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $data['responseData']['returnUrl']);
        self::assertStringContainsString(self::FINISH_SIGN, $crawler->html());
        self::assertNull(
            $this->doctrineHelper->getEntityRepositoryForClass(ShoppingList::class)->find($shoppingList)
        );
        $inventoryLevel = $this->getInventoryLevel($shoppingList);
        self::assertLessThan(0, $inventoryLevel->getQuantity());
    }

    public function testDecrementWithInventoryThreshold()
    {
        $shoppingList = $this->prepareShoppingList($this->precisionBottleQuantity);
        $this->initProductInventoryThreshold($shoppingList->getLineItems()[0]->getProduct());

        $crawler = $this->navigateThroughCheckout();
        self::assertStringContainsString(self::PRODUCT_ERROR_TEXT, $crawler->html());
    }

    public function testCreateOrderWithInventoryThreshold()
    {
        $inventoryThreshold = 5;
        $shoppingList = $this->prepareShoppingList($this->precisionBottleQuantity - $inventoryThreshold);
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
        $data = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertArrayHasKey('successUrl', $data['responseData']);
        self::assertNotEmpty($data['responseData']['successUrl']);
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $data['responseData']['returnUrl']);
        self::assertStringContainsString(self::FINISH_SIGN, $crawler->html());
        self::assertNull(
            $this->doctrineHelper->getEntityRepositoryForClass(ShoppingList::class)->find($shoppingList)
        );
        $inventoryLevel = $this->getInventoryLevel($shoppingList);
        self::assertEquals($inventoryThreshold, $inventoryLevel->getQuantity());
    }

    private function assertCurrentStep(Crawler $crawler, string $textToCheck): void
    {
        $stepLabel = $crawler->filterXPath(self::CHECKOUT_STEP_LABEL)->text();
        self::assertStringContainsString($textToCheck, $stepLabel);
    }

    private function initProductDecrementFallback(
        Product $product,
        string $decrementQuantity = '1',
        string $allowBackOrder = '0'
    ): void {
        $decrementFallback = new EntityFieldFallbackValue();
        $backOrderFallback = new EntityFieldFallbackValue();
        $decrementFallback->setScalarValue($decrementQuantity);
        $backOrderFallback->setScalarValue($allowBackOrder);
        $this->emFallback->persist($decrementFallback);
        $this->emFallback->persist($backOrderFallback);
        $product->setDecrementQuantity($decrementFallback);
        $product->setBackOrder($backOrderFallback);
        $this->doctrineHelper->getEntityManager(Product::class)->flush();
        $this->emFallback->flush();
    }

    private function initProductInventoryThreshold(Product $product, string $inventoryThreshold = '5'): void
    {
        $inventoryThresholdFallback = new EntityFieldFallbackValue();
        $inventoryThresholdFallback->setScalarValue($inventoryThreshold);
        $this->emFallback->persist($inventoryThresholdFallback);
        $product->setInventoryThreshold($inventoryThresholdFallback);
        $this->doctrineHelper->getEntityManager(Product::class)->flush();
        $this->emFallback->flush();
    }

    private function goToNextStep(Crawler $crawler): Crawler
    {
        return $this->client->submit($this->getTransitionForm($crawler));
    }

    private function submitPaymentTransitionForm(Crawler $crawler): Crawler
    {
        $form = $this->getTransitionForm($crawler);
        $values = $this->explodeArrayPaths($form->getValues());
        $values[self::ORO_WORKFLOW_TRANSITION]['payment_method'] =
            $this->getPaymentMethodIdentifier($this->getContainer());
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

    private function prepareShoppingList(
        int $quantityToOrder = 5,
        string $decrementQuantity = '1',
        string $allowBackorder = '0'
    ): ShoppingList {
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

    private function navigateThroughCheckout(): Crawler
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

    private function getInventoryLevel(ShoppingList $shoppingList): InventoryLevel
    {
        $lineItem = $shoppingList->getLineItems()[0];
        $productUnitPrecision = $this->doctrineHelper
            ->getEntityRepositoryForClass(ProductUnitPrecision::class)
            ->findOneBy(['product' => $lineItem->getProduct(), 'unit' => $lineItem->getUnit()]);

        return $this->doctrineHelper
            ->getEntityRepositoryForClass(InventoryLevel::class)
            ->findOneBy([
                'product' => $lineItem->getProduct(),
                'productUnitPrecision' => $productUnitPrecision
            ]);
    }
}
