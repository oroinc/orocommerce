<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Controller\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend\CheckoutControllerTestCase;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
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
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @dbIsolationPerTest
 * @group CommunityEdition
 */
class CheckoutControllerTest extends CheckoutControllerTestCase
{
    const CONTINUE_BUTTON = "//button[contains(text(), 'Continue')]";
    const CHECKOUT_STEP_LABEL2 = "//h2[text()[contains(.,'%s')]]";
    const CHECKOUT_STEP_LABEL = "//h2[contains(@class, 'checkout__title')]";
    const MIN_ERROR_MESSAGE = 'oro.inventory.product.error.quantity_below_min_limit';
    const MAX_ERROR_MESSAGE = 'oro.inventory.product.error.quantity_over_max_limit';

    /**
     * @var EntityManagerInterface
     */
    protected $emFallback;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function setUp()
    {
        parent::setUp();
        $this->emFallback = $this->registry->getManagerForClass(
            EntityFieldFallbackValue::class
        );
        $this->translator = $this->getContainer()->get('translator');
        $this->configManager = $this->getContainer()->get('oro_config.manager');
    }

    protected function tearDown()
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

    /**
     * @param Crawler $crawler
     * @param bool $shouldBeFirstStep
     */
    protected function assertCurrentStep(Crawler $crawler, $shouldBeFirstStep = false)
    {
        $stepLabel = $crawler->filterXPath(self::CHECKOUT_STEP_LABEL)->text();
        if ($shouldBeFirstStep) {
            $this->assertContains('Billing Information', $stepLabel);
        } else {
            $this->assertNotContains('Billing Information', $stepLabel);
        }
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
     * @param Product $product
     */
    protected function validateStep(Crawler $crawler, Product $product)
    {
        $this->verifyQuantityError($crawler, $product, false, false, false, null);
    }

    /**
     * @param Crawler $crawler
     * @param Product $product
     * @return Crawler
     */
    protected function setInvalidLimitAndVerifyCheckoutRestarted(Crawler $crawler, Product $product)
    {
        $this->updateSystemQuantityLimits(1, 2);
        $form = $this->getTransitionForm($crawler);
        $crawler = $this->client->submit($form);
        $this->verifyQuantityError($crawler, $product, false, true, true, 2);

        return $crawler;
    }

    /**
     * @param Crawler $crawler
     * @param Product $product
     * @param bool $minError
     * @param bool $maxError
     * @param bool $shouldBeFirstStep
     * @param null|int $quantityLimit
     */
    protected function verifyQuantityError(
        Crawler $crawler,
        Product $product,
        $minError = true,
        $maxError = false,
        $shouldBeFirstStep = false,
        $quantityLimit = null
    ) {
        $continueButton = $crawler->filterXPath(self::CONTINUE_BUTTON);
        $buttonClasses = $continueButton->attr('class');

        if ($minError || $maxError) {
            $this->assertContains('btn--disabled', $buttonClasses);
        } else {
            $this->assertNotContains('btn--disabled', $buttonClasses);
        }

        $this->assertCurrentStep($crawler, $shouldBeFirstStep);

        $content = $this->client->getResponse()->getContent();
        $minMessage = $this->getErrorMessage($product, $quantityLimit, true);
        if ($minError) {
            $this->assertContains($minMessage, $content);
        } else {
            $this->assertNotContains($minMessage, $content);
        }

        $maxMessage = $this->getErrorMessage($product, $quantityLimit, false);
        if ($maxError) {
            $this->assertContains($maxMessage, $content);
        } else {
            $this->assertNotContains($maxMessage, $content);
        }
    }

    /**
     * @param Product $product
     * @param int $quantityLimit
     * @param bool $isMinMessage
     * @return string
     */
    protected function getErrorMessage(Product $product, $quantityLimit, $isMinMessage = true)
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

    /**
     * @param int $minLimit
     * @param int $maxLimit
     */
    protected function updateSystemQuantityLimits($minLimit, $maxLimit)
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set('oro_inventory.minimum_quantity_to_order', $minLimit);
        $configManager->set('oro_inventory.maximum_quantity_to_order', $maxLimit);
        $configManager->flush();
    }

    /**
     * @param Product $product
     */
    protected function initProductLimitAsSystemFallback(Product $product)
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

    /**
     * @param QuoteDemand $quoteDemand
     */
    protected function startCheckoutFromQuoteDemand(QuoteDemand $quoteDemand)
    {
        $this->startCheckoutByData($this->getCheckoutFromQuoteDemandData($quoteDemand));
    }


    /**
     * @param QuoteDemand $quoteDemand
     * @return array
     */
    protected function getCheckoutFromQuoteDemandData(QuoteDemand $quoteDemand)
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
     * @return array
     */
    protected function getInventoryFixtures()
    {
        return [
            LoadQuoteProductDemandData::class,
        ];
    }
}
