<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutStep;
use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutSuccessStep;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
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
        'Flat Rate' => 'shippingMethodType',
        'Payment Terms' => 'paymentMethod',
        'Delete the shopping list' => 'oro_workflow_transition[remove_source]'
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
     * @When /^I had checked "(?P<value>.+)" on the "(?P<step>[\w\s]+)" checkout step and press (?P<button>[\w\s]+)$/
     *
     * @param string $value
     * @param string $step
     * @param string $button
     */
    public function checkValueOnCheckoutStepAndPressButton($value, $step, $button)
    {
        $this->assertTitle($step);

        $page = $this->getSession()->getPage();
        $element = $page->findField(self::$valueMapping[$value]);

        self::assertTrue($element->isValid(), sprintf('Could not found option "%s" on page', $value));
        self::assertTrue($element->isChecked(), sprintf('Option "%s" is not checked', $value));

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
}
