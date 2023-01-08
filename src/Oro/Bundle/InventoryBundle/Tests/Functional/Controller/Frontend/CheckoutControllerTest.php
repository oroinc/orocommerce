<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Controller\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend\CheckoutControllerTestCase;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @dbIsolationPerTest
 * @group CommunityEdition
 */
class CheckoutControllerTest extends CheckoutControllerTestCase
{
    use ConfigManagerAwareTestTrait;

    private const CONTINUE_BUTTON = "//button[contains(text(), 'Continue')]";
    private const CHECKOUT_STEP_LABEL2 = "//h2[text()[contains(.,'%s')]]";
    private const CHECKOUT_STEP_LABEL = "//h2[contains(@class, 'checkout__title')]";
    private const MIN_ERROR_MESSAGE = 'oro.inventory.product.error.quantity_below_min_limit';
    private const MAX_ERROR_MESSAGE = 'oro.inventory.product.error.quantity_over_max_limit';

    private EntityManagerInterface $emFallback;
    private TranslatorInterface $translator;
    private ConfigManager $configManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emFallback = $this->registry->getManagerForClass(EntityFieldFallbackValue::class);
        $this->translator = $this->getContainer()->get('translator');
        $this->configManager = self::getConfigManager();
    }

    protected function tearDown(): void
    {
        $this->updateSystemQuantityLimits(null, null);
    }

    public function testRequestForQuoteCheckoutIsNotAffectedByQuantityLimits()
    {
        /** @var QuoteDemand $quoteDemand */
        $quoteDemand = $this->getReference(LoadQuoteProductDemandData::QUOTE_DEMAND_3);
        $lineItem = $quoteDemand->getLineItems()->first();
        $lineItem->setQuantity(3);
        $this->registry->getManagerForClass(LineItem::class)->flush();
        $this->startCheckoutFromQuoteDemand($quoteDemand);
        $crawler = $this->client->request('GET', self::$checkoutUrl);

        $this->updateSystemQuantityLimits(1, 2);
        $crawler = $this->goToNextStep($crawler);
        $this->assertCurrentStep($crawler);
        $crawler = $this->goToNextStep($crawler);
        $this->assertCurrentStep($crawler);
        $crawler = $this->goToNextStep($crawler);
        $this->assertCurrentStep($crawler);
        $crawler = $this->goToNextStep($crawler);
        $this->assertCurrentStep($crawler);
        $crawler = $this->goToNextStep($crawler);
        $this->assertCurrentStep($crawler);
    }

    public function testCheckoutRestartsOnQuantityErrorOnEachStep()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $lineItem = $shoppingList->getLineItems()[0];
        $product = $lineItem->getProduct();
        $this->initProductLimitAsSystemFallback($product);
        $lineItem->setQuantity(3);
        $this->registry->getManagerForClass(LineItem::class)->flush();

        $this->startCheckout($shoppingList);
        $crawler = $this->client->request('GET', self::$checkoutUrl);

        // set limits outside of current quantity and verify errors
        $this->setInvalidLimitAndVerifyCheckoutRestarted($crawler, $product);

        // set limits inside of current quantity and verify step1 with no errors
        $this->updateSystemQuantityLimits(2, 5);
        $crawler = $this->client->request('GET', self::$checkoutUrl);
        $this->verifyQuantityError($crawler, $product, false, false, true);
        // go to step 2
        $crawler = $this->goToNextStep($crawler);
        $this->validateStep($crawler, $product);

        // being on step 2 set limits outside of quantity and verify errors and checkout reset
        $crawler = $this->setInvalidLimitAndVerifyCheckoutRestarted($crawler, $product);

        // set correct limits and continue to step 2
        $this->updateSystemQuantityLimits(2, 5);
        $crawler = $this->goToNextStep($crawler);
        $this->validateStep($crawler, $product);
        // continue to step 3
        $crawler = $this->goToNextStep($crawler);
        $this->validateStep($crawler, $product);

        // set invalid limits and verify checkout restart with error
        $crawler = $this->setInvalidLimitAndVerifyCheckoutRestarted($crawler, $product);

        // set correct limits and continue to step 2
        $this->updateSystemQuantityLimits(2, 5);
        $crawler = $this->goToNextStep($crawler);
        $this->validateStep($crawler, $product);
        // continue to step 3
        $crawler = $this->goToNextStep($crawler);
        $this->validateStep($crawler, $product);
        // continue to step 4
        $crawler = $this->goToNextStep($crawler);
        $this->validateStep($crawler, $product);

        // set invalid limits and verify checkout restart with error
        $crawler = $this->setInvalidLimitAndVerifyCheckoutRestarted($crawler, $product);

        // set correct limits and continue to step 2
        $this->updateSystemQuantityLimits(2, 5);
        $crawler = $this->goToNextStep($crawler);
        $this->validateStep($crawler, $product);
        // continue to step 3
        $crawler = $this->goToNextStep($crawler);
        $this->validateStep($crawler, $product);
        // continue to step 4
        $crawler = $this->goToNextStep($crawler);
        $this->validateStep($crawler, $product);
        // continue to step 5
        $crawler = $this->goToNextStep($crawler);
        $this->validateStep($crawler, $product);

        // set invalid limits and verify checkout restart with error
        $this->setInvalidLimitAndVerifyCheckoutRestarted($crawler, $product);
    }

    private function assertCurrentStep(Crawler $crawler, bool $shouldBeFirstStep = false): void
    {
        $stepLabel = $crawler->filterXPath(self::CHECKOUT_STEP_LABEL)->text();
        if ($shouldBeFirstStep) {
            self::assertStringContainsString('Billing Information', $stepLabel);
        } else {
            self::assertStringNotContainsString('Billing Information', $stepLabel);
        }
    }

    private function goToNextStep(Crawler $crawler): Crawler
    {
        return $this->client->submit($this->getTransitionForm($crawler));
    }

    private function validateStep(Crawler $crawler, Product $product): void
    {
        $this->verifyQuantityError($crawler, $product, false);
    }

    private function setInvalidLimitAndVerifyCheckoutRestarted(Crawler $crawler, Product $product): Crawler
    {
        $this->updateSystemQuantityLimits(1, 2);
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        $this->verifyQuantityError($crawler, $product, false, true, true, 2);

        return $crawler;
    }

    private function verifyQuantityError(
        Crawler $crawler,
        Product $product,
        bool $minError = true,
        bool $maxError = false,
        bool $shouldBeFirstStep = false,
        int $quantityLimit = null
    ): void {
        $continueButton = $crawler->filterXPath(self::CONTINUE_BUTTON);
        $buttonClasses = $continueButton->attr('class');

        if ($minError || $maxError) {
            self::assertStringContainsString('btn--disabled', $buttonClasses);
        } else {
            self::assertStringNotContainsString('btn--disabled', $buttonClasses);
        }

        $this->assertCurrentStep($crawler, $shouldBeFirstStep);

        $content = $this->client->getResponse()->getContent();
        $minMessage = $this->getErrorMessage($product, $quantityLimit);
        if ($minError) {
            self::assertStringContainsString($minMessage, $content);
        } else {
            self::assertStringNotContainsString($minMessage, $content);
        }

        $maxMessage = $this->getErrorMessage($product, $quantityLimit, false);
        if ($maxError) {
            self::assertStringContainsString($maxMessage, $content);
        } else {
            self::assertStringNotContainsString($maxMessage, $content);
        }
    }

    private function getErrorMessage(Product $product, ?int $quantityLimit, bool $isMinMessage = true): string
    {
        if ($isMinMessage) {
            $message = 'oro.inventory.product.error.quantity_below_min_limit';
        } else {
            $message = 'oro.inventory.product.error.quantity_over_max_limit';
        }

        return $this->translator->trans(
            $message,
            ['%limit%' => $quantityLimit, '%sku%' => $product->getSku(), '%product_name%' => $product->getName()]
        );
    }

    private function updateSystemQuantityLimits(?int $minLimit, ?int $maxLimit): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_inventory.minimum_quantity_to_order', $minLimit);
        $configManager->set('oro_inventory.maximum_quantity_to_order', $maxLimit);
        $configManager->flush();
    }

    private function initProductLimitAsSystemFallback(Product $product): void
    {
        $entityFallback = new EntityFieldFallbackValue();
        $entityFallback->setFallback(SystemConfigFallbackProvider::FALLBACK_ID);
        $entityFallback2 = new EntityFieldFallbackValue();
        $entityFallback2->setFallback(SystemConfigFallbackProvider::FALLBACK_ID);
        $this->emFallback->persist($entityFallback);
        $this->emFallback->persist($entityFallback2);
        $product->setMinimumQuantityToOrder($entityFallback);
        $product->setMaximumQuantityToOrder($entityFallback2);
        $this->registry->getManagerForClass(Product::class)->flush();
        $this->emFallback->flush();
    }

    private function startCheckoutFromQuoteDemand(QuoteDemand $quoteDemand): void
    {
        $this->startCheckoutByData($this->getCheckoutFromQuoteDemandData($quoteDemand));
    }

    private function getCheckoutFromQuoteDemandData(QuoteDemand $quoteDemand): array
    {
        return [
            'context' => new ActionData([]),
            'options' => [
                'parameters_mapping' => [
                    'sourceCriteria' => [
                        'quoteDemand' => $quoteDemand,
                    ],
                ],
                'action_group' => 'start_checkout',
                'results' => [
                    'redirectUrl' => new PropertyPath('redirectUrl'),
                ]
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getInventoryFixtures(): array
    {
        return [
            LoadQuoteProductDemandData::class,
        ];
    }
}
