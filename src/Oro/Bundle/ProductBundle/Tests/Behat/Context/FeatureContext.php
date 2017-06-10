<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\MinkExtension\Context\MinkAwareContext;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\ProductBundle\Tests\Behat\Element\ProductTemplate;
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
     * Validate unique variant field values when changing simple products or extended fields BB-7110
     *
     * I click on info tooltip for selected Enum value
     *
     * Example: I click info tooltip for enum value "Red"
     *
     * @When /^I click info tooltip for enum value "(?P<name>[\w\s]+)"$/
     *
     */
    public function iClickTooltipOnEnumValue($name)
    {
        $entityConfigForm = $this->createElement('EntityConfigForm');

        $enumInputContainer = $entityConfigForm->find(
            'xpath',
            sprintf(
                '//input[@value="%s"]/../../..',
                $name
            )
        );

        $i = $enumInputContainer->find(
            'xpath',
            'i[contains(@class, "fa-info-circle") and contains(@class, "tooltip-icon")]'
        );
        $i->click();
    }

    /**
     * Validate unique variant field values when changing simple products or extended fields BB-7110
     *
     * Selected Enum value is unique and used in Configurable product.
     * I should see info tooltip for the selected enum value
     *
     * Example: I should see info tooltip for enum value "Red"
     *
     * @Then /^(?:|I should )see info tooltip for enum value "(?P<name>[^"]+)"$/
     *
     */
    public function iSeeTooltipForEnumValue($name)
    {
        $entityConfigForm = $this->createElement('EntityConfigForm');

        // @codingStandardsIgnoreStart
        $enumInputWithTooltip = $entityConfigForm->find(
            'xpath',
            sprintf(
                '//input[@value="%s"]/../../../i[contains(@class, "fa-info-circle") and contains(@class, "tooltip-icon")]',
                $name
            )
        );
        // @codingStandardsIgnoreEnd

        static::assertNotEmpty($enumInputWithTooltip);
    }

    /**
     * Validate unique variant field values when changing simple products or extended fields BB-7110
     *
     * On the "Product" entity I delete enum value
     *
     * Example: I delete enum value by name "Green"
     *
     * @When /^I delete enum value by name "(?P<name>[\w\s]+)"$/
     *
     */
    public function iDeleteEnumValueByName($name)
    {
        $entityConfigForm = $this->createElement('EntityConfigForm');

        $enumInputContainer = $entityConfigForm->find(
            'xpath',
            sprintf(
                '//input[@value="%s"]/../../..',
                $name
            )
        );

        $x = $enumInputContainer->find(
            'xpath',
            'button[contains(@class, "removeRow")]'
        );
        $x->press();
    }

    /**
     * Validate unique variant field values when changing simple products or extended fields BB-7110
     *
     * I should not see selected enum value on Product attribute edit page
     *
     * Example: I should not see enum value "Green"
     *
     * @Then /^I should not see enum value "(?P<name>[\w\s]+)"$/
     *
     */
    public function iShouldNotSeeEnumValue($name)
    {
        $entityConfigForm = $this->createElement('EntityConfigForm');

        $enumInputContainer = $entityConfigForm->find(
            'xpath',
            sprintf(
                '//input[@value="%s"]',
                $name
            )
        );

        self::assertEmpty($enumInputContainer);
    }

    /**
     * Assert popup
     * Example: Then I should see "At least one of the fields First name, Last name must be defined." popup
     *
     * @Then /^(?:|I should )see "(?P<title>[^"]+)" popup$/
     */
    public function iShouldSeePopup($title)
    {
        $popup = $this->spin(function (MinkAwareContext $context) {
            return $context->getSession()->getPage()->find('css', '.popover-content');
        });

        self::assertNotFalse($popup, 'Popup not found on page');
        $message = $popup->getText();
        $popup->find('css', 'i.popover-close')->click();

        self::assertContains($title, $message, sprintf(
            'Expect that "%s" error message contains "%s" string, but it isn\'t',
            $message,
            $title
        ));
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
     * @When I add price :price to Price Attribute :priceAttribute
     *
     * @param string $priceAttributeName
     * @param int $price
     */
    public function addPriceToAdditionalPriceAttribute($priceAttributeName, $price)
    {
        /** @var Form $form */
        $form = $this->createElement('ProductForm');

        /** @var NodeElement $label */
        $labels = $form->findAll('xpath', '//div[@class="price-attributes-collection"]/div/div/label');

        $savedLabel = false;
        foreach ($labels as $label) {
            if (trim($label->getText()) === $priceAttributeName) {
                $label->getParent()->getParent()
                    ->find('xpath', '//input[contains(@id, "productPriceAttributesPrices")]')
                    ->setValue($price);

                $savedLabel = true;
            }
        }

        if (!$savedLabel) {
            self::fail(sprintf('Can not find label with text %s', $priceAttributeName));
        }
    }

    /**
     * @When I clear Price Attribute :priceAttribute
     *
     * @param string $priceAttributeName
     */
    public function clearPriceToAdditionalPriceAttribute($priceAttributeName)
    {
        /** @var Form $form */
        $form = $this->createElement('ProductForm');

        /** @var NodeElement $label */
        $labels = $form->findAll('xpath', '//div[@class="price-attributes-collection"]/div/div/label');

        $savedLabel = false;
        foreach ($labels as $label) {
            if (trim($label->getText()) === $priceAttributeName) {
                $label->getParent()->getParent()
                    ->find('xpath', '//input[contains(@id, "productPriceAttributesPrices")]')
                    ->setValue(' ');

                $savedLabel = true;
            }
        }

        if (!$savedLabel) {
            self::fail(sprintf('Can not find label with text %s', $priceAttributeName));
        }
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
     * @Then /^I should see value "(?P<value>[^"]+)" in "(?P<elementName>[^"]+)" options$/
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
     * @Then /^I should not see value "(?P<value>[^"]+)" in "(?P<elementName>[^"]+)" options$/
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

    /**
     * @When I open product with sku ":sku" on the store frontend
     */
    public function openProductWithSkuOnTheStoreFrontend($sku)
    {
        $product = $this->getRepository(Product::class)->findOneBy(['sku' => $sku]);

        if (!$product) {
            self::fail(sprintf('Can\'t find product with sku "%s"', $sku));
        }

        $this->visitPath($this->getUrl('oro_product_frontend_product_view', ['id' => $product->getId()]));
    }

    /**
     * Assert specific template containing specified data on page.
     * Example: Then I should see "Two Columns Page" with "Product Group" containing data:
     *            | Color | Green |
     *            | Size  | L     |
     *
     * @Then /^(?:|I )should see "(?P<templateName>[^"]*)" with "(?P<groupName>[^"]*)" containing data:$/
     */
    public function assertTemplateWithGroupContainsData($templateName, $groupName, TableNode $table)
    {
        /** @var ProductTemplate $template */
        $template = $this->createElement($templateName);

        $template->assertGroupWithValue($groupName, $table);
    }

    /**
     * Assert prices on specific template.
     * Example: Then I should see the following prices on "Two Columns Page":
     *    | Listed Price: | [$10.00 / item, $445.50 / set] |
     *    | Your Price:   | $10.00 / item                  |
     *
     * @Then /^(?:|I )should see the following prices on "(?P<templateName>[^"]*)":$/
     */
    public function assertPricesOnTemplatePage($templateName, TableNode $table)
    {
        /** @var ProductTemplate $template */
        $template = $this->createElement($templateName);

        $template->assertPrices($table);
    }

    /**
     * @param string $route
     * @param array $params
     * @return string
     */
    protected function getUrl($route, $params = [])
    {
        return $this->getContainer()->get('router')->generate($route, $params);
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass($className)
            ->getRepository($className);
    }

    /**
     * @Then /^I should not see tag "(?P<tag>[^"]+)" inside "(?P<element>[^"]+)" element$/
     *
     * @param string $tag
     * @param string $element
     */
    public function iShouldNotSeeTagInsideElement($tag, $element)
    {
        $page = $this->getSession()->getPage();

        $result = $page->find(
            'xpath',
            '//*[@id="' . $element . '"]/' . $tag
        );

        static::assertTrue(
            is_null($result),
            sprintf('Tag "%s" inside element "%s" is found', $element, $tag)
        );
    }
}
