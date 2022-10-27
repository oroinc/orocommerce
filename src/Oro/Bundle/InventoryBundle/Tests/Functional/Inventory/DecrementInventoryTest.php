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
    use EnabledPaymentMethodIdentifierTrait {
        getReference as protected;
    }
    use ConfigManagerAwareTestTrait;

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

    /**
     * @var int
     */
    protected $precisionBottleQuantity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctrineHelper = static::getContainer()->get('oro_entity.doctrine_helper');
        $this->emFallback = $this->doctrineHelper->getEntityManager(EntityFieldFallbackValue::class);
        $this->precisionBottleQuantity = self::processTemplateData(
            '@inventory_level.product_unit_precision.product-1.bottle->quantity'
        );
        $configManager = self::getConfigManager('global');
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
        $data = static::getJsonResponseContent($this->client->getResponse(), 200);
        static::assertArrayHasKey('successUrl', $data['responseData']);
        static::assertNotEmpty($data['responseData']['successUrl']);
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $data['responseData']['returnUrl']);
        static::assertStringContainsString(self::FINISH_SIGN, $crawler->html());
        static::assertNull(
            $this->doctrineHelper->getEntityRepositoryForClass(ShoppingList::class)->find($shoppingList)
        );
        $inventoryLevel = $this->getInventoryLevel($shoppingList);
        $initialQuantity = $this->precisionBottleQuantity;
        $inventoryLevel = $this->doctrineHelper
            ->getEntityRepositoryForClass(InventoryLevel::class)
            ->find($inventoryLevel);
        static::assertEquals($initialQuantity, $inventoryLevel->getQuantity());
    }

    public function testCheckProductHaveEnoughQuantity()
    {
        $this->prepareShoppingList($this->precisionBottleQuantity + 1);

        $crawler = $this->navigateThroughCheckout();
        static::assertStringContainsString(self::PRODUCT_ERROR_TEXT, $crawler->html());
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
        $data = static::getJsonResponseContent($this->client->getResponse(), 200);
        static::assertArrayHasKey('successUrl', $data['responseData']);
        static::assertNotEmpty($data['responseData']['successUrl']);
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $data['responseData']['returnUrl']);
        static::assertStringContainsString(self::FINISH_SIGN, $crawler->html());
        static::assertNull(
            $this->doctrineHelper->getEntityRepositoryForClass(ShoppingList::class)->find($shoppingList)
        );
        $inventoryLevel = $this->getInventoryLevel($shoppingList);
        static::assertLessThan(0, $inventoryLevel->getQuantity());
    }

    public function testDecrementWithInventoryThreshold()
    {
        $shoppingList = $this->prepareShoppingList($this->precisionBottleQuantity);
        $this->initProductInventoryThreshold($shoppingList->getLineItems()[0]->getProduct());

        $crawler = $this->navigateThroughCheckout();
        static::assertStringContainsString(self::PRODUCT_ERROR_TEXT, $crawler->html());
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
        $data = static::getJsonResponseContent($this->client->getResponse(), 200);
        static::assertArrayHasKey('successUrl', $data['responseData']);
        static::assertNotEmpty($data['responseData']['successUrl']);
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $data['responseData']['returnUrl']);
        static::assertStringContainsString(self::FINISH_SIGN, $crawler->html());
        static::assertNull(
            $this->doctrineHelper->getEntityRepositoryForClass(ShoppingList::class)->find($shoppingList)
        );
        $inventoryLevel = $this->getInventoryLevel($shoppingList);
        static::assertEquals($inventoryThreshold, $inventoryLevel->getQuantity());
    }

    /**
     * @param Crawler $crawler
     * @param string  $textToCheck
     */
    protected function assertCurrentStep(Crawler $crawler, $textToCheck)
    {
        $stepLabel = $crawler->filterXPath(self::CHECKOUT_STEP_LABEL)->text();
        static::assertStringContainsString($textToCheck, $stepLabel);
    }

    /**
     * @param Product $product
     * @param string  $decrementQuantity
     * @param string  $allowBackOrder
     */
    protected function initProductDecrementFallback(Product $product, $decrementQuantity = '1', $allowBackOrder = '0')
    {
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

    /**
     * @param Product $product
     * @param string  $inventoryThreshold
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
     *
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
     *
     * @return Crawler
     */
    protected function submitPaymentTransitionForm(Crawler $crawler)
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

    /**
     * @param int    $quantityToOrder
     * @param string $decrementQuantity
     * @param string $allowBackorder
     *
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
        static::assertCurrentStep($crawler, 'Billing Information');
        $crawler = $this->goToNextStep($crawler);
        static::assertCurrentStep($crawler, 'Shipping Information');
        $crawler = $this->goToNextStep($crawler);
        static::assertCurrentStep($crawler, 'Shipping Method');
        $crawler = $this->goToNextStep($crawler);
        static::assertCurrentStep($crawler, 'Payment');
        $crawler = $this->submitPaymentTransitionForm($crawler);
        static::assertCurrentStep($crawler, 'Order Review');

        return $crawler;
    }

    /**
     * @param ShoppingList $shoppingList
     *
     * @return InventoryLevel
     */
    protected function getInventoryLevel(ShoppingList $shoppingList)
    {
        $lineItem = $shoppingList->getLineItems()[0];
        $productUnitPrecision = $this->doctrineHelper
            ->getEntityRepositoryForClass(ProductUnitPrecision::class)
            ->findOneBy(['product' => $lineItem->getProduct(), 'unit' => $lineItem->getUnit()]);
        $inventoryLevel = $this->doctrineHelper
            ->getEntityRepositoryForClass(InventoryLevel::class)
            ->findOneBy([
                'product' => $lineItem->getProduct(),
                'productUnitPrecision' => $productUnitPrecision
            ]);

        return $inventoryLevel;
    }
}
