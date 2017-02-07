<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @When I create product and click continue
     */
    public function createProductAndClickContinue()
    {
        $this->visitPath('admin/product/create');
        $this->getSession()->getPage()->pressButton('Continue');
        $this->waitForAjax();
    }

    /**
     * @When I fill product name field with :productName value
     * @param string $productName
     */
    public function fillProductNameFieldWithValue($productName)
    {
        $productNameField = $this->createElement('ProductNameField');
        $productNameField->focus();
        $productNameField->setValue($productName);
        $productNameField->blur();
        $this->waitForAjax();
    }

    /**
     * @Then I should see slug prototypes field filled with :slugName value
     * @param string $slugName
     */
    public function shouldSeeSlugPrototypesFieldFilledWithValue($slugName)
    {
        $slugPrototypesField = $this->createElement('SlugPrototypesField');

        self::assertEquals($slugName, $slugPrototypesField->getValue());
    }
}
