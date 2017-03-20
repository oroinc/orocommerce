<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutStep;
use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutSuccessStep;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;
use Oro\Bundle\WarehouseBundle\SystemConfig\WarehouseConfig;

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
     * @BeforeScenario @conditional-fixtures
     * @todo: Move this to other bundle: BB-8365
     */
    public function loadFixtures()
    {
        /* @var $configManager ConfigManager */
        $configManager = $this->getContainer()->get('oro_config.global');

        /* @var $warehouses Warehouse[] */
        $warehouses = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityRepository(
            Warehouse::class
        )->findAll();

        $enabledWarehouses = [];

        $k = 0;
        foreach ($warehouses as $warehouse) {
            $warehouseConfig = new WarehouseConfig($warehouse, $k + 1);
            $enabledWarehouses[] = $warehouseConfig;
            $k;
        }

        $configManager->set(
            'oro_warehouse.enabled_warehouses',
            $enabledWarehouses
        );

        $configManager->flush();
    }

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
