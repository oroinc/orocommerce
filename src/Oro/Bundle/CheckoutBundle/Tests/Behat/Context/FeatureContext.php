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
        'Value'=> 'paymentMethod',
        'Delete this Shopping List after Submitting Order' => 'oro_workflow_transition[remove_source]'
    ];

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
        $element = $page->findField(self::$valueMapping[$value]);

        self::assertTrue($element->isValid(), sprintf('Could not found option "%s" on page', $value));

        if (!$element->isChecked()) {
            $element->getParent()->click();
        }

        self::assertTrue($element->isChecked(), sprintf('Option "%s" is not checked', $value));

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

        $requestAQuote = $this->createElement($element);

        self::assertNotNull($requestAQuote);

        foreach ($table->getRows() as $row) {
            $productFound = false;
            foreach ($requestAQuote->getElements($element.'ProductLine') as $productLine) {
                if ($this->matchProductLine($productLine, $row, $element)) {
                    $productFound = true;
                    break;
                }
            }

            self::assertTrue($productFound, sprintf(
                'Product %s, QTY: %s %s has not been found',
                ...$row
            ));
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
     * @param Element $productLine
     * @param array   $row
     * @param string  $elementName
     * @return bool
     */
    private function matchProductLine(Element $productLine, array $row, $elementName)
    {
        list($name, $quantity, $unit) = $row;

        try {
            self::assertContains($name, $productLine->getElement($elementName.'ProductLineName')->getText());
            self::assertContains($quantity, $productLine->getElement($elementName.'ProductLineQuantity')->getText());
            self::assertContains($unit, $productLine->getElement($elementName.'ProductLineUnit')->getText());
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }
}
