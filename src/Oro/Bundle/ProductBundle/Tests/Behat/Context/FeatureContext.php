<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @When I fill product name field with :productName value
     *
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
     * @When I am on quick order form page
     */
    public function amOnQuickOrderFormPage()
    {
        $this->visitPath('customer/product/quick-add/');
    }

    /**
     * @When I add product :productSku with quantity :productQuantity to quick order form
     *
     * @param string $productSku
     * @param int    $productQuantity
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
     *
     * @param string $productSku
     * @param int    $productQuantity
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

    /**
     * @Then I go to product with sku :productSku edit page
     *
     * @param string $productSku
     */
    public function goToProductEditPage($productSku)
    {
        /** @var MainMenu $mainMenu */
        $mainMenu = $this->createElement('MainMenu');
        $mainMenu->openAndClick('Products/ Products');
        $this->waitForAjax();

        /** @var Grid $grid */
        $grid = $this->createElement('Grid');
        $grid->clickActionLink($productSku, 'Edit');
        $this->waitForAjax();
    }

    /**
     * Example: Then I fill product fields with next data:
     *            | Name                | Name      |
     *            | SKU                 | SKU       |
     *            | Status              | enabled   |
     *            | PrimaryUnit         | item      |
     *            | PrimaryPrecision    | 0         |
     *            | AdditionalUnit      | set       |
     *            | AdditionalPrecision | 0         |
     * @Then I fill product fields with next data:
     *
     * @param TableNode $table
     *
     * @return Form
     */
    public function fillProductFieldsWithNextData(TableNode $table)
    {
        /** @var Form $form */
        $form = $this->createElement('ProductForm');

        if (in_array('AdditionalUnit', $table->getColumn(0), true)) {
            $additionalUnitAdd = $form->find('css', 'a.btn.add-list-item');
            $additionalUnitAdd->click();
            $this->waitForAjax();
        }
        $form->fill($table);

        return $form;
    }

    /**
     * Example: Then I save product with next data:
     *            | Name                | Name      |
     *            | SKU                 | SKU       |
     *            | Status              | enabled   |
     *            | PrimaryUnit         | item      |
     *            | PrimaryPrecision    | 0         |
     *            | AdditionalUnit      | set       |
     *            | AdditionalPrecision | 0         |
     * @Then I save product with next data:
     *
     * @param TableNode $table
     */
    public function saveProductWithNextData(TableNode $table)
    {
        $form = $this->fillProductFieldsWithNextData($table);
        $form->saveAndClose();
        $this->waitForAjax();
    }

    /**
     * @Then I should see value ":value" in ":elementName" options
     *
     * @param string $value
     * @param string $elementName
     *
     * @return boolean
     */
    public function shouldSeeValueInElementOptions($value, $elementName)
    {
        static::assertTrue(in_array($value, $this->getOptionsForElement($elementName), true));
    }

    /**
     * @Then I should not see value ":value" in ":elementName" options
     *
     * @param string $value
     * @param string $elementName
     *
     * @return boolean
     */
    public function shouldNotSeeValueInElementOptions($value, $elementName)
    {
        static::assertFalse(in_array($value, $this->getOptionsForElement($elementName), true));
    }

    /**
     * @param string $elementName
     *
     * @return array
     */
    protected function getOptionsForElement($elementName)
    {
        $element = $this->createElement($elementName);
        $optionElements = $element->findAll('css', 'option');
        $options = [];
        /** @var NodeElement[] $optionElements */
        foreach ($optionElements as $option) {
            $options[] = $option->getValue();
        }

        return $options;
    }
}
