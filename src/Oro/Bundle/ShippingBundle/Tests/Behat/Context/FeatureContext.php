<?php

namespace Oro\Bundle\ShippingBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethodType;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @var OroMainContext
     */
    private $oroMainContext;

    /**
     * @BeforeScenario
     * @param BeforeScenarioScope $scope
     */
    public function loadFlatRateConfig(BeforeScenarioScope $scope)
    {
        $shippingRuleName = 'Shipping Rule First';

        $em = $this->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityManagerForClass(ShippingMethodsConfigsRule::class);
        $rule = $em->getRepository(Rule::class)->findOneBy(['name' => $shippingRuleName]);
        /** @var ShippingMethodsConfigsRuleRepository $repository */
        $repository = $em->getRepository(ShippingMethodsConfigsRule::class);
        /** @var ShippingMethodsConfigsRule $shippingRule */
        $shippingRule = $repository->findOneBy(['rule' => $rule]);
        $methodConfigs = $shippingRule->getMethodConfigs();
        $methods = $this->getContainer()->get('oro_flat_rate_shipping.method.provider')->getShippingMethods();
        /** @var ShippingMethodInterface $method */
        $method = reset($methods);
        foreach ($methodConfigs as $methodConfig) {
            if ($methodConfig->getMethod() === $method->getIdentifier()) {
                return;
            }
        }
        $types = $method->getTypes();
        $type = reset($types);
        $typeConfig = new ShippingMethodTypeConfig();
        $typeConfig->setType($type->getIdentifier())
            ->setEnabled(true)
            ->setOptions([
                FlatRateMethodType::PRICE_OPTION => 1.5,
                FlatRateMethodType::TYPE_OPTION => FlatRateMethodType::PER_ORDER_TYPE,
                FlatRateMethodType::HANDLING_FEE_OPTION => 1.5,
            ]);
        $methodConfig = new ShippingMethodConfig();
        $methodConfig->setMethod($method->getIdentifier())
            ->addTypeConfig($typeConfig);
        $shippingRule->addMethodConfig($methodConfig);
        $em->persist($typeConfig);
        $em->persist($methodConfig);
        $em->flush();
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
    }

    /**
     * Walk through menus and navigations to get Checkout step page of given shopping list name
     *
     * @When /^Buyer is on Checkout step on (?P<shoppingListName>[\w\s]+)$/
     */
    public function buyerIsOnShippingMethodCheckoutStep($shoppingListName)
    {
        $this->createOrderFromShoppingList($shoppingListName);

        /** @var checkoutStep $checkoutStep */
        $checkoutStep = $this->createElement('CheckoutStep');
        $checkoutStep->assertTitle('Billing Information');
        $this->waitForAjax();

        $this->getSession()->getPage()->pressButton('Continue');
        $this->waitForAjax();
        $this->getSession()->getPage()->pressButton('Continue');
        $this->waitForAjax();
        $checkoutStep->assertTitle('Shipping Method');
    }

    /**
     * Assert that given shippingType is shown
     *
     * @Then Shipping Type :shippingType is shown for Buyer selection
     */
    public function shippingTypeFlatRateIsShownForBuyerSelection($shippingType)
    {
        $element= $this->createElement('CheckoutFormRow');
        self::assertContains(
            $shippingType,
            $element->getText(),
            "Shipping type '$shippingType' not found on checkout form"
        );
    }

    /**
     * @Then the order total is recalculated to :total
     */
    public function theOrderTotalIsRecalculatedTo($total)
    {
        $this->waitForAjax();
        $totalElement = $this->createElement('CheckoutTotalSum');
        if (!$totalElement->isVisible()) {
            $this->createElement('CheckoutTotalTrigger')->click();
        }
        self::assertEquals($total, $totalElement->getText());
    }

    /**
     * @Then Flash message appears that there is no shipping methods available
     */
    public function flashMessageAppearsThatThereIsNoShippingMethodsAvailable()
    {
        $flashMessages = $this->createElement('CreateOrderFlashMessage');

        self::assertTrue(
            $flashMessages->isValid(),
            'Flash message is not found, or found more then one'
        );
        self::assertEquals(
            'No shipping methods are available, please contact us to complete the order submission.',
            $flashMessages->getText()
        );
    }

    /**
     * @Then There is no shipping method available for this order
     */
    public function noShippingMethodsAvailable()
    {
        $notificationAllert = $this->createElement('Notification Alert');

        self::assertTrue(
            $notificationAllert->isValid(),
            'Notification Alert is not found, or found more then one'
        );
        self::assertEquals(
            'No shipping methods are available, please contact us to complete the order submission.',
            $notificationAllert->getText()
        );
    }

    /**
     * Example: Given Admin User edited "Shipping Rule 1" with next data:
     *            | Enabled  | true    |
     *            | Currency | USD     |
     *            | Country  | Germany |
     *
     * @Given Admin User edited :shippingRule with next data:
     */
    public function adminUserEditedWithNextData($shippingRule, TableNode $table)
    {
        $this->getMink()->setDefaultSessionName('second_session');
        $this->getSession()->resizeWindow(1920, 1880, 'current');

        $this->oroMainContext->loginAsUserWithPassword();
        $this->waitForAjax();
        /** @var MainMenu $mainMenu */
        $mainMenu = $this->createElement('MainMenu');
        $mainMenu->openAndClick('System/Shipping Rules');
        $this->waitForAjax();

        /** @var Grid $grid */
        $grid = $this->createElement('Grid');
        $grid->clickActionLink($shippingRule, 'Edit');
        $this->waitForAjax();

        /** @var Form $form */
        $form = $this->createElement('Shipping Rule');
        $form->fill($table);
        $form->saveAndClose();
        $this->waitForAjax();

        $this->getSession('second_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
    }

    /**
     * Example: Given Admin User created "Shipping Rule 5" with next data:
     *            | Enabled       | true      |
     *            | Currency      | EUR       |
     *            | Sort Order    | -1        |
     *            | Type          | Per Order |
     *            | Price         | 5         |
     *            | HandlingFee   | 1.5       |
     *
     * @Given Admin User created :shoppingRuleName with next data:
     */
    public function adminUserCreatedWithNextData($shoppingRuleName, TableNode $table)
    {
        $this->getMink()->setDefaultSessionName('second_session');
        $this->getSession()->resizeWindow(1920, 1880, 'current');

        $this->oroMainContext->loginAsUserWithPassword();
        $this->waitForAjax();

        /** @var MainMenu $mainMenu */
        $mainMenu = $this->createElement('MainMenu');
        $mainMenu->openAndClick('System/Shipping Rules');
        $this->waitForAjax();

        $this->getSession()->getPage()->clickLink('Create Shipping Rule');
        $this->waitForAjax();

        /** @var Form $form */
        $form = $this->createElement('Shipping Rule');
        $form->fillField('Name', $shoppingRuleName);

        // Add method type config
        if (in_array('Type', $table->getColumn(0), true)) {
            $shippingMethodConfigAdd = $form->find('css', '.add-method');
            $shippingMethodConfigAdd->click();
            $this->waitForAjax();
        }

        foreach ($table->getColumn(0) as $columnItem) {
            if (false !== strpos($columnItem, 'Country')) {
                $destinationAdd = $form->find('css', '.add-list-item');
                $destinationAdd->click();
            }
        }

        $form->fill($table);
        $form->saveAndClose();

        $this->waitForAjax();
        $this->getSession('second_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
    }

    /**
     * @When Buyer is on view shopping list :shoppingListName page and clicks create order button
     * @param string $shoppingListName
     */
    public function buyerIsOnViewShoppingListPageAndClicksCreateOrderButton($shoppingListName)
    {
        $this->createOrderFromShoppingList($shoppingListName);
    }

    /**
     * @When Buyer is again on Shipping Method Checkout step on :shoppingListName
     */
    public function buyerIsAgainOnShippingMethodCheckoutStepOn($shoppingListName)
    {
        $this->createOrderFromShoppingList($shoppingListName);
        /** @var checkoutStep $checkoutStep */
        $checkoutStep = $this->createElement('CheckoutStep');
        $checkoutStep->assertTitle('Shipping Method');
    }

    /**
     * @param string $shoppingListName
     */
    protected function createOrderFromShoppingList($shoppingListName)
    {
        /** @var ObjectManager $manager */
        $manager = $this->getContainer()->get('doctrine')->getManagerForClass(ShoppingList::class);
        /** @var ShoppingList $shoppingList */
        $shoppingList = $manager->getRepository(ShoppingList::class)->findOneBy(['label' => $shoppingListName]);
        $this->visitPath('customer/shoppinglist/'.$shoppingList->getId());
        $this->waitForAjax();
        $this->getSession()->getPage()->clickLink('Create Order');
        $this->waitForAjax();
    }

    /**
     * Example: Given Buyer created order with next shipping address:
     *            | Country         | Ukraine              |
     *            | City            | Kyiv                 |
     *            | State           | Kyïvs'ka mis'ka rada |
     *            | Zip/Postal Code | 01000                |
     *            | Street          | Hreschatik           |
     *
     * @When Buyer created order with next shipping address:
     */
    public function buyerCreatedOrderWithNextShippingAddress(TableNode $table)
    {
        /** @var checkoutStep $checkoutStep */
        $checkoutStep = $this->createElement('CheckoutStep');
        $checkoutStep->assertTitle('Shipping Method');

        $this->getSession()->getPage()->clickLink('Back');
        $this->waitForAjax();
        $checkoutStep->assertTitle('Shipping Information');

        /** @var Form $form */
        $form = $this->createElement('Address');
        $form->fillField('SELECT SHIPPING ADDRESS', 'Enter other address');
        $this->waitForAjax();
        /** @var int $row */
        if ($row = array_search('Country', $table->getColumn(0))) {
            $form->fillField('Country', $table->getRow($row)[1]);
            $this->waitForAjax();
        }
        $form->fill($table);
        $this->getSession()->getPage()->pressButton('Continue');
    }


    /**
     * Feature_bundle DisablingShippingIntegration
     *
     * Setting Shipping Integration type on Create Integration page
     *
     * Example: I set Type value to "Flat Rate Shipping"
     *
     * @When /^I set Type value to "(?P<step>[\w\s]+)"$/
     *
     */
    public function ISetTypeValue($value)
    {
        throw new PendingException();
    }

    /**
     * Feature_bundle DisablingShippingIntegration
     *
     * Setting Shipping integration name on Create Integration page
     *
     * Example: I set Name value to "New Flat Rate"
     *
     * @When /^I set Name value to "(?P<step>[\w\s]+)"$/
     *
     */
    public function ISetNameValue($value)
    {
        throw new PendingException();
    }

    /**
     *  Feature_bundle DisablingShippingIntegration
     *
     * Setting Shipping integration label on Create Integration page
     *
     * Example: I set Label value to "New Flat Rate"
     *
     * @When /^I set Label value to "(?P<step>[\w\s]+)"$/
     *
     */
    public function ISetLabelValue($value)
    {
        throw new PendingException();
    }

    /**
     * Click on selected integration in the grid
     *
     * Example: I select integration by name "Flat Rate"
     *
     * @When /^I select integration by "(?P<step>[\w\s]+)" name$/
     *
     */
    public function ISelectIntegrationByName($name)
    {
        throw new PendingException();
    }

    /**
     * Feature_bundle DisablingShippingIntegration
     *
     * Verify that Existing Shipping Rules popup appears
     *
     * Example: I should see Existing Shipping Rules popup
     *
     * @When /^I should see Existing Shipping Rules popup$/
     *
     */
    public function IShouldSeeExistingShippingRulesPopup()
    {
        throw new PendingException();
    }

    /**
     * Feature_bundle DisablingShippingIntegration
     *
     * Verify that Shipping Rule has information about disabled integration
     *
     * Example: I should see "1. Flat rate (Price: $x.00) (disabled)" text in configuration column
     *
     * @When /^I should see "(.*)" text in configuration column$/
     *
     */
    public function CheckIfConfigurationTextIsPresent()
    {
        throw new PendingException();
    }

    /**
     * Click on selected shipping rule in the grid
     *
     * Example: I select shipping rule by name "Default"
     *
     * @When /^I select shipping rule by "(?P<step>[\w\s]+)" name$/
     *
     */
    public function ISelectShippingRulenByName($name)
    {
        throw new PendingException();
    }

    /**
     * Feature_bundle DisablingShippingIntegration
     *
     * Verify that Method dropdown contains "Default Flat Rate disabled" method
     *
     * Example: I should see "Default Flat Rate disabled" method in Method dropdown
     *
     * @When /^I should see "(?P<step>[\w\s]+)" method in Method dropdown$/
     *
     */
    public function CheckMethodDropdown()
    {
        throw new PendingException();
    }
}
