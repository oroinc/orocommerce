<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Controller\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend\CheckoutControllerTestCase;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolationPerTest
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

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function setUp()
    {
        parent::setUp();
        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $this->emFallback = $this->doctrineHelper->getEntityManager(
            EntityFieldFallbackValue::class
        );
        $this->translator = $this->getContainer()->get('translator');
        $this->configManager = $this->getContainer()->get('oro_config.manager');
    }

    public function testRequestForQuoteCheckoutIsNotAffectedByQuantityLimits()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $lineItem = $shoppingList->getLineItems()[0];
        $product = $lineItem->getProduct();
        $this->initProductLimitAsSystemFallback($product);
        $lineItem->setQuantity(3);
        $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManager(LineItem::class)->flush();

        $this->startCheckout($shoppingList);
        $sourceRepository = $this->doctrineHelper->getEntityRepository('OroCheckoutBundle:CheckoutSource');
        /** @var CheckoutSource $checkoutSource */
        $checkoutSource = $sourceRepository->findOneBy(['shoppingList' => $shoppingList]);
        $quoteDemand = new QuoteDemand();
        $emQuoteDemand = $this->doctrineHelper->getEntityManager(QuoteDemand::class);
        $emQuoteDemand->persist($quoteDemand);
        $emQuoteDemand->flush();
        $checkoutSource->setQuoteDemand($quoteDemand);

        $this->doctrineHelper->getEntityManager(CheckoutSource::class)->flush();

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
        $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManager(LineItem::class)->flush();

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

    protected function validateStep(Crawler $crawler, Product $product)
    {
        $this->verifyQuantityError($crawler, $product, false, false, false);
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
        $this->verifyQuantityError($crawler, $product, false, true, true);

        return $crawler;
    }

    /**
     * @param Crawler $crawler
     * @param Product $product
     * @param bool $minError
     * @param bool $maxError
     * @param bool $shouldBeFirstStep
     */
    protected function verifyQuantityError(
        Crawler $crawler,
        Product $product,
        $minError = true,
        $maxError = false,
        $shouldBeFirstStep = false
    ) {
        $continueButton = $crawler->filterXPath(self::CONTINUE_BUTTON);
        $buttonClasses = $continueButton->attr('class');

        if (!$minError && !$maxError) {
            $this->assertContains('btn-orange', $buttonClasses);
        } else {
            $this->assertNotContains('btn-orange', $buttonClasses);
        }

        $this->assertCurrentStep($crawler, $shouldBeFirstStep);

        $content = $this->client->getResponse()->getContent();
        $minMessage = $this->getErrorMessage($product, true);
        if ($minError) {
            $this->assertContains($minMessage, $content);
        } else {
            $this->assertNotContains($minMessage, $content);
        }

        $maxMessage = $this->getErrorMessage($product, false);
        if ($maxError) {
            $this->assertContains($maxMessage, $content);
        } else {
            $this->assertNotContains($maxMessage, $content);
        }
    }

    /**
     * @param Product $product
     * @param bool $isMinMessage
     * @return string
     */
    protected function getErrorMessage(Product $product, $isMinMessage = true)
    {
        if ($isMinMessage) {
            $message = 'oro.inventory.product.error.quantity_below_min_limit';
            $errorLimit = $this->getMinimumSystemQuantityLimit();
        } else {
            $message = 'oro.inventory.product.error.quantity_over_max_limit';
            $errorLimit = $this->getMaximumSystemQuantityLimit();
        }

        return $this->translator->trans(
            $message,
            ['%limit%' => $errorLimit, '%sku%' => $product->getSku(), '%product_name%' => $product->getName()]
        );
    }

    /**
     * @param int $minLimit
     * @param int $maxLimit
     */
    protected function updateSystemQuantityLimits($minLimit, $maxLimit)
    {
        $this->configManager->set(Configuration::getMaximumQuantityToOrderFullConfigurationName(), $maxLimit);
        $this->configManager->set(Configuration::getMinimumQuantityToOrderFullConfigurationName(), $minLimit);
        $this->configManager->flush();
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
        $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManager(Product::class)->flush();
        $this->emFallback->flush();
    }

    /**
     * @return int
     */
    protected function getMinimumSystemQuantityLimit()
    {
        return $this->configManager->get(Configuration::getMinimumQuantityToOrderFullConfigurationName());
    }

    /**
     * @return int
     */
    protected function getMaximumSystemQuantityLimit()
    {
        return $this->configManager->get(Configuration::getMaximumQuantityToOrderFullConfigurationName());
    }
}
