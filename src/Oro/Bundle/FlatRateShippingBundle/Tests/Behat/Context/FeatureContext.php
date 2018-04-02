<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * Example: I add shipping method "Flat Rate" with:
     *            | price        | 100      |
     *            | handling_fee | 0        |
     *            | type         | per_item |
     * @When I add shipping method :shippingMethod with:
     *
     * @param string $shippingMethod
     * @param TableNode $table
     */
    public function fillProductNameFieldWithValue($shippingMethod, TableNode $table)
    {
        /** @var Form $form */
        $form = $this->createElement('Shipping Rule Flat Rate');
        $shippingMethodSelector = $form->find('xpath', '//select[@name="oro_shipping_methods_configs_rule[method]"]');
        $this->elementFactory->wrapElement('Select2Entity', $shippingMethodSelector)->setValue($shippingMethod);
        $form->find('css', 'a.add-method')->click();
        $this->waitForAjax();

        $form->fill($table);
    }
}
