<?php

namespace Oro\Bundle\ShippingBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;

use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutStep;
use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutForm;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\FormBundle\Tests\Behat\Element\OroForm;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\ShippingBundle\Tests\Behat\Element\ShoppingListWidget;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\ElementFactoryDictionary;

use Symfony\Component\HttpKernel\KernelInterface;

class FeatureContext extends OroFeatureContext implements OroElementFactoryAware, KernelAwareContext
{
    use ElementFactoryDictionary;

    /**
     * @var KernelInterface
     */
    protected $kernel;


    public function setKernel(KernelInterface $kernelInterface)
    {
        $this->kernel = $kernelInterface;
    }

    /**
     * @Given there is EUR currency in the system configuration
     */
    public function thereIsEurCurrencyInTheSystemConfiguration()
    {
        $container = $this->kernel->getContainer();
        $configManager = $container->get('oro_config.global');
        $currencies = (array)$configManager->get('oro_currency.allowed_currencies', []);
        $currencies = array_unique(array_merge($currencies, ['EUR']));
        $configManager->set('oro_currency.allowed_currencies', $currencies);
        $configManager->flush();
        $configManager = $container->get('oro_config.manager');
        $configManager->set('oro_b2b_pricing.enabled_currencies', ['EUR','USD']);
        $configManager->flush();
    }

    /**
     * @Given /^I login as (?P<email>\S+)$/
     */
    public function loginAsBuyer($email)
    {
        $this->visitPath('account/user/login');
        $this->getSession()->resizeWindow(1920, 1080, 'current');
        $this->getSession()->getDriver()->waitForAjax();
        /** @var OroForm $form */
        $form = $this->createElement('OroForm');
        $table = new TableNode([
            ['Email Address', $email],
            ['Password', $email]
        ]);
        $form->fill($table);
        $form->pressButton('Sign In');
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
        $this->getSession()->getDriver()->waitForAjax();

        $this->getSession()->getPage()->pressButton('Continue');
        $this->getSession()->getDriver()->waitForAjax();
       // $checkoutStep->assertTitle('Shipping Information');
        $this->getSession()->getPage()->pressButton('Continue');
        $this->getSession()->getDriver()->waitForAjax();
        $checkoutStep->assertTitle('Shipping Method');
    }

    /**
     * @Then Shipping Type FlatRate is shown for Buyer selection
     */
    public function shippingTypeFlatRateIsShownForBuyerSelection()
    {
        /** @var checkoutForm $checkoutForm */
        $checkoutForm = $this->createElement('CheckoutForm');
        $checkoutForm->assertHas('Flat Rate');
    }

    /**
     * @Then the order total is recalculated to <:arg1>
     */
    public function theOrderTotalIsRecalculatedTo($arg1)
    {
        /** @var checkoutForm $checkoutForm */
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
        $this->getSession()->getDriver()->waitForAjax();

        /** @var Grid $grid */
        $grid = $this->createElement('Grid');
        $grid->clickActionLink($shippingRule, 'Edit');
        $this->getSession()->getDriver()->waitForAjax();

        /** @var Form $form */
        $form = $this->createElement('Shipping Rule');
        $form ->clickLink('Add');
        $form->fill($table);
        $form->saveAndClose();
        $this->getSession()->getDriver()->waitForAjax();

        $this->getSession('second_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
    }

    /**
     * @Given Admin User created :arg1 with next data:
     */
    public function adminUserCreatedWithNextData($shoppingRuleName, TableNode $table)
    {
        $this->getMink()->setDefaultSessionName('second_session');
        $this->getSession()->resizeWindow(1920, 1080, 'current');

        $this->loginAsAdmin();

        /** @var MainMenu $mainMenu */
        $mainMenu = $this->createElement('MainMenu');
        $mainMenu->openAndClick('System/Shipping Rules');
        $this->getSession()->getDriver()->waitForAjax();

        $this->getSession()->getPage()->clickLink('Create Shipping Rule');
        $this->getSession()->getDriver()->waitForAjax();

        /** @var Form $form */
        $form = $this->createElement('Shipping Rule');
        $form->fillField('Name', $shoppingRuleName);
        $form->fillField('Sort Order', 1);
        $form ->clickLink('Add');
        $form->fill($table);
        $form->saveAndClose();

        $this->getSession()->getDriver()->waitForAjax();
        sleep(10);
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
        $this->getSession()->getDriver()->waitForAjax();
    }

    /*
   * @param string $shoppingListName
   */
    protected function createOrderFromShoppingList($shoppingListName)
    {
        $this->getSession()->resizeWindow(1920, 1080, 'current');
        $this->getSession()->getDriver()->waitForAjax();
        /** @var ShoppingListWidget $shoppingListWidget */
        $shoppingListWidget = $this->createElement('ShoppingListWidget');
        $shoppingListWidget->mouseOver();
        $this->getSession()->getDriver()->evaluateScript("$('li.shopping-lists-frontend-widget').trigger('mouseover')");
        $this->getSession()->getDriver()->waitForAjax();
        $shoppingList = $shoppingListWidget->getShoppingList($shoppingListName);
        $shoppingList->viewDetails();
        $this->getSession()->getDriver()->waitForAjax();
        $this->getSession()->getPage()->clickLink('Create Order');
        $this->getSession()->getDriver()->waitForAjax();
    }
}
