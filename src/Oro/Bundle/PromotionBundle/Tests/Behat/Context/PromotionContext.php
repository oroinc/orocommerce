<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\ConfigBundle\Tests\Behat\Element\SidebarConfigMenu;
use Oro\Bundle\ConfigBundle\Tests\Behat\Element\SystemConfigForm;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\PromotionBundle\CouponGeneration\Code\CodeGenerator;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;
use Oro\Bundle\PromotionBundle\Tests\Behat\Element\PromotionBackendOrder;
use Oro\Bundle\PromotionBundle\Tests\Behat\Element\PromotionBackendOrderLineItem;
use Oro\Bundle\PromotionBundle\Tests\Behat\Element\PromotionCheckoutStep;
use Oro\Bundle\PromotionBundle\Tests\Behat\Element\PromotionOrder;
use Oro\Bundle\PromotionBundle\Tests\Behat\Element\PromotionOrderForm;
use Oro\Bundle\PromotionBundle\Tests\Behat\Element\PromotionShoppingList;
use Oro\Bundle\PromotionBundle\Tests\Behat\Element\PromotionShoppingListLineItem;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Context\ShoppingListContext;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\LineItemsAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PromotionContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @var OroMainContext
     */
    private $oroMainContext;

    /**
     * @var ShoppingListContext
     */
    private $shoppingListContext;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
        $this->shoppingListContext = $environment->getContext(ShoppingListContext::class);
    }

    /**
     * @Then /^(?:|I )see next line item discounts for shopping list "(?P<shoppingListLabel>[^"]+)":$/
     *
     * @param string $shoppingListLabel
     * @param TableNode $table
     */
    public function assertShoppingListLineItemDiscount($shoppingListLabel, TableNode $table)
    {
        /** @var PromotionShoppingList $shoppingList */
        $shoppingList = $this->createElement('PromotionShoppingList');

        $shoppingList->assertTitle($shoppingListLabel);

        $this->assertLineItemDiscounts($shoppingList, $table);
    }

    /**
     * @Then /^(?:|I )see next line item discounts for checkout:$/
     */
    public function assertCheckoutStepLineItemDiscount(TableNode $table)
    {
        /** @var PromotionCheckoutStep $checkoutStep */
        $checkoutStep = $this->createElement('PromotionCheckoutStep');

        $this->assertLineItemDiscounts($checkoutStep, $table);
    }

    /**
     * @Then /^(?:|I )see next line item discounts for order:$/
     */
    public function assertOrderLineItemDiscount(TableNode $table)
    {
        /** @var PromotionOrder $order */
        $order = $this->createElement('PromotionOrder');

        $this->assertLineItemDiscounts($order, $table);
    }

    /**
     * @Then /^(?:|I )see next line item discounts for backoffice order:$/
     */
    public function assertBackendOrderLineItemDiscount(TableNode $table)
    {
        // discount update request is executed after 1.5 sec timeout
        $this->getSession()->wait(1600);
        $this->waitForAjax();
        /** @var PromotionBackendOrder $order */
        $order = $this->createElement('PromotionBackendOrder');

        $discounts = [];
        /** @var PromotionBackendOrderLineItem $lineItem */
        foreach ($order->getLineItems() as $lineItem) {
            $discounts[] = array_merge([$lineItem->getProductSKU()], $lineItem->getDiscountWithRowTotals());
        }

        $rows = $table->getRows();
        array_shift($rows);

        static::assertEquals($rows, $discounts);
    }

    /**
     * @Then /^(?:|I )click delete line item "(?P<productSKU>[^"]+)"$/
     *
     * @param string $productSKU
     */
    public function clickDeleteLineItem($productSKU)
    {
        /** @var PromotionShoppingList $shoppingList */
        $shoppingList = $this->createElement('PromotionShoppingList');

        /** @var PromotionShoppingListLineItem[] $lineItems */
        $lineItems = $shoppingList->getLineItems();

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getProductSKU() == $productSKU) {
                $lineItem->delete();
            }
        }
    }

    /**
     * @Given /^(?:|I )disable inventory management$/
     */
    public function iDisableInventoryManagement()
    {
        /** @var MainMenu $menu */
        $menu = $this->createElement('MainMenu');
        $menu->openAndClick('System/Configuration');
        $this->waitForAjax();

        /** @var SidebarConfigMenu $sidebarMenu */
        $sidebarMenu = $this->createElement('SidebarConfigMenu');
        $sidebarMenu->openNestedMenu('Commerce/Inventory/Product Options');
        $this->waitForAjax();

        /** @var SystemConfigForm $form */
        $form = $this->createElement('SystemConfigForm');
        $form->uncheckCheckboxByLabel('Decrement Inventory', 'Use default');

        $this->oroMainContext->fillField('Decrement Inventory', 0);
        $this->oroMainContext->pressButton('Save settings');
        $this->oroMainContext->iShouldSeeFlashMessage('Configuration saved');
    }

    /**
     * @Given /^(?:|I )do the order through completion, and should be on order view page$/
     */
    public function iDoTheOrderThroughCompletionAndShouldBeOnOrderViewPage()
    {
        $this->shoppingListContext->openShoppingList('List 1');
        $this->waitForAjax();
        $this->oroMainContext->pressButton('Create Order');
        $this->waitForAjax();
        $this->oroMainContext->pressButton('Continue');
        $this->waitForAjax();
        $this->oroMainContext->assertPageTitle('Shipping Information - Checkout');
        $this->oroMainContext->pressButton('Continue');
        $this->waitForAjax();
        $this->oroMainContext->assertPageTitle('Shipping Method - Checkout');
        $this->oroMainContext->pressButton('Continue');
        $this->waitForAjax();
        $this->oroMainContext->assertPageTitle('Payment - Checkout');
        $this->oroMainContext->pressButton('Continue');
        $this->waitForAjax();
        $this->oroMainContext->assertPageTitle('Order Review - Checkout');
        $this->oroMainContext->pressButton('Submit Order');
        $this->waitForAjax();
        $this->oroMainContext->clickLink('click here to review');
        $this->waitForAjax();
        $this->oroMainContext->assertPage('Order Frontend View');
    }

    /**
     * @Given /^(?:|I )go through the order completion, and should be on order view page$/
     */
    public function iGoThroughTheOrderCompletionAndShouldBeOnOrderViewPage()
    {
        $this->oroMainContext->pressButton('Continue');
        $this->waitForAjax();
        $this->oroMainContext->assertPageTitle('Shipping Information - Checkout');
        $this->oroMainContext->pressButton('Continue');
        $this->waitForAjax();
        $this->oroMainContext->assertPageTitle('Shipping Method - Checkout');
        $this->oroMainContext->pressButton('Continue');
        $this->waitForAjax();
        $this->oroMainContext->assertPageTitle('Payment - Checkout');
        $this->oroMainContext->pressButton('Continue');
        $this->waitForAjax();
        $this->oroMainContext->assertPageTitle('Order Review - Checkout');
        $this->oroMainContext->pressButton('Submit Order');
        $this->waitForAjax();
        $this->oroMainContext->clickLink('click here to review');
        $this->waitForAjax();
        $this->oroMainContext->assertPage('Order Frontend View');
    }

    /**
     * @When /^(?:|I )save order without discounts recalculation$/
     */
    public function iSaveOrderWithoutDiscountsRecalculation()
    {
        /** @var PromotionOrderForm $orderForm */
        $orderForm = $this->createElement('PromotionOrderForm');

        $orderForm->saveWithoutDiscountsRecalculation();
    }

    // @codingStandardsIgnoreStart
    /**
     * Example: Then I expecting to see alphabetic coupon of 10 symbols with prefix "hello" suffix "kitty" and dashes every 0 symbols
     * Example: Then I expecting to see alphanumeric coupon of 16 symbols with prefix "hello" suffix "kitty" and dashes every 4 symbols
     * Example: Then I expecting to see numeric coupon of 12 symbols with prefix "" suffix "" and dashes every 2 symbols
     *
     * @Then /^(?:|I )expecting to see (?P<codeType>[^"]*) coupon of (?P<codeLength>(?:\d+)) symbols with prefix "(?P<codePrefix>[^"]*)" suffix "(?P<codeSuffix>[^"]*)" and dashes every (?P<dashesSequence>(?:\d+)) symbols$/
     * @param string $codeType
     * @param int $codeLength
     * @param string $codePrefix
     * @param string $codeSuffix
     * @param int $dashesSequence
     */
    // @codingStandardsIgnoreEnd
    public function assertCouponMatchesGivenOptions(
        $codeType,
        $codeLength,
        $codePrefix,
        $codeSuffix,
        $dashesSequence
    ) {
        $pattern = $this->getRegexpPatternForCouponCode($codeType);

        if ($dashesSequence > 0) {
            $pattern = $this->setDashesForCouponCode($pattern, $codeLength, $dashesSequence);
        } else {
            $pattern .= '{' . $codeLength . '}';
        }

        $pattern = '/^' . $codePrefix . $pattern . $codeSuffix . '$/';

        $element = $this->elementFactory->createElement('couponCodePreview');
        static::assertMatchesRegularExpression($pattern, $element->getText());
    }

    /**
     * @param LineItemsAwareInterface $element
     * @param $table
     */
    private function assertLineItemDiscounts($element, TableNode $table)
    {
        $expectedDiscounts = [];
        /** @var PromotionShoppingListLineItem $lineItem */
        foreach ($element->getLineItems() as $lineItem) {
            $sku = $lineItem->getProductSKU();
            if ($sku) {
                $expectedDiscounts[$sku] = ['sku' => $sku, 'discount' => $lineItem->getDiscount()];
            }
        }

        $rows = $table->getRows();
        array_shift($rows);

        foreach ($rows as list($sku, $discount)) {
            static::assertNotEmpty(
                $expectedDiscounts[$sku],
                sprintf(
                    'Can\'t find line item with "%s" sku.',
                    $sku
                )
            );
            static::assertEquals(
                (string)$expectedDiscounts[$sku]['discount'],
                $discount,
                sprintf(
                    'Wrong value for "%s" line item. Expected "%s" got "%s"',
                    $sku,
                    $expectedDiscounts[$sku]['discount'],
                    $discount
                )
            );
        }
    }

    /**
     * @param string $codeType
     * @return string
     */
    protected function getRegexpPatternForCouponCode($codeType)
    {
        switch ($codeType) {
            case CodeGenerationOptions::ALPHABETIC_CODE_TYPE:
                $pattern = '[a-zA-Z]';
                break;
            case CodeGenerationOptions::NUMERIC_CODE_TYPE:
                $pattern = '[0-9]';
                break;
            default:
                $pattern = '[a-zA-Z0-9]';
        };

        return $pattern;
    }

    /**
     * @param string $pattern
     * @param int $codeLength
     * @param int $dashesSequence
     * @return string
     */
    protected function setDashesForCouponCode($pattern, $codeLength, $dashesSequence)
    {
        $patternData = [];
        $complexPattern = $pattern . '{' . $dashesSequence . '}';
        $partsLimit = floor($codeLength / $dashesSequence);

        for ($i = 0; $i < $partsLimit; $i++) {
            $patternData[] = $complexPattern;
        }

        $codeLengthAfterDashes = $codeLength % $dashesSequence;

        if ($codeLengthAfterDashes) {
            $patternData[] = $pattern . '{' . $codeLengthAfterDashes . '}';
        }

        return implode(CodeGenerator::DASH_SYMBOL, $patternData);
    }
}
