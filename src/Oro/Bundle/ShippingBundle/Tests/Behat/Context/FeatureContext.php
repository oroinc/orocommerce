<?php

namespace Oro\Bundle\ShippingBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;

use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutStep;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\FormBundle\Tests\Behat\Element\OroForm;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\ShippingBundle\Tests\Behat\Element\CheckoutForm;
use Oro\Bundle\ShippingBundle\Tests\Behat\Element\CheckoutTotal;
use Oro\Bundle\ShippingBundle\Tests\Behat\Element\ShoppingListWidget;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\ElementFactoryDictionary;

use Symfony\Component\HttpKernel\KernelInterface;

class FeatureContext extends OroFeatureContext implements OroElementFactoryAware, KernelAwareContext
{
    use ElementFactoryDictionary, KernelDictionary;

    /**
     * @Given there is EUR currency in the system configuration
     */
    public function thereIsEurCurrencyInTheSystemConfiguration()
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        /** @var var array $currencies */
        $currencies = (array)$configManager->get('oro_currency.allowed_currencies', []);
        $currencies = array_unique(array_merge($currencies, ['EUR']));
        $configManager->set('oro_currency.allowed_currencies', $currencies);
        $configManager->flush();

        $configManager = $this->getContainer()->get('oro_config.manager');
        $configManager->set('oro_b2b_pricing.enabled_currencies', ['EUR','USD']);
        $configManager->flush();
    }

    /**
     * @Given /^I login as (?P<email>\S+)$/
     */
    public function loginAsBuyer($email)
    {
        $this->visitPath('account/user/login');
        $this->waitForAjax();
        /** @var OroForm $form */
        $form = $this->createElement('OroForm');
        $table = new TableNode([
            ['Email Address', $email],
            ['Password', $email]
        ]);
        $form->fill($table);
        $form->pressButton('Sign In');
        $this->waitForAjax();
    }

    /**
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
     * @Then Shipping Type FlatRate is shown for Buyer selection
     */
    public function shippingTypeFlatRateIsShownForBuyerSelection()
    {
        /** @var CheckoutForm $checkoutForm */
        $checkoutForm = $this->createElement('CheckoutForm');
        $checkoutForm->assertHas('Flat Rate');
    }

    /**
     * @Then the order total is recalculated to <:arg1>
     */
    public function theOrderTotalIsRecalculatedTo($arg1)
    {
        /** @var CheckoutTotal $checkoutTotal */
        $checkoutTotal = $this->createElement('CheckoutTotal');
        $checkoutTotal->isEqual($arg1);
    }

    /**
     * @Then There is no shipping method available for this order
     */
    public function noShippingMethodsAvailable()
    {
        $this->assertSession()->elementContains('css', '.notification_alert', 'No shipping methods are available');
    }

    /**
     * @Given Admin User edited :arg1 with next data:
     */
    public function adminUserEditedWithNextData($shippingRule, TableNode $table)
    {
        $this->getMink()->setDefaultSessionName('second_session');
        $this->getSession()->resizeWindow(1920, 1080, 'current');

        $this->loginAsAdmin();

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
     * @Given Admin User Created :arg1 with next data
     */
    public function adminUserCreatedWithNextData($shoppingRuleName, TableNode $table)
    {
        $this->getMink()->setDefaultSessionName('second_session');
        $this->getSession()->resizeWindow(1920, 1080, 'current');

        $this->loginAsAdmin();

        /** @var MainMenu $mainMenu */
        $mainMenu = $this->createElement('MainMenu');
        $mainMenu->openAndClick('System/Shipping Rules');
        $this->waitForAjax();

        $this->getSession()->getPage()->clickLink('Create Shipping Rule');
        $this->waitForAjax();

        /** @var Form $form */
        $form = $this->createElement('Shipping Rule');
        $form->fillField('Name', $shoppingRuleName);
        $form->fillField('Sort Order', '2');
        $form ->clickLink('Add');
        $form->fill($table);
        $form->saveAndClose();

        $this->waitForAjax();
        $this->getSession('second_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
    }

    /**
     * @When Buyer is again on Shipping Method Checkout step on :arg1
     */
    public function buyerIsAgainOnShippingMethodCheckoutStepOn($shoppingListName)
    {
        $this->createOrderFromShoppingList($shoppingListName);
        /** @var checkoutStep $checkoutStep */
        $checkoutStep = $this->createElement('CheckoutStep');
        $checkoutStep->assertTitle('Shipping Method');
    }

    protected function loginAsAdmin()
    {
        $this->visitPath('admin/user/login');
        /** @var Form $login */
        $login = $this->createElement('Login');
        $login->fill(new TableNode([['Username', 'admin'], ['Password', 'admin']]));
        $login->pressButton('Log in');
        $this->waitForAjax();
    }

    /**
     * @param string $shoppingListName
     */
    protected function createOrderFromShoppingList($shoppingListName)
    {
        $this->visitPath('account/shoppinglist/1');
        $this->waitForAjax();
        $this->getSession()->getPage()->clickLink('Create Order');
        $this->waitForAjax();
    }
}
