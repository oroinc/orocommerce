<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

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

    /**
     * @When I am on quick order form page
     */
    public function amOnQuickOrderFormPage()
    {
        $this->visitPath('customer/product/quick-add/');
    }

    /**
     * @When I add product :productSku with quantity :productQuantity to quick order form
     * @param string $productSku
     * @param int $productQuantity
     */
    public function addProductToQuickAddForm($productSku, $productQuantity)
    {
        $quickAddForm = $this->createElement('QuickAddForm');
        $firstSkuField = $quickAddForm->find('css', 'input[name="oro_product_quick_add[products][0][productSku]"]');

        $firstSkuField->focus();
        $firstSkuField->setValue($productSku);
        $firstSkuField->blur();

        $firstQuantityField = $quickAddForm->find(
            'css',
            'input[name="oro_product_quick_add[products][0][productQuantity]"]'
        );

        $firstQuantityField->setValue($productQuantity);
    }

    /**
     * @When click create order button
     */
    public function clickCreateOrderButton()
    {
        $createOrderButton = $this->createElement('CreateOrderButton');
        $createOrderButton->click();
    }

    /**
     * @Then I should see flash error messages
     */
    public function shouldSeeFlashErrorMessages()
    {
        $page = $this->getPage();
        $flashMessages = $page->findAll('css', 'div.notification-flash--error');

        static::assertNotEmpty($flashMessages);
    }

    /**
     * @Then quick order form contains product with sku :productSku and quantity :productQuantity
     * @param string $productSku
     * @param int $productQuantity
     */
    public function quickOrderFormContainsProductWithSkuAndQuantity($productSku, $productQuantity)
    {
        $quickAddForm = $this->createElement('QuickAddForm');
        $firstSkuField = $quickAddForm->find('css', 'input[name="oro_product_quick_add[products][0][productSku]"]');

        static::assertEquals($productSku, $firstSkuField->getValue());

        $firstQuantityField = $quickAddForm->find(
            'css',
            'input[name="oro_product_quick_add[products][0][productQuantity]"]'
        );

        static::assertEquals($productQuantity, $firstQuantityField->getValue());
    }
}
