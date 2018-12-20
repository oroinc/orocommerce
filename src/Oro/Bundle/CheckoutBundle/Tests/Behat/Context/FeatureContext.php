<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutStep;
use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutSuccessStep;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /** @var array */
    protected static $formMapping = [
        'Billing Information' => 'oro_workflow_transition[billing_address][customerAddress]',
        'Shipping Information' => 'oro_workflow_transition[shipping_address][customerAddress]'
    ];

    /** @var array */
    protected static $valueMapping = [
        'Ship to this address' => 'oro_workflow_transition[ship_to_billing_address]',
        'Flat Rate' => 'shippingMethodType',
        'Payment Terms' => 'paymentMethod',
        'Value' => 'paymentMethod',
        'Delete this shopping list after submitting order' => 'oro_workflow_transition[remove_source]',
        'Save shipping address' => 'oro_workflow_transition[save_shipping_address]',
        'Save my data and create an account' =>
            'oro_workflow_transition[late_registration][is_late_registration_enabled]'
    ];

    /** @var string */
    protected $currentPath;

    /**
     * @When /^I select "(?P<value>.+)" on the "(?P<step>[\w\s]+)" checkout step and press (?P<button>[\w\s]+)$/
     *
     * @param string $value
     * @param string $step
     * @param string $button
     */
    public function selectValueOnCheckoutStepAndPressButton($value, $step, $button)
    {
        $this->assertTitle($step);

        $page = $this->getSession()->getPage();
        $page->selectFieldOption(self::$formMapping[$step], $value);

        $page->pressButton($button);
        $this->waitForAjax();
    }

    /**
     * @When /^I check "(?P<value>.+)" on the "(?P<step>[\w\s]+)" checkout step and press (?P<button>[\w\s]+)$/
     *
     * @param string $value
     * @param string $step
     * @param string $button
     */
    public function checkValueOnCheckoutStepAndPressButton($value, $step, $button)
    {
        $this->assertTitle($step);
        $this->checkValueOnCheckoutPage($value);

        $page = $this->getSession()->getPage();
        $page->pressButton($button);
        $this->waitForAjax();
    }

    /**
     * @When /^I check "(?P<value>.+)" on the checkout page$/
     *
     * @param string $value
     */
    public function checkValueOnCheckoutPage($value)
    {
        $page = $this->getSession()->getPage();
        $element = $page->findField(self::$valueMapping[$value] ?? $value);

        self::assertTrue($element->isValid(), sprintf('Could not found option "%s" on page', $value));

        if (!$element->isChecked()) {
            $element->getParent()->click();
        }

        self::assertTrue($element->isChecked(), sprintf('Option "%s" is not checked', $value));

        $this->waitForAjax();
    }

    /**
     * @When /^I uncheck "(?P<value>.+)" on the checkout page$/
     *
     * @param string $value
     */
    public function unCheckValueOnCheckoutPage($value)
    {
        $page = $this->getSession()->getPage();
        $element = $page->findField(self::$valueMapping[$value] ?? $value);

        self::assertTrue($element->isValid(), sprintf('Could not found option "%s" on page', $value));

        if ($element->isChecked()) {
            $element->getParent()->click();
        }

        self::assertFalse($element->isChecked(), sprintf('Option "%s" is checked', $value));

        $this->waitForAjax();
    }

    /**
     * @When /^I uncheck "(?P<value>.+)" on the "(?P<step>[\w\s]+)" checkout step and press (?P<button>[\w\s]+)$/
     *
     * @param string $value
     * @param string $step
     * @param string $button
     */
    public function uncheckValueOnCheckoutStepAndPressButton($value, $step, $button)
    {
        $this->assertTitle($step);
        $this->uncheckValueOnCheckoutPage($value);

        $page = $this->getSession()->getPage();
        $page->pressButton($button);
        $this->waitForAjax();
    }


    /**
     * @When /^on the "(?P<step>[\w\s]+)" checkout step I press (?P<button>[\w\s]+)$/
     *
     * @param string $step
     * @param string $button
     */
    public function onCheckoutStepAndPressButton($step, $button)
    {
        $this->assertTitle($step);
        $page = $this->getSession()->getPage();
        $page->pressButton($button);
        $this->waitForAjax();
    }

    /**
     * @When /^I see the "Thank You" page with "(?P<title>.+)" title$/
     *
     * @param string $title
     */
    public function onSuccessCheckoutStep($title)
    {
        /** @var CheckoutSuccessStep $checkoutStep */
        $checkoutStep = $this->createElement('CheckoutSuccessStep');
        $checkoutStep->assertTitle($title);
    }

    /**
     * @param string $title
     */
    protected function assertTitle($title)
    {
        /** @var CheckoutStep $checkoutStep */
        $checkoutStep = $this->createElement('CheckoutStep');
        $checkoutStep->assertTitle($title);
    }

    /**
     * @Given /^"(?P<step>[\w\s]+)" checkout step "(?P<element>[\w\s]+)" contains products$/
     */
    public function checkoutStepContainsProducts($step, $element, TableNode $table)
    {
        $this->assertTitle($step);
        $this->assertCheckoutGridContainsProducts($element, $table);
    }

    /**
     * Checks if Checkout summary table contains following products with quantity and product unit
     * Example:
     * Then Checkout "Order Summary Products Grid" should contain products:
     *      | 400-Watt Bulb Work Light | 5 | items |
     *
     * @Given /^Checkout "(?P<element>[\w\s]+)" should contain products:$/
     *
     * @param string $element
     * @param TableNode $table
     */
    public function checkoutGridContainsProducts($element, TableNode $table)
    {
        $this->assertCheckoutGridContainsProducts($element, $table);
    }

    /**
     * Checks if Checkout summary table not contains following products with quantity and product unit
     * Example:
     * Then Checkout "Order Summary Products Grid" should not contain products:
     *      | 400-Watt Bulb Work Light | 5 | items |
     *
     * @Given /^Checkout "(?P<element>[\w\s]+)" should not contain products:$/
     *
     * @param string $element
     * @param TableNode $table
     */
    public function checkoutGridNotContainsProducts($element, TableNode $table)
    {
        $this->assertCheckoutGridContainsProducts($element, $table, false);
    }

    /**
     * @param string $gridName
     * @param TableNode $table
     * @param bool $contains
     */
    protected function assertCheckoutGridContainsProducts($gridName, TableNode $table, $contains = true)
    {
        $grid = $this->createElement($gridName);

        self::assertNotNull($grid);

        foreach ($table->getRows() as $row) {
            $productFound = false;
            foreach ($grid->getElements($gridName . 'ProductLine') as $productLine) {
                if ($this->matchProductLine($productLine, $row, $gridName)) {
                    $productFound = true;
                    break;
                }
            }

            if ($contains) {
                self::assertTrue($productFound, sprintf(
                    'Product %s, QTY: %s %s has not been found',
                    ...$row
                ));
            } else {
                self::assertFalse($productFound, sprintf(
                    'Product %s, QTY: %s %s has been found',
                    ...$row
                ));
            }
        }
    }

    /**
     * @Then /^on the "(?P<step>[\w\s]+)" checkout step (?:|I )go back to "(?P<buttonTitle>(?:[^"]|\\")*)"$/
     *
     * @param string $step
     * @param string $buttonTitle
     */
    public function goBackTo($step, $buttonTitle)
    {
        $this->assertTitle($step);

        $goBackButton = null;
        $titleAttribute = 'data-title';
        foreach ($this->findAllElements('CheckoutGoBackButton') as $goBackButton) {
            if (!$goBackButton->hasAttribute($titleAttribute)) {
                continue;
            }

            if ($goBackButton->getAttribute($titleAttribute) === $buttonTitle) {
                $goBackButton->click();
                $this->waitForAjax();

                return;
            }
        }

        self::fail(sprintf('Button with title "%s" was not found', $buttonTitle));
    }

    /**
     * @Given /^(?:|I )wait "Submit Order" button$/
     */
    public function iWaitSubmitOrderButton()
    {
        $this->getSession()->getDriver()->wait(30000, "0 == $('button.checkout__submit-btn:disabled').length");
    }

    /**
     * @param Element $productLine
     * @param array $row
     * @param string $elementName
     * @return bool
     */
    private function matchProductLine(Element $productLine, array $row, $elementName)
    {
        list($name, $quantity, $unit) = $row;

        try {
            self::assertContains($name, $productLine->getElement($elementName . 'ProductLineName')->getText());
            self::assertContains($quantity, $productLine->getElement($elementName . 'ProductLineQuantity')->getText());
            self::assertContains($unit, $productLine->getElement($elementName . 'ProductLineUnit')->getText());
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * This step used for compare urls after some actions
     *
     * @Given /^(?:|I )keep in mind current path$/
     */
    public function iKeepInMindCurrentPath()
    {
        $parsedUrl = parse_url($this->getSession()->getCurrentUrl());
        $this->currentPath = $parsedUrl['path'];
    }

    /**
     * @Then path remained the same
     */
    public function urlRemainedTheSame()
    {
        $parsedUrl = parse_url($this->getSession()->getCurrentUrl());
        self::assertEquals(
            $this->currentPath,
            $parsedUrl['path']
        );
    }

    /**
     * @Then /^(?:|I )should see "(?P<field>(?:[^"]|\\")*)" button enabled$/
     */
    public function iShouldSeeButtonEnabled($field)
    {
        self::assertTrue(
            $this->spin(function () use ($field) {
                $button = $this->elementFactory->createElement($field);

                return !$button->hasAttribute('disabled');
            }, 5),
            'Button is disabled'
        );
    }

    /**
     * @Then /^(?:|I )should see "(?P<field>(?:[^"]|\\")*)" button disabled/
     */
    public function iShouldSeeButtonDisabled($field)
    {
        self::assertTrue(
            $this->spin(function () use ($field) {
                $button = $this->elementFactory->createElement($field);

                return $button->hasAttribute('disabled');
            }, 5),
            'Button is enabled'
        );
    }

    /**
     * @When /^(?:|I )expand "(?P<entity>(?:[^"]|\\")*)" permissions in "(?P<section>(?:[^"]|\\")*)" section$/
     *
     * @param string $entity
     * @param string $section
     */
    public function iExpandEntityPermissions($entity, $section)
    {
        $page = $this->getSession()->getPage();
        $expandElement = $page->find(
            'xpath',
            "//h4[contains(@class,'scrollspy-title')][text()=\"$section\"]/.." .
            "//div[contains(@class,'entity-name')][text()=\"$entity\"]" .
            "/..//*[contains(@class,'collapse-action')]"
        );
        if ($expandElement) {
            $expandElement->focus();
            $expandElement->click();
        }
    }

    /**
     * @When /^(?:|I )click Perform Transition permissions for "(?P<transition>(?:[^"]|\\")*)" transition$/
     *
     * @param string $transition
     */
    public function iClickPerformTransitionPermissions($transition)
    {
        $page = $this->getSession()->getPage();
        $element = $page->find(
            'xpath',
            "//*[contains(@class,'field-name')][contains(text(),'$transition')]/" .
            "..//*[contains(@class,'action-permissions__item')]/" .
            "*[contains(@class,'action-permissions__dropdown-toggle')]"
        );
        if ($element) {
            $element->focus();
            $element->click();
        }
    }

    /**
     * @Then /^(?:|I )should see next items in permissions dropdown:$/
     *
     * @param TableNode $table
     */
    public function iShouldSeeItemsInPermissionsDropdown(TableNode $table)
    {
        $itemElements = $this->findAllElements('Permissions Dropdown Items');
        $actualItems = [];
        if (count($itemElements)) {
            foreach ($itemElements as $itemElement) {
                $actualItems[] = $itemElement->getText();
            }
        }

        $expectedItems = [];
        foreach ($table->getRows() as $row) {
            $expectedItems[] = reset($row);
        }

        self::assertEquals($expectedItems, $actualItems);
    }

    /**
     * @Then /^(?:|I )choose "(?P<option>[^"]*)" in permissions dropdown$/
     *
     * @param string $option
     */
    public function iSelectOptionInPermissionsDropdown($option)
    {
        $itemElement = $this->findElementContains('Permissions Dropdown Items', $option);

        self::assertNotNull($itemElement, "Selected Option is not found in permissions dropdown");

        $itemElement->focus();
        $itemElement->click();
    }
}
