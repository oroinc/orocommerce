<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\MinkAwareContext;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\AttachmentBundle\Tests\Behat\Context\AttachmentImageContext;
use Oro\Bundle\ConfigBundle\Tests\Behat\Context\FeatureContext as ConfigContext;
use Oro\Bundle\DataGridBundle\Tests\Behat\Context\GridContext;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridFilters;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Tests\Behat\Context\FormContext;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Inventory\InventoryManager;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Behat\Element\MultipleChoice;
use Oro\Bundle\ProductBundle\Tests\Behat\Element\ProductTemplate;
use Oro\Bundle\ProductBundle\Tests\Behat\Element\ProductUnitSelector;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    private const PRODUCT_SKU = 'SKU123';
    private const PRODUCT_INVENTORY_QUANTITY = 100;
    private const PRODUCT_LOW_INVENTORY_QUANTITY = 10;
    private const IMAGES_ORDER_REMEMBER_KEY = 'images_order';

    private ?OroMainContext $oroMainContext = null;

    private ?GridContext $gridContext = null;

    private ?ConfigContext $configContext = null;

    private ?FormContext $formContext = null;

    private ?AttachmentImageContext $attachmentImageContext = null;

    private array $rememberedData = [];

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
        $this->gridContext = $environment->getContext(GridContext::class);
        $this->configContext = $environment->getContext(ConfigContext::class);
        $this->formContext = $environment->getContext(FormContext::class);
        $this->attachmentImageContext = $environment->getContext(AttachmentImageContext::class);
    }

    /**
     * @Given /^There are products in the system (?P<level>available|low inventory) for order$/
     */
    public function thereAreProductsAvailableForOrder($level): void
    {
        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->getAppContainer()->get('oro_entity.doctrine_helper');
        $inventoryLevelEntityManager = $doctrineHelper->getEntityManagerForClass(InventoryLevel::class);
        $inventoryLevelRepository = $inventoryLevelEntityManager->getRepository(InventoryLevel::class);

        $iterator = $this->getProductUnitPrecisionsIteratorAll();
        /** @var ProductUnitPrecision $productUnitPrecision */
        foreach ($iterator as $productUnitPrecision) {
            $product = $productUnitPrecision->getProduct();
            /** @var InventoryLevel $inventoryLevel */
            $inventoryLevel = $inventoryLevelRepository->findOneBy(
                ['product' => $product, 'productUnitPrecision' => $productUnitPrecision]
            );

            if (!$inventoryLevel) {
                /** @var InventoryManager $inventoryManager */
                $inventoryManager = $this->getAppContainer()->get('oro_inventory.manager.inventory_manager');
                $inventoryLevel = $inventoryManager->createInventoryLevel($product->getPrimaryUnitPrecision());
            }
            $qty = $level === 'available' ? self::PRODUCT_INVENTORY_QUANTITY : self::PRODUCT_LOW_INVENTORY_QUANTITY;
            $inventoryLevel->setQuantity($qty);

            $inventoryLevelEntityManager->persist($inventoryLevel);
        }

        $inventoryLevelEntityManager->flush();
    }

    /**
     * @Given /^I set products inventory levels as following:$/
     */
    public function setProductsInventoryLevel(TableNode $table): void
    {
        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->getAppContainer()->get('oro_entity.doctrine_helper');
        $productEntityRepository = $doctrineHelper->getEntityRepositoryForClass(Product::class);
        $inventoryLevelEntityManager = $doctrineHelper->getEntityManagerForClass(InventoryLevel::class);
        $inventoryLevelRepository = $inventoryLevelEntityManager->getRepository(InventoryLevel::class);

        foreach ($table->getRows() as [$sku, $unitCode, $quantity]) {
            /** @var Product $product */
            $product = $productEntityRepository->findOneBy(['sku' => $sku]);
            $precision = $product->getUnitPrecision($unitCode) ?? $product->getPrimaryUnitPrecision();
            $inventoryLevel = $inventoryLevelRepository->findOneBy(
                ['product' => $product, 'productUnitPrecision' => $precision]
            );
            if (!$inventoryLevel) {
                /** @var InventoryManager $inventoryManager */
                $inventoryManager = $this->getAppContainer()->get('oro_inventory.manager.inventory_manager');
                $inventoryLevel = $inventoryManager->createInventoryLevel($precision);
            }

            $inventoryLevel->setQuantity($quantity);
            $inventoryLevelEntityManager->persist($inventoryLevel);
        }

        $inventoryLevelEntityManager->flush();
    }

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
     * @Then I should see flash error messages
     */
    public function shouldSeeFlashErrorMessages()
    {
        $page = $this->getPage();
        $flashMessages = $page->findAll('css', 'div.notification-flash--error');

        static::assertNotEmpty($flashMessages);
    }

    /**
     * Validate unique variant field values when changing simple products or extended fields BB-7110
     *
     * I click on info tooltip for selected Enum value
     *
     * Example: I click info tooltip for enum value "Red"
     *
     * @When /^I click info tooltip for enum value "(?P<name>[\w\s]+)"$/
     */
    public function iClickTooltipOnEnumValue($name)
    {
        $entityConfigForm = $this->createElement('EntityConfigForm');

        $enumInputContainer = $entityConfigForm->find(
            'xpath',
            sprintf(
                '//input[@value="%s"]/../../../div[contains(@class, "tooltip-icon-container")]',
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
     */
    public function iSeeTooltipForEnumValue($name)
    {
        $entityConfigForm = $this->createElement('EntityConfigForm');

        // phpcs:disable
        $enumInputWithTooltip = $entityConfigForm->find(
            'xpath',
            sprintf(
                '//input[@value="%s"]/../../../div[contains(@class, "tooltip-icon-container")]/i[contains(@class, "tooltip-icon")]',
                $name
            )
        );
        // phpcs:enable

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
            return $context->getSession()->getPage()->find('css', '.popover-body');
        });

        self::assertNotFalse($popup, 'Popup not found on page');
        $message = $popup->getText();
        $popup->find('css', 'i.popover-close')->click();

        static::assertStringContainsString($title, $message, \sprintf(
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
     */
    public function shouldNotSeeValueInElementOptions($value, $elementName)
    {
        static::assertFalse(in_array($value, $this->getOptionsForElement($elementName), true));
    }

    /**
     * @Then /^I should see (?P<counterValue>\d+) for "(?P<counterType>[\w\s]+)" counter$/
     *
     * @param string $counterType
     * @param int    $counterValue
     */
    public function iShouldSeeCounterValue($counterType, $counterValue)
    {
        $counterElement = $this->createElement(sprintf('%s Counter', $counterType));

        static::assertEquals(
            $counterValue,
            $counterElement->getText(),
            sprintf('Counter value "%s" doesn\'t match expected "%s"', $counterValue, $counterElement->getText())
        );
    }

    /**
     * @Given /^(?:|I )am on Content Node page and added Product Collection variant$/
     */
    public function iAmOnContentNodePageAndAddedProductCollectionVariant()
    {
        $this->oroMainContext->iOpenTheMenuAndClick('Marketing/Web Catalogs');
        $this->waitForAjax();
        $this->gridContext->clickActionInRow('Default Web Catalog', 'Edit Content Tree');
        $this->waitForAjax();
        $this->oroMainContext->iClickOn('Show Variants Dropdown');
        $this->waitForAjax();
        $this->oroMainContext->pressButton('Add Product Collection');
        $this->waitForAjax();
        $this->oroMainContext->pressButton('Content Variants');
        $this->waitForAjax();
        $this->oroMainContext->assertPageContainsNumElements(1, 'Product Collection Variant Label');
    }

    /**
     * @Given /^(?:|I )set "Mass action limit" in Product Collections settings to the "(?P<limit>[^"]+)"$/
     *
     * @param string $limit
     */
    public function iSetMassActionLimitInProductCollectionsSettings($limit)
    {
        $this->iOnProductCollectionsSettingsPage();
        $this->waitForAjax();
        $this->formContext->uncheckUseDefaultForField('Mass action limit', 'Use default');
        $this->waitForAjax();
        $this->oroMainContext->fillField('Mass action limit', $limit);
        $this->waitForAjax();
        $this->oroMainContext->pressButton('Save settings');
        $this->waitForAjax();
        $this->oroMainContext->iShouldSeeFlashMessage('Configuration saved');
    }

    /**
     * @Given /^I am on Product Collections settings page$/
     */
    public function iOnProductCollectionsSettingsPage()
    {
        $this->oroMainContext->iOpenTheMenuAndClick('System/Configuration');
        $this->waitForAjax();
        $this->configContext->followLinkOnConfigurationSidebar('Commerce/Product/Product Collections');
    }

    /**
     * @Given /^I have all products available in (?P<tab>[\s\w]+) tab, and focused on it$/
     */
    public function iHaveAllProductsAvailableInTabAndFocusedOnIt($tab)
    {
        $this->oroMainContext->pressButton($tab);
        $this->oroMainContext->pressButton('Add Button');
        $this->waitForAjax();
        $this->gridContext->iCheckAllRecordsInGrid('Add Products Popup');
        $this->oroMainContext->iClickOnSmthInElement('Add', 'UiDialog ActionPanel');
        $this->waitForAjax();
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
     * Example: Then I should see "Wide Template" with "Product Group" containing data:
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
     * Example: Then I should see the following prices on "Wide Template":
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
        return $this->getAppContainer()->get('router')->generate($route, $params);
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getAppContainer()
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
            sprintf('Tag "%s" inside element "%s" is found', $tag, $element)
        );
    }

    /**
     * @Given /^"([^"]*)" option for related products is enabled$/
     * @param string $option
     */
    public function optionForRelatedProductsIsEnabled($option)
    {
        switch ($option) {
            case 'Enable Related Products':
                $option = 'oro_product.related_products_enabled';
                break;
            case 'Maximum Number Of Assigned Items':
                $option = 'oro_product.max_number_of_related_products';
                break;
            case 'Assign In Both Directions':
                $option = 'oro_product.related_products_bidirectional';
                break;
            default:
                throw new \InvalidArgumentException(sprintf('There is no mapping to `%s` options', $option));
        }

        $configManager = $this->getAppContainer()->get('oro_config.global');
        $configManager->set($option, 1);
        $configManager->flush();
    }

    /**
     * @Then /^(?:|I )should see "(?P<elementNameOrText>[^"]*)" for "(?P<SKU>[^"]*)" product$/
     */
    public function shouldSeeForProduct($elementNameOrText, $SKU)
    {
        $productItem = $this->findProductItem($SKU);

        if ($this->isElementVisible($elementNameOrText, $productItem)) {
            return;
        }

        self::assertNotFalse(
            stripos($productItem->getText(), $elementNameOrText),
            sprintf(
                '%s "%s" for product with SKU "%s" is not present or not visible',
                $this->hasElement($elementNameOrText) ? 'Element' : 'Text',
                $elementNameOrText,
                $SKU
            )
        );
    }

    /**
     * @Then /^(?:|I )should not see "(?P<elementNameOrText>[^"]*)" for "(?P<SKU>[^"]*)" product$/
     */
    public function shouldNotSeeForProduct($elementNameOrText, $SKU)
    {
        $productItem = $this->findProductItem($SKU);

        $textAndElementPresentedOnPage = $this->isElementVisible($elementNameOrText, $productItem)
            || stripos($productItem->getText(), $elementNameOrText);

        self::assertFalse(
            $textAndElementPresentedOnPage,
            sprintf(
                '%s "%s" for product with SKU "%s" is present or visible',
                $this->hasElement($elementNameOrText) ? 'Element' : 'Text',
                $elementNameOrText,
                $SKU
            )
        );
    }

    /**
     * phpcs:disable
     * Example: I should see product unit selector as "single" for product with SKU "SKU1"
     * @Then /^(?:|I )should see product unit selector as "(?P<selectorType>[^"]*)" for product with SKU "(?P<SKU>[^"]*)"$/
     * phpcs:enable
     */
    public function shouldSeeForProductUnit($selectorType, $sku)
    {
        $productItem = $this->findProductItem($sku);

        self::assertTrue($productItem->isVisible());

        $unitSelector = $productItem->getElement('ProductUnitSelector');

        self::assertTrue($unitSelector->isVisible());
        self::assertEquals($selectorType, $unitSelector->getSelectorType());
    }

    /**
     * Example: I set product unit selector as "each" for product with SKU "SKU1"
     * @When /^(?:|I )set product unit selector as "(?P<selectorType>[^"]*)" for product with SKU "(?P<SKU>[^"]*)"$/
    */
    public function setProductUnitForProduct($selectorType, $sku): void
    {
        $productItem = $this->findProductItem($sku);

        self::assertTrue($productItem->isVisible());

        /**
         * @var ProductUnitSelector $unitSelector
         */
        $unitSelector = $productItem->getElement('ProductUnitSelector');

        $availableValues = $unitSelector->getValues();

        static::assertTrue(
            in_array($selectorType, $availableValues),
            sprintf('Product unit %s is not available', $selectorType)
        );

        $unitSelector->setValue($selectorType);
    }

    /**
     * Example: I should see that the "Product Kit Line Item Totals Form Unit" element has a selected unit "set"
     * @Then /^(?:|I )should see that the "(?P<elementName>[^"]*)" element has a selected unit "(?P<unit>[^"]*)"$/
     */
    public function shouldSeeThatElementHasSelectedProductUnit($elementName, $unit)
    {
        $productUnit = $this->createElement($elementName);

        $value = $productUnit->getAttribute('type') == 'hidden'
            ? $productUnit->getParent()->find('css', '[data-toggle-type]')->getText()
            : $productUnit->getValue();
        self::assertEquals($unit, $value);
    }

    /**
     * Example: I should see that the "Product Kit Line Item Totals Form Unit" element has available units:
     *  |   item    |
     *  |   set     |
     * @Then /^(?:|I )should see that the "(?P<elementName>[^"]*)" element has available units:$/
     */
    public function shouldSeeThatElementContainsProductUnits($elementName, TableNode $table)
    {
        $productUnit = $this->createElement($elementName);
        $actualOptions = $productUnit->getValues();

        $expectedOptions = array_map(function (array $row) {
            list($value) = $row;

            return $value;
        }, $table->getRows());

        self::assertEqualsCanonicalizing($expectedOptions, $actualOptions);
    }

    /**
     * phpcs:disable
     * Example: I should see that the "Product Kit Line Item Totals Form Unit" element has a product unit selector of type "single"
     * @Then /^(?:|I )should see that the "(?P<elementName>[^"]*)" element has a product unit selector of type "(?P<selectorType>[^"]*)"$/
     * phpcs:enable
     */
    public function shouldSeeThatElementHasProductUnitSelectorOfType($elementName, $unit)
    {
        $productUnit = $this->createElement($elementName);
        self::assertEquals($unit, $productUnit->getSelectorType());
    }

    /**
     * Example: I should see "This product will be available later" for "SKU123" product on shopping list
     * @Then /^(?:|I )should see "(?P<elementNameOrText>[^"]*)" for "(?P<SKU>[^"]*)" product on shopping list$/
     */
    public function shouldSeeForProductInShoppingList($elementNameOrText, $SKU)
    {
        $productItem = $this->findProductItemInShoppingList($SKU);

        if ($this->isElementVisible($elementNameOrText, $productItem)) {
            return;
        }

        self::assertNotFalse(
            stripos($productItem->getText(), $elementNameOrText),
            sprintf(
                '%s "%s" for product with SKU "%s" is not present or not visible',
                $this->hasElement($elementNameOrText) ? 'Element' : 'Text',
                $elementNameOrText,
                $SKU
            )
        );
    }

    /**
     * Example: I should not see "This product will be available later" for "SKU123" product on shopping list
     * @Then /^(?:|I )should not see "(?P<elementNameOrText>[^"]*)" for "(?P<SKU>[^"]*)" product on shopping list$/
     */
    public function shouldNotSeeForProductInShoppingList($elementNameOrText, $SKU)
    {
        $productItem = $this->findProductItemInShoppingList($SKU);

        $textAndElementPresentedOnPage = $this->isElementVisible($elementNameOrText, $productItem)
            || stripos($productItem->getText(), $elementNameOrText);

        self::assertFalse(
            $textAndElementPresentedOnPage,
            sprintf(
                '%s "%s" for product with SKU "%s" is present or visible',
                $this->hasElement($elementNameOrText) ? 'Element' : 'Text',
                $elementNameOrText,
                $SKU
            )
        );
    }

    /**
     * phpcs:disable
     *
     * Example: I should see notification "This item is running low on inventory" for "SKU123" product with Unit of Quantity "item" in order
     * @Then /^(?:|I )should see notification "(?P<elementNameOrText>[^"]*)" for "(?P<SKU>[^"]*)" product with Unit of Quantity "(?P<unit>[^"]*)" in order$/
     *
     * phpcs:enable
     */
    public function shouldSeeNotificationForProductWithUnitInOrder($elementNameOrText, $SKU, $unit): void
    {
        $selector = sprintf(
            "//*[contains(text(), '%s')]/ancestor::tr//" .
            "td[contains(@class, 'grid-body-cell-unit') and contains(text(), '%s')]/ancestor::tr/" .
            "following-sibling::tr[contains(@class, 'notification-row') and position()=1]",
            $SKU,
            $unit
        );

        $notificationRow = $this->getSession()->getPage()->find('xpath', $selector);

        self::assertNotNull($notificationRow, sprintf('notifications for the line item with SKU "%s" not found', $SKU));

        if ($this->isElementVisible($elementNameOrText, $notificationRow)) {
            return;
        }

        self::assertNotFalse(
            stripos($notificationRow->getText(), $elementNameOrText),
            sprintf(
                '%s "%s" for product with SKU "%s" and Unit of Quantity "%s" is not present or not visible',
                $this->hasElement($elementNameOrText) ? 'Element' : 'Text',
                $elementNameOrText,
                $SKU,
                $unit
            )
        );
    }

    /**
     * phpcs:disable
     *
     * Example: I should not see "This item is running low on inventory" for "SKU123" product with Unit of Quantity "item" in order
     * @Then /^(?:|I )should not see "(?P<elementNameOrText>[^"]*)" for "(?P<SKU>[^"]*)" product with Unit of Quantity "(?P<unit>[^"]*)" in order$/
     *
     * phpcs:enable
     */
    public function shouldNotSeeNotificationForProductWithUnitInOrder($elementNameOrText, $SKU, $unit): void
    {
        $selector = sprintf(
            "//*[contains(text(), '%s')]/ancestor::tr//" .
            "td[contains(@class, 'grid-body-cell-unit') and contains(text(), '%s')]/ancestor::tr/" .
            "following-sibling::tr[contains(@class, 'notification-row') and position()=1]",
            $SKU,
            $unit
        );

        $notificationRow = $this->getSession()->getPage()->find('xpath', $selector);

        $textAndElementPresentedOnPage = $this->isElementVisible($elementNameOrText, $notificationRow)
            || ($notificationRow && stripos($notificationRow->getText(), $elementNameOrText));

        self::assertFalse(
            $textAndElementPresentedOnPage,
            sprintf(
                '%s "%s" for product with SKU "%s" is present or visible',
                $this->hasElement($elementNameOrText) ? 'Element' : 'Text',
                $elementNameOrText,
                $SKU
            )
        );
    }

    /**
     * phpcs:disable
     *
     * Example: I should see notification "This item is running low on inventory" for "SKU123" product with Unit of Quantity "item" in shopping list
     * @Then /^(?:|I )should see notification "(?P<elementNameOrText>[^"]*)" for "(?P<SKU>[^"]*)" product with Unit of Quantity "(?P<unit>[^"]*)" in shopping list$/
     *
     * phpcs:enable
     */
    public function shouldSeeNotificationForProductWithUnitInShoppingList($elementNameOrText, $SKU, $unit): void
    {
        $selectedUnitXpath = sprintf(
            './/*[contains(@class, "select") and contains(text(), "%1$s")]' .
            ' or .//input[@type="radio" and @checked]/following-sibling::label[1][contains(text(), "%1$s")]' .
            ' or .//span[contains(@class, "single-unit") and contains(text(), "%1$s")]',
            $unit
        );

        $rowSelector = sprintf(
            '//*[contains(concat(" ", normalize-space(@class), " "), " grid-row ") and ' .
            './/*[contains(concat(" ", normalize-space(text()), " "), " %s ")]' .
                ' and .//*[' .
                    'contains(concat(" ", normalize-space(@class), " "), " unit-select-root ")' .
                    ' and (%s)' .
                ']' .
            ']',
            $SKU,
            $selectedUnitXpath
        );

        $productItem = $this->getSession()->getPage()->find('xpath', $rowSelector);
        self::assertNotNull($productItem, sprintf('Product with SKU "%s" and Unit "%s" not found', $SKU, $unit));

        $idNotificationRow = $productItem->getAttribute('data-related-row');

        $notificationRowSelector = sprintf(
            '//*[contains(concat(" ", normalize-space(@class), " "), " notification-row ") and @data-row-id="%s"]',
            $idNotificationRow
        );

        $notificationRow = $this->getSession()->getPage()->find('xpath', $notificationRowSelector);
        self::assertNotNull($notificationRow, sprintf('Notifications for the line item with SKU "%s" not found', $SKU));

        if ($this->isElementVisible($elementNameOrText, $notificationRow)) {
            return;
        }

        self::assertStringContainsStringIgnoringCase(
            $elementNameOrText,
            $notificationRow->getText(),
            sprintf(
                '"%s" for product with SKU "%s" and Unit of Quantity "%s" is not present or not visible',
                $elementNameOrText,
                $SKU,
                $unit
            )
        );
    }

    /**
     * phpcs:disable
     *
     * Example: I should not see notification "This item is running low on inventory" for "SKU123" product with Unit of Quantity "item" in shopping list
     * @Then /^(?:|I )should not see notification "(?P<elementNameOrText>[^"]*)" for "(?P<SKU>[^"]*)" product with Unit of Quantity "(?P<unit>[^"]*)" in shopping list$/
     *
     * phpcs:enable
     */
    public function shouldNotSeeNotificationForProductWithUnitInShoppingList($elementNameOrText, $SKU, $unit): void
    {
        $selector = sprintf(
            "//*[contains(text(), '%s')]/ancestor::tr//td[contains(@class, 'grid-body-cell-quantity')]//" .
            "div[(contains(@class, 'select') and contains(text(), '%s')) or " .
            "(input[@type='radio' and @checked]/following-sibling::label[1][contains(text(), '%s')]) or ".
            "(span[contains(@class, 'single-unit') and contains(text(), '%s')])]" .
            "/ancestor::tr/following-sibling::tr[contains(@class, 'notification-row') and position()=1]",
            $SKU,
            $unit,
            $unit,
            $unit
        );

        $notificationRow = $this->getSession()->getPage()->find('xpath', $selector);

        $textAndElementPresentedOnPage = $this->isElementVisible($elementNameOrText, $notificationRow)
            || ($notificationRow && stripos($notificationRow->getText(), $elementNameOrText));

        self::assertFalse(
            $textAndElementPresentedOnPage,
            sprintf(
                '%s "%s" for product with SKU "%s" is present or visible',
                $this->hasElement($elementNameOrText) ? 'Element' : 'Text',
                $elementNameOrText,
                $SKU
            )
        );
    }

    /**
     * Assert that embedded block contains specified product with specified element.
     * Example: Then should see "Low Inventory" for "PSKU1" product in the "New Arrivals Block"
     *
     * @Then /^(?:|I )should see "(?P<elementName>[^"]*)" for "(?P<SKU>[^"]*)" product in the "(?P<blockName>[^"]+)"$/
     */
    public function iShouldSeeElementForTheFollowingProductsInEmbeddedBlock($elementName, $SKU, $blockName)
    {
        $block = $this->createElement($blockName);
        self::assertTrue($block->isValid(), sprintf('Embedded block "%s" was not found', $blockName));

        $productItem = $this->findProductItem($SKU, $block);

        if ($this->isElementVisible($elementName, $productItem)) {
            return;
        }

        self::assertNotFalse(
            stripos($productItem->getText(), $elementName),
            sprintf('text or element "%s" for product with SKU "%s" is not present or not visible', $elementName, $SKU)
        );
    }

    /**
     * Assert that embedded block does not contain specified product with specified element.
     * Example: Then should not see "Low Inventory" for "PSKU1" product in the "New Arrivals Block"
     *
     * @Then /^(?:|I )should not see "(?P<element>[^"]*)" for "(?P<SKU>[^"]*)" product in the "(?P<blockName>[^"]+)"$/
     */
    public function iShouldNotSeeElementForTheFollowingProductsInEmbeddedBlock($element, $SKU, $blockName)
    {
        $block = $this->createElement($blockName);
        self::assertTrue($block->isValid(), sprintf('Embedded block "%s" was not found', $blockName));

        $productItem = $this->findProductItem($SKU, $block);

        $textAndElementPresentedOnPage = $this->isElementVisible($element, $productItem)
            || stripos($productItem->getText(), $element);

        self::assertFalse(
            $textAndElementPresentedOnPage,
            sprintf('text or element "%s" for product with SKU "%s" is present or visible', $element, $SKU)
        );
    }

    /**
     * @Then /^(?:|I )should see "(?P<elementName>[^"]*)" for "(?P<SKU>[^"]*)" line item "(?P<element>[^"]*)"$/
     */
    public function shouldSeeForLineItem($elementName, $SKU, $element)
    {
        $productItem = $this->findElementContains($element, $SKU);
        self::assertNotNull($productItem, sprintf('line item with SKU "%s" not found', $SKU));

        if ($this->isElementVisible($elementName, $productItem)) {
            return;
        }

        self::assertNotFalse(
            stripos($productItem->getText(), $elementName),
            sprintf(
                'text or element "%s" for line item with SKU "%s" is not present or not visible',
                $elementName,
                $SKU
            )
        );
    }

    /**
     * phpcs:disable
     *
     * @Then /^(?:|I )should see notification "(?P<elementName>[^"]*)" for "(?P<SKU>[^"]*)" line item "(?P<element>[^"]*)"$/
     *
     * phpcs:enable
     */
    public function shouldSeeNotificationForLineItem($elementName, $SKU, $element): void
    {
        $productItem = $this->findElementContains($element, $SKU);
        self::assertNotNull($productItem, sprintf('line item with SKU "%s" not found', $SKU));

        $notificationRow = $this->getSession()
            ->getPage()
            ->find(
                'xpath',
                sprintf(
                    "(%s)/following-sibling::tr[contains(@class, 'notification-row') and position()=1]",
                    $productItem->getXpath()
                )
            );

        self::assertNotNull($notificationRow, sprintf('notifications for the line item with SKU "%s" not found', $SKU));

        if ($this->isElementVisible($elementName, $notificationRow)) {
            return;
        }

        self::assertNotFalse(
            stripos($notificationRow->getText(), $elementName),
            sprintf(
                'text or element "%s" for line item with SKU "%s" is not present or not visible',
                $elementName,
                $SKU
            )
        );
    }

    /**
     * @Then /^(?:|I )should not see "(?P<elementName>[^"]*)" for "(?P<SKU>[^"]*)" line item "(?P<element>[^"]*)"$/
     */
    public function shouldNotSeeForLineItem($elementName, $SKU, $element)
    {
        $productItem = $this->findElementContains($element, $SKU);
        self::assertNotNull($productItem, sprintf('line item with SKU "%s" not found', $SKU));

        $textAndElementPresentedOnPage = $this->isElementVisible($elementName, $productItem)
            || stripos($productItem->getText(), $elementName);

        self::assertFalse(
            $textAndElementPresentedOnPage,
            sprintf('text or element "%s" for line item with SKU "%s" is present or visible', $elementName, $SKU)
        );
    }

    /**
     * phpcs:disable
     *
     * @Then /^(?:|I )should not see notification "(?P<elementName>[^"]*)" for "(?P<SKU>[^"]*)" line item "(?P<element>[^"]*)"$/
     *
     * phpcs:enable
     */
    public function shouldNotSeeNotificationForLineItem($elementName, $SKU, $element): void
    {
        $productItem = $this->findElementContains($element, $SKU);
        self::assertNotNull($productItem, sprintf('line item with SKU "%s" not found', $SKU));

        $notificationRow = $this->getSession()
            ->getPage()
            ->find(
                'xpath',
                sprintf(
                    "(%s)/following-sibling::tr[contains(@class, 'notification-row') and position()=1]",
                    $productItem->getXpath()
                )
            );

        $textAndElementPresentedOnPage = $this->isElementVisible($elementName, $notificationRow)
            || ($notificationRow && stripos($notificationRow->getText(), $elementName));

        self::assertFalse(
            $textAndElementPresentedOnPage,
            sprintf('text or element "%s" for line item with SKU "%s" is present or visible', $elementName, $SKU)
        );
    }

    /**
     * @Then /^(?:|I )click "([^"]*)" for "([^"]*)" product$/
     */
    public function clickElementForSelectedProduct($elementName, $SKU)
    {
        $productItem = $this->findProductItem($SKU);
        $element = $productItem->getElement($elementName);
        self::assertTrue($element->isVisible(), sprintf("Can't find element '%s' for SKU '%s'", $elementName, $SKU));
        $this->oroMainContext->scrollToXpath($element->getXpath());
        $element->focus();
        $element->click();
    }

    /**
     * @When /^grid sorter should have "(?P<field>.*)" options$/
     */
    public function sorterShouldHave($field)
    {
        $sorter = $this->createElement('Frontend Product Grid Sorter');
        $options = $sorter->find('xpath', sprintf('//option[contains(., "%s")]', $field));

        self::assertNotEmpty($options, sprintf('No sorter options found for field "%s"', $field));
    }

    /**
     * @Given /^(?:|I )should see "([^"]*)" product$/
     */
    public function iShouldSeeInSearchResults($productSku)
    {
        $this->oroMainContext
            ->iShouldSeeStringInElementUnderElements($productSku, 'ProductFrontendRowSku', 'ProductFrontendRow');
    }

    /**
     * @Given /^(?:|I )should see "([^"]*)" featured product$/
     */
    public function iShouldSeeFeaturedProduct($productSku)
    {
        $this->oroMainContext
            ->iShouldSeeStringInElementUnderElements($productSku, 'ProductFrontendRowSku', 'Featured Products Block');
    }

    /**
     * @Then /^(?:|I )should not see "([^"]*)" product$/
     *
     * @param string $productSku
     */
    public function iShouldNotSeeInSearchResults($productSku)
    {
        $this->oroMainContext
            ->iShouldNotSeeStringInElementUnderElements($productSku, 'ProductFrontendRowSku', 'ProductFrontendRow');
    }

    /**
     * @Then /^I should see "(?P<text>(?:[^"]|\\")*)" in related products$/
     */
    public function iShouldSeeInRelatedProducts($string)
    {
        $this->oroMainContext
            ->iShouldSeeStringInElementUnderElements($string, 'ProductRelatedItem', 'ProductRelatedProducts');
    }

    /**
     * @Then /^I should see "(?P<text>(?:[^"]|\\")*)" in upsell products$/
     */
    public function iShouldSeeInUpsellProducts($string)
    {
        $this->oroMainContext
            ->iShouldSeeStringInElementUnderElements($string, 'ProductRelatedItem', 'ProductUpsellProducts');
    }

    /**
     * @Then /^I should not see "(?P<text>(?:[^"]|\\")*)" in related products$/
     */
    public function iShouldNotSeeInRelatedProducts($string)
    {
        $this->oroMainContext
            ->iShouldNotSeeStringInElementUnderElements($string, 'ProductRelatedItem', 'ProductRelatedProducts');
    }

    /**
     * @Then /^I should not see "(?P<text>(?:[^"]|\\")*)" in upsell products$/
     */
    public function iShouldNotSeeInUpsellProducts($string)
    {
        $this->oroMainContext
            ->iShouldNotSeeStringInElementUnderElements($string, 'ProductRelatedItem', 'ProductUpsellProducts');
    }

    /**
     * Assert that embedded block contains specified products.
     * Example: Then should see the following products in the "New Arrivals Block":
     *            | SKU  | Product Price Your | Product Price Listed |
     *            | SKU1 | $1 / each          | $2 / each            |
     *            | SKU2 | $2 / each          | $3 / each            |
     *
     * @Then /^(?:|I )should see the following products in the "(?P<blockName>[^"]+)":$/
     */
    public function iShouldSeeFollowingProductsInEmbeddedBlock(TableNode $table, $blockName)
    {
        $block = $this->createElement($blockName);
        self::assertTrue($block->isValid(), sprintf('Embedded block "%s" was not found', $blockName));

        foreach ($table as $row) {
            $skuOrTitleKey = key($row);
            $skuOrTitle = $row[$skuOrTitleKey];
            $productItem = $this->findElementContains('EmbeddedProduct', $skuOrTitle, $block);
            self::assertTrue($productItem->isIsset(), sprintf('Product "%s" was not found', $skuOrTitle));

            unset($row[$skuOrTitleKey]);
            foreach ($row as $elementName => $expectedValue) {
                $element = $this->createElement($elementName, $productItem);
                self::assertTrue(
                    $element->isIsset(),
                    sprintf('Element "%s" in product "%s" was not found', $elementName, $skuOrTitle)
                );

                static::assertStringContainsString(
                    $expectedValue,
                    $element->getText(),
                    \sprintf(
                        'Element "%s" in product "%s" does not contains text: %s',
                        $elementName,
                        $skuOrTitle,
                        $expectedValue
                    )
                );
            }
        }
    }

    /**
     * Assert that embedded block does not contain specified products.
     * Example: Then should see the following products in the "New Arrivals Block":
     *            | SKU  |
     *            | SKU1 |
     *            | SKU2 |
     *
     * @Then /^(?:|I )should not see the following products in the "(?P<blockName>[^"]+)":$/
     */
    public function iShouldNotSeeFollowingProductsInEmbeddedBlock(TableNode $table, $blockName)
    {
        $block = $this->createElement($blockName);
        self::assertTrue($block->isValid(), sprintf('Embedded block "%s" was not found', $blockName));

        foreach ($table as $row) {
            foreach ($row as $rowName => $rowValue) {
                $productItem = $this->findElementContains('EmbeddedProduct', $rowValue, $block);
                self::assertFalse($productItem->isIsset(), sprintf('Product "%s" should not be present', $rowValue));
            }
        }
    }

    /**
     * Assert that embedded block contains specified products with specified sticker.
     * Example: Then should see "New Arrival Sticker" for the following products in the "Featured Products Block":
     *            | SKU  |
     *            | SKU1 |
     *            | SKU2 |
     *
     * @Then /^(?:|I )should see "(?P<sticker>[^"]+)" for the following products in the "(?P<blockName>[^"]+)":$/
     */
    public function iShouldSeeStickerForFollowingProductsInEmbeddedBlock(TableNode $table, $sticker, $blockName)
    {
        $block = $this->createElement($blockName);
        self::assertTrue($block->isValid(), sprintf('Embedded block "%s" was not found', $blockName));

        foreach ($table as $row) {
            $embeddedProduct = $this->findElementContains('EmbeddedProduct', $row['SKU'], $block);
            self::assertTrue($embeddedProduct->isIsset(), sprintf('Product "%s" is not present', $row['SKU']));

            $stickerElement = $this->createElement($sticker, $embeddedProduct);
            self::assertTrue($stickerElement->isIsset());
        }
    }

    /**
     * Assert that embedded block contains specified products without specified sticker.
     * Example: Then should not see "New Arrival Sticker" for the following products in the "Featured Products Block":
     *            | SKU  |
     *            | SKU1 |
     *            | SKU2 |
     *
     * @Then /^(?:|I )should not see "(?P<sticker>[^"]+)" for the following products in the "(?P<blockName>[^"]+)":$/
     */
    public function iShouldNotSeeStickerForFollowingProductsInEmbeddedBlock(TableNode $table, $sticker, $blockName)
    {
        $block = $this->createElement($blockName);
        self::assertTrue($block->isValid(), sprintf('Embedded block "%s" was not found', $blockName));

        foreach ($table as $row) {
            $embeddedProduct = $this->findElementContains('EmbeddedProduct', $row['SKU'], $block);
            self::assertTrue($embeddedProduct->isIsset(), sprintf('Product "%s" is not present', $row['SKU']));

            $stickerElement = $this->createElement($sticker, $embeddedProduct);
            self::assertFalse($stickerElement->isIsset());
        }
    }

    /**
     * Example: I remember "listed" image resized ID
     *
     * @Then /^I remember "(?P<imageType>[^"]*)" image resized ID$/
     * @param string $imageType
     */
    public function iRememberResizedImageId($imageType)
    {
        $imageSrc = $this->getProductImageSrc($imageType);

        preg_match('/\/media\/cache\/attachment\/resize\/\d+\/\d+\/\d+\/(.+)\.\w+/', $imageSrc, $matches);
        self::assertNotEmpty($matches[1], sprintf('Image ID not found for "%s" image', $imageType));

        $this->rememberedData[$imageType] = $matches[1];
    }

    /**
     * Example: I remember "listed" image filtered ID
     *
     * @Then /^I remember "(?P<imageType>[^"]*)" image filtered ID$/
     * @param string $imageType
     */
    public function iRememberFilteredImageId($imageType)
    {
        $imageSrc = $this->getProductImageSrc($imageType);

        preg_match('/\/media\/cache\/attachment\/filter\/[^\/]+\/[^\/]+\/\d+\/(.+)\.\w+/', $imageSrc, $matches);
        self::assertNotEmpty($matches[1], sprintf('Image ID not found for "%s" image', $imageType));

        $this->rememberedData[$imageType] = $matches[1];
    }

    /**
     * Gets image src from product image collection on product edit form.
     *
     * @param string $imageType Product image type, e.g. main, listing, additional
     */
    private function getProductImageSrc(string $imageType): string
    {
        $form = $this->createElement('OroForm');
        $image = $form->find(
            'xpath',
            sprintf(
                '//input[@type="radio"][contains(@name, "images")][contains(@name, "%s")][@checked="checked"]'
                . '/ancestor::tr/descendant::img',
                $imageType
            )
        );
        self::assertNotEmpty($image, sprintf('Image with type "%s" not found on page', $imageType));

        return $image->getAttribute('src');
    }

    /**
     * Example: I remember images order in "Product Images" element
     *
     * @Then /^I remember images order in "(?P<elementName>[^"]*)" element$/
     * @param string $elementName
     */
    public function iRememberImagesOrderInElement($elementName)
    {
        $this->rememberedData[self::IMAGES_ORDER_REMEMBER_KEY] = $this->getImagesOrderInElement($elementName);
    }

    /**
     * Example: I should see images in "Product Images" element
     *
     * @Then /^I should see images in "(?P<elementName>[^"]*)" element$/
     * @param string $elementName
     */
    public function iShouldSeeImagesInElement($elementName)
    {
        $element = $this->createElement($elementName);
        $images = $element->findAll('xpath', '//img');

        self::assertNotEmpty($images, sprintf('Images not found in the "%s" element', $elementName));
    }

    /**
     * Example: I should see images in "Product Images" element in remembered order
     *
     * @Then /^I should see images in "(?P<elementName>[^"]*)" element in remembered order$/
     * @param string $elementName
     */
    public function iShouldSeeImagesInElementInRememberedOrder($elementName)
    {
        $rememberedOrder = $this->rememberedData[self::IMAGES_ORDER_REMEMBER_KEY];
        self::assertNotEmpty($rememberedOrder, 'No remembered images order');
        $currentOrder = $this->getImagesOrderInElement($elementName);
        $rememberedOrder = array_values(array_intersect($rememberedOrder, $currentOrder));
        self::assertSame($currentOrder, $rememberedOrder, 'Images order differs from remembered');
    }

    /**
     * @param string $elementName
     * @return array
     */
    private function getImagesOrderInElement($elementName): array
    {
        $this->waitForImagesToLoad();
        $element = $this->createElement($elementName);
        $images = $element->findAll('xpath', '//img');
        $pattern = '/\/media\/cache\/attachment[\/\w]+\/(.+?)(?:\.\w+)+/';
        $attributeToParse = 'src';

        if (!$images) {
            $images = $element->findAll('xpath', '//a');
            $pattern = '/\/attachment\/download\/\d+\/(.+?)(?:\.\w+)+/';
            $attributeToParse = 'href';
        }

        $imagesOrder = [];
        /** @var NodeElement $image */
        foreach ($images as $image) {
            $imageSrc = $image->getAttribute($attributeToParse);
            $matches = [];
            preg_match($pattern, $imageSrc, $matches);

            $imagesOrder[] = $matches[1];
        }

        self::assertNotEmpty($imagesOrder, sprintf('Images not found in the "%s" element', $elementName));

        return $imagesOrder;
    }

    /**
     * Images to get loaded (since they are loaded lazily)
     * @throws AssertionFailedError
     * @param int $time Time should be in milliseconds
     * @return bool
     */
    private function waitForImagesToLoad($time = 5000)
    {
        $result = $this->getSession()->getDriver()
            ->wait($time, "0 === document.querySelectorAll('.slick-loading').length");

        if (!$result) {
            self::fail(sprintf('Waited for images to load more than %d seconds', $time / 1000));
        }

        return $result;
    }

    /**
     * @Given /^(?:|I )wait popup widget is initialized$/
     */
    public function iWaitPopupWidgetIsInitialized()
    {
        $this->getSession()->getDriver()->wait(5000, "0 !== $('.slick-track .slick-active img[src]').length");
    }

    /**
     * @param string $content
     * @return NodeElement
     */
    private function getImageCell($content)
    {
        /** @var Grid $grid */
        $grid = $this->elementFactory->createElement('Grid');
        self::assertTrue($grid->isIsset(), 'Element "Grid" not found on the page');

        return $grid->getRowByContent($content)
            ->getCellByHeader('image');
    }

    /**
     * @param string $imageType
     * @param string $content
     * @return NodeElement|mixed|null
     */
    private function getImageForProductInGrid($imageType, $content)
    {
        $imageId = $this->rememberedData[$imageType] ?? '';
        self::assertNotEmpty($imageId, sprintf('No remembered image ID for "%s" image type', $imageType));

        return $this->getImageCell($content)
            ->find(
                'xpath',
                sprintf('//img[contains(@class, "thumbnail")][contains(@src, "%s")]', $imageId)
            );
    }

    /**
     * Example: I should not see remembered "listing" image for product with "SKU123"
     *
     * @Then /^I should not see remembered "(?P<imageType>[^"]*)" image for product with "(?P<content>[^"]*)"/
     * @param string $imageType
     * @param string $content
     */
    public function iShouldNotSeeRememberImageIdOnBackendProductsGrid($imageType, $content)
    {
        $image = $this->getImageForProductInGrid($imageType, $content);

        self::assertEmpty($image, sprintf('Image "%s" found for product with "%s"', $imageType, $content));
    }

    /**
     * Example: I should see remembered "main" image for product with "SKU123"
     *
     * @Then /^I should see remembered "(?P<imageType>[^"]*)" image for product with "(?P<content>[^"]*)"/
     * @param string $imageType
     * @param string $content
     */
    public function iShouldSeeRememberImageIdOnBackendProductsGrid($imageType, $content)
    {
        $image = $this->getImageForProductInGrid($imageType, $content);

        self::assertNotEmpty($image, sprintf('No image "%s" found for product with "%s"', $imageType, $content));

        $response = $this->loadImage($image->getAttribute('src'));

        self::assertEquals(
            200,
            $response->getStatusCode(),
            sprintf(
                'Expected "200" status code, got "%s" when requested the url "%s" of image "%s" for product with "%s"',
                $response->getStatusCode(),
                $image->getAttribute('src'),
                $imageType,
                $content
            )
        );

        $this->attachmentImageContext->iShouldSeePictureElement($image->getParent());
    }

    /**
     * Example: I should see remembered "listing" image in "Product Form Images" element
     *
     * @Then /^I should see remembered "(?P<imageType>[^"]*)" image in "(?P<elementName>[^"]*)" element$/
     * @param string $imageType
     * @param string $elementName
     */
    public function iShouldSeeRememberedImageIdInElement($imageType, $elementName)
    {
        $element = $this->createElement($elementName);

        $rememberedImageId = $this->rememberedData[$imageType] ?? '';
        self::assertNotEmpty($rememberedImageId, sprintf(
            'No remembered image ID for "%s" image type',
            $imageType
        ));

        $imageXPath = sprintf('//img[contains(@src, "%s")]', $rememberedImageId);
        $image = $this->spin(static fn (MinkAwareContext $context) => $element->find('xpath', $imageXPath), 5);

        self::assertNotEmpty($image, sprintf(
            'No image with id "%s" found in "%s"',
            $rememberedImageId,
            $elementName
        ));

        $response = $this->loadImage($image->getAttribute('src'));

        self::assertEquals(
            200,
            $response->getStatusCode(),
            sprintf(
                'Expected "200" status code, got "%s" when requested the url "%s" of image "%s" in element "%s"',
                $response->getStatusCode(),
                $image->getAttribute('src'),
                $imageType,
                $elementName
            )
        );
    }

    /**
     * Example: When I click on Image cell in grid row contains "Charlie"
     *
     * @Given /^(?:|I )click on Image cell in grid row contains "(?P<content>(?:[^"]|\\")*)"$/
     *
     * @param string $content
     */
    public function clickOnCell($content)
    {
        $this->getImageCell($content)
            ->find('xpath', '//a[contains(@class, "view-image")]')
            ->click();
    }

    /**
     * Example: When I click on dropdown element in grid row contains "Charlie"
     *
     * @Given /^(?:|I )click on "(?P<elementName>[\w\s]*)" element in grid row contains "(?P<content>(?:[^"]|\\")*)"$/
     */
    public function clickOnElementInCell(string $elementName, string $content)
    {
        /** @var Grid $grid */
        $grid = $this->elementFactory->createElement('Grid');
        self::assertTrue($grid->isIsset(), 'Element "Grid" not found on the page');

        $row = $grid->getRowByContent($content);

        if ($row) {
            $xpath = $this->elementFactory->createElement($elementName)->getXpath();

            $element = $row->find('xpath', $xpath);

            if ($element) {
                $element->click();
            }
        }
    }

    /**
     * Example: I should see remembered "main" image preview
     *
     * @Then /^(?:|I )should see remembered "(?P<imageType>[^"]*)" image preview$/
     *
     * @param string $imageType
     */
    public function iShouldSeeRememberedProductImagePreview($imageType)
    {
        $imageId = $this->rememberedData[$imageType] ?? '';
        self::assertNotEmpty($imageId, sprintf('No remembered image ID for "%s" image type', $imageType));

        $largeImage = $this->getSession()
            ->getPage()
            ->find(
                'xpath',
                sprintf('//img[contains(@class, "images-list__item")][contains(@src, "%s")]', $imageId)
            );

        self::assertNotNull($largeImage, 'Large image not visible');

        $this->attachmentImageContext->iShouldSeePictureElement($largeImage->getParent());
    }

    /**
     * Example: I should see remembered "listing" image in "Top Selling Items" section
     *
     * @Then /^I should see remembered "(?P<imageType>[^"]*)" image in "(?P<sectionName>[^"]*)" section$/
     * @param string $imageType
     * @param string $sectionName
     */
    public function iShouldSeeRememberImageId($imageType, $sectionName)
    {
        $section = $this->getSession()->getPage()->find(
            'xpath',
            sprintf('//h2[contains(.,"%s")]/../..', $sectionName)
        );
        self::assertNotEmpty($section, sprintf('Section "%s" not found on page', $sectionName));

        $rememberedImageId = isset($this->rememberedData[$imageType]) ? $this->rememberedData[$imageType] : '';
        self::assertNotEmpty($rememberedImageId, sprintf(
            'No remembered image ID for "%s" image type',
            $imageType
        ));

        $image = $section->find(
            'xpath',
            sprintf(
                '//img[contains(@class, "product-item__preview-image")][contains(@src, "%s")]',
                $rememberedImageId
            )
        );
        self::assertNotEmpty($image, sprintf(
            'No image with id "%s" found in section "%s"',
            $rememberedImageId,
            $sectionName
        ));
    }

    /**
     * Example: I should see preview image with alt "alt" for "SKU" product
     *
     * @Then /^(?:|I )should see preview image with alt "(?P<alt>(?:[^"]|\\")*)" for "(?P<SKU>[^"]*)" product$/
     * @Then /^(?:|I )should see preview image with alt "(?P<alt>(?:[^"]|\\")*)"$/
     *
     * @param string $alt
     * @param string|null $SKU
     */
    public function iShouldSeeImageWithAlt($alt, $SKU = null)
    {
        $alt = $this->fixStepArgument($alt);
        if ($SKU !== null) {
            $productItem = $this->findProductItem($SKU);
            $image = $this->createElement('Product Preview Image', $productItem);
        } else {
            $image = $this->createElement('Product Image (view page)');
        }

        self::assertEquals(
            $alt,
            $image->getAttribute('alt'),
            sprintf('Preview image with alt "%s" not found for product "%s"', $alt, $SKU)
        );
    }

    /**
     * Example: I should see picture for "SKU" product in the "New Arrivals Block"
     *
     * @Then /^(?:|I )should see picture for "(?P<SKU>[^"]*)" product in the "(?P<blockName>[^"]+)"$/
     * @Then /^(?:|I )should see product picture in the "(?P<blockName>[^"]+)"$/
     *
     * @param string $blockName
     * @param string|null $SKU
     */
    public function iShouldSeePictureForProductInBlock($blockName, $SKU = null): void
    {
        $block = $this->createElement($blockName);

        self::assertTrue($block->isValid(), sprintf('Embedded block "%s" was not found', $blockName));

        if ($SKU) {
            $productItem = $this->findProductItem($SKU, $block);
            $picture = $this->createElement('Product Preview Picture', $productItem);
        } else {
            $picture = $this->createElement('Product View Picture', $block);
        }

        $this->attachmentImageContext->iShouldSeePictureElement($picture);
    }

    /**
     * Example: I open product gallery for "SKU" product
     *
     * @Then /^(?:|I )open product gallery for "(?P<SKU>[^"]*)" product$/
     *
     * @param string $SKU
     */
    public function iOpenProductGallery($SKU)
    {
        $productItem = $this->findProductItem($SKU);

        $galleryTrigger = $this->createElement('Product Item Gallery Trigger', $productItem);

        self::assertNotEmpty($galleryTrigger, sprintf('Image gallery not found for product "%s"', $SKU));

        $galleryTrigger->focus();
        $galleryTrigger->click();
    }

    /**
     * Example: I should see gallery image with alt "alt"
     *
     * @Then /^(?:|I )should see gallery image with alt "(?P<alt>(?:[^"]|\\")*)"$/
     *
     * @param string $alt
     */
    public function iShouldSeeGalleryImageWithAlt($alt)
    {
        $alt = $this->fixStepArgument($alt);
        $galleryWidgetImage = $this->createElement('Popup Gallery Widget Image');

        self::assertEquals(
            $alt,
            $galleryWidgetImage->getAttribute('alt'),
            sprintf('Image with alt "%s" not found in product gallery', $alt)
        );
    }

    /**
     * Click on button in matrix order window
     * Example: Given I click "Add to Shopping List" in matrix order window
     * @When /^(?:|I )click "(?P<button>(?:[^"]|\\")*)" in matrix order window$/
     */
    public function pressButtonInModalWindow($button)
    {
        $modalWindow = $this->getPage()->findVisible('css', 'div.matrix-order-widget');
        self::assertNotNull($modalWindow, 'There is no visible matrix order on page at this moment');
        try {
            $button = $this->fixStepArgument($button);
            $modalWindow->pressButton($button);
        } catch (ElementNotFoundException $e) {
            if ($modalWindow->hasLink($button)) {
                $modalWindow->clickLink($button);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Check checkboxes in multiple select filter
     * Example: When I check "Task, Email" in Activity Type filter in frontend product grid
     * Example: When I check "Task, Email" in "Activity Type filter" in frontend product grid
     *
     * @When /^(?:|I )check "(?P<filterItems>.+)" in (?P<filterName>[\w\s]+) filter in frontend product grid$/
     * @When /^(?:|I )check "(?P<filterItems>.+)" in "(?P<filterName>[^"]+)" filter in frontend product grid$/
     *
     * @param string $filterName
     * @param string $filterItems
     */
    public function iCheckCheckboxesInFilter($filterName, $filterItems)
    {
        /** @var MultipleChoice $filterItem */
        $filterItem = $this->getGridFilters()->getFilterItem('Frontend Product Grid MultipleChoice', $filterName);
        $filterItem->checkItemsInFilter($filterItems);
    }

    //phpcs:disable
    /**
     * Options search in multiple select filter
     * Example: When I type "Task" in search field of Activity Type filter in frontend product grid
     * Example: When I type "Task" in search field of "Activity Type filter" in frontend product grid
     *
     * @When /^(?:|I )type "(?P<searchTerm>.+)" in search field of (?P<filterName>[\w\s]+) filter in frontend product grid$/
     * @When /^(?:|I )type "(?P<searchTerm>.+)" in search field of "(?P<filterName>[^"]+)" filter in frontend product grid$/
     *
     * @param string $searchTerm
     * @param string $filterName
     */
    //phpcs:enable
    public function iSearchForOptionsInFilter($searchTerm, $filterName)
    {
        /** @var MultipleChoice $filterItem */
        $filterItem = $this->getGridFilters()->getFilterItem('Frontend Product Grid MultipleChoice', $filterName);

        $filterItem->open();
        // Wait for open widget
        $this->getDriver()->waitForAjax();

        $searchField = $filterItem->getSearchField();
        $this->getDriver()->typeIntoInput($searchField->getXpath(), $searchTerm);
    }

    /**
     * Checks if multiple choice filter contains expected options in the given order and no other options.
     *
     * Example: Then I should see "Address Filter" filter with exact options in frontend product grid:
     *            | Address 1 |
     *            | Address 2 |
     * @When /^(?:|I )should see "(?P<filterName>[^"]+)" filter with exact options in frontend product grid:$/
     */
    public function shouldSeeSelectWithOptions($filterName, TableNode $options)
    {
        /** @var MultipleChoice $filterItem */
        $filterItem = $this->getGridFilters()->getFilterItem(
            'Frontend Product Grid MultipleChoice',
            $filterName
        );

        self::assertEquals($options->getColumn(0), $filterItem->getChoices(), 'Filter options are not as expected');
    }

    /**
     * @return GridFilters|Element
     */
    private function getGridFilters()
    {
        $filters = $this->elementFactory->createElement('GridFilters');
        if (!$filters->isVisible()) {
            $gridToolbarActions = $this->elementFactory->createElement('GridToolbarActions');
            if ($gridToolbarActions->isVisible()) {
                $gridToolbarActions->getActionByTitle('Filter Toggle')->click();
            }

            $filterState = $this->elementFactory->createElement('GridFiltersState');
            if ($filterState->isValid() && $filterState->isVisible()) {
                $filterState->click();
            }
        }

        return $filters;
    }

    /**
     * Activate filter block in frontend product grid
     *
     * @When /^(?:|I )check that filter block visible in frontend product grid$/
     */
    public function enableGridFilters()
    {
        $this->getGridFilters();
    }

    /**
     * Select a value for product attribute on product update form
     * Example: I fill in product attribute "Color" with "Red"
     *
     * @When /^(?:|I )fill in product attribute "(?P<field>(?:[^"]|\\")*)" with "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function fillProductAttribute($field, $value)
    {
        $field = $this->fixStepArgument($field);
        $value = $this->fixStepArgument($value);
        $form = $this->createElement('OroForm');
        $value = $form->normalizeValue($value);

        $form
            ->find('css', sprintf('[id^="oro_product_%s"]', $field))
            ->setValue($value);
    }

    /**
     * Assert product additional units
     * Example: And I should see following product images:
     *            | cat1.jpg | 1 | 1 | 1 |
     *            | cat2.jpg |   |   | 1 |
     *
     * @Then /^(?:|I )should see following product images:$/
     */
    public function iShouldSeeFollowingImages(TableNode $table)
    {
        $element = $this->getPage()->find('xpath', '//div[contains(@class, "image-collection")]/table/tbody');

        self::assertNotNull($element, 'Image table not found on the page.');

        $crawler = new Crawler($element->getHtml());
        $results = [];
        $crawler->filter('tr')->each(function (Crawler $tr) use (&$results) {
            $row = [];
            $tr->filter('td')->each(function (Crawler $td) use (&$row) {
                if ($td->filter('i.fa-check-square-o')->count()) {
                    $row[] = 1;
                } else {
                    $row[] = trim($td->filter('td')->first()->text());
                }
            });

            $results[] = $row;
        });

        foreach ($table->getRows() as $key => $row) {
            foreach ($row as &$value) {
                $value = trim($value);
            }

            self::assertEquals($results[$key], $row, sprintf('Result "%s" not found', $table->getRowAsString($key)));
        }
    }

    /**
     * Assert product additional units
     * Example: And I should see following product additional units:
     *            | item | 1 | 5  | Yes |
     *            | set  | 5 | 10 | No  |
     *
     * @Then /^(?:|I )should see following product additional units:$/
     */
    public function iShouldSeeFollowingAdditionalUnits(TableNode $table)
    {
        $element = $this->getPage()->find('xpath', '//table[contains(@class, "unit-table")]/tbody');

        self::assertNotNull($element, 'Additional units table not found on the page.');

        $crawler = new Crawler($element->getHtml());
        $results = [];
        $crawler->filter('tr')->each(function (Crawler $tr) use (&$results) {
            $row = [];
            $tr->filter('td')->each(function (Crawler $td) use (&$row) {
                $row[] = trim($td->filter('td')->first()->text());
            });

            $results[] = $row;
        });

        foreach ($table->getRows() as $key => $row) {
            foreach ($row as &$value) {
                $value = trim($value);
            }

            self::assertEquals($results[$key], $row, sprintf('Result "%s" not found', $table->getRowAsString($key)));
        }
    }

    /**
     * @param string $skuOrName
     * @param Element|null $context
     *
     * @return Element
     */
    private function findProductItem(string $skuOrName): Element
    {
        $skuOrName = strtolower($skuOrName);
        $itemBySkuEl = $this->createElement('ProductItemBySku');
        $xpath = str_replace(':sku:', $skuOrName, $itemBySkuEl->getXpath());
        $nodeElement = $this->getPage()->find('xpath', $xpath);
        if (!$nodeElement) {
            $itemBySkuEl = $this->createElement('ProductItemByName');
            $xpath = str_replace(':name:', $skuOrName, $itemBySkuEl->getXpath());
            $nodeElement = $this->getPage()->find('xpath', $xpath);
        }
        self::assertNotNull($nodeElement, sprintf('Product with SKU/Name "%s" not found', $skuOrName));
        $productItem = $this->elementFactory->wrapElement('ProductItem', $nodeElement);
        self::assertNotNull($productItem, sprintf('Product with SKU "%s" not found', $skuOrName));

        return $productItem;
    }

    private function findProductItemInShoppingList(string $SKU, ?Element $context = null): Element
    {
        $productItem = $this->findElementContains('Shopping list line item', $SKU, $context);
        self::assertNotNull($productItem, sprintf('Product with SKU "%s" not found', $SKU));

        return $productItem;
    }

    /**
     * @When /^(?:|I )attach "(?P<fileName>.*)" for Product Images/
     */
    public function iAttachFileToField(string $fileName)
    {
        $importFileLink = $this->createElement('Import Choose File Link');
        $importFileLink->click();

        $importFile = $this->createElement('Import Choose File');
        $importFile->setValue($fileName);
    }

    private function getProductUnitPrecisionsIteratorAll(): iterable
    {
        $doctrineHelper = $this->getAppContainer()->get('oro_entity.doctrine_helper');
        $pnpRepository = $doctrineHelper->getEntityRepositoryForClass(ProductUnitPrecision::class);
        $queryBuilder = $pnpRepository->createQueryBuilder('pup');
        $queryBuilder->select('pup', 'pu')
            ->innerJoin('pup.product', 'p')
            ->innerJoin('pup.unit', 'pu');

        return $queryBuilder->getQuery()->toIterable();
    }

    /**
     * Assert tabs exist in a tab set.
     * Example: Then I should see the following tabs on product page:
     *              | First Tab  |
     *              | Second Tab |
     *
     * @Then /^(?:|I )should see the following tabs on product page:$/
     */
    public function iShouldSeeFollowingTabs(TableNode $table)
    {
        $values = $table->getColumn(0);
        foreach ($values as $value) {
            $linkElement = $this->elementFactory->findElementContainsByXPath('Product Tab Link', $value, false);
            self::assertTrue($linkElement->isValid(), "Tab with '$value' text not found in product form page");
        }
    }

    /**
     * Assert tabs is not exists in a tab set.
     * Example: Then I should not see the following tabs on product page:
     *              | First Tab  |
     *              | Second Tab |
     *
     * @Then /^(?:|I )should not see the following tabs on product page:$/
     */
    public function iShouldNotSeeFollowingTabs(TableNode $table)
    {
        $values = $table->getColumn(0);
        foreach ($values as $value) {
            $linkElement = $this->elementFactory->findElementContainsByXPath('Product Tab Link', $value, false);
            self::assertFalse($linkElement->isValid(), "Tab with '$value' text is present in product form page");
        }
    }
}
