<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\FormBundle\Tests\Behat\Element\OroForm;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\ElementFactoryDictionary;
use OroB2B\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutStep;
use OroB2B\Bundle\ShippingBundle\Tests\Behat\Elements\ShoppingListWidget;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Behat\Symfony2Extension\Context\KernelAwareContext;

class FeatureContext extends OroFeatureContext implements OroElementFactoryAware, KernelAwareContext
{
    use ElementFactoryDictionary;
    /**
     * @var ContainerInterface
     */
    protected $container;

    protected $kernel;

    public function setKernel(KernelInterface $kernelInterface)
    {
        $this->kernel = $kernelInterface;
    }

    /**
     * @Given there is EUR currency in the system configuration exist
     */
    public function thereIsEurCurrencyInTheSystemConfigurationExist()
    {
        $container = $this->kernel->getContainer();
        $configManager = $container->get('oro_config.global');
        $configManager->set('oro_b2b_pricing.enabled_currencies', ['EUR','USD']);

    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @Given /^I login as (?P<email>\S+)$/
     */
    public function loginAsBuyer($email)
    {
        $this->visitPath('app_dev.php/account/user/login');
        /** @var OroForm $form */
        $form = $this->createElement('OroForm');
        $table = new TableNode([
            ['Email Address', $email],
            ['Password', $email]
        ]);
        $form->fill($table);
        $form->pressButton('Sign In');

        $container = $this->kernel->getContainer();
        $configManager = $container->get('oro_config.global');
        $currencies = (array)$configManager->get('oro_b2b_pricing.enabled_currencies', []);

    }

    /**
     * @When /^Buyer is on (?P<checkoutStep>[\w\s]+) Checkout step on (?P<shoppingListName>[\w\s]+)$/
     */
    public function buyerIsOnShippingMethodCheckoutStep($checkoutStep, $shoppingListName)
    {
        $this->getSession()->getDriver()->waitForAjax();
        /** @var ShoppingListWidget $shoppingListWidget */
        $shoppingListWidget = $this->createElement('ShoppingListWidget');
        $shoppingListWidget->mouseOver();
        $shoppingList = $shoppingListWidget->getShoppingList($shoppingListName);
        $shoppingList->viewDetails();
        $this->getSession()->getDriver()->waitForAjax();
        $this->getSession()->getPage()->clickLink('Create Order');
        $this->getSession()->getDriver()->waitForAjax();

        /** @var CheckoutStep $checkoutStep */
        $checkoutStep = $this->createElement('CheckoutStep');
        $checkoutStep->assertTitle('Billing Information');
        $this->getSession()->getDriver()->waitForAjax();
        $this->getSession()->getPage()->pressButton('Continue');
        $this->getSession()->getDriver()->waitForAjax();
        $checkoutStep->assertTitle('Shipping Information');
//
//        $this->getSession()->getPage()->pressButton('Continue');
//        $this->getSession()->getDriver()->waitForAjax();
//        $checkoutStep->assertTitle('Shipping Method');
    }

    /**
     * @Then There is no shipping method available for this order
     */
    public function noShippingMethodsAvailable()
    {
        $this->assertSession()->elementContains('css', '.notification_alert', 'No shipping methods are available');
    }

    /**
     * @Given Admin User has Shipping Rulesт Full permissions
     */
    public function adminUserHasShippingRulesFullPermissions()
    {
        throw new PendingException();
    }

    /**
     * @Given Buyer User with Edit Shipping Address permissions
     */
    public function buyerUserWithEditShippingAddressPermissions()
    {
        throw new PendingException();
    }

    /**
     * @Given Shopping Rule Flat Rate Shipping Cost = :arg1
     */
    public function shoppingRuleFlatRateShippingCost($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given Shopping Rule Flat Rate Type = per Order by default
     */
    public function shoppingRuleFlatRateTypePerOrderByDefault()
    {
        throw new PendingException();
    }

    /**
     * @Given Shopping Rule Flat Rate Handling Fee = :arg1
     */
    public function shoppingRuleFlatRateHandlingFee($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given Admin User created Flat Rate Shipping Rule #:arg1 with next data:
     */
    public function adminUserCreatedFlatRateShippingRuleWithNextData(TableNode $table)
  {
      throw new PendingException();
  }

    /**
     * @Given Buyer created order with:
     */
    public function buyerCreatedOrderWith(TableNode $table)
    {
        throw new PendingException();
    }

    /**
     * @Then Shipping Type :arg1 is shown for Buyer selection
     */
    public function shippingTypeFlatRateEurIsShownForBuyerSelection($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then One the next Checkout step order subtotal is recalculated to :arg1
     */
    public function oneTheNextCheckoutStepOrderSubtotalIsRecalculatedTo($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given Admin User edited :arg1 with next data:
     */
    public function adminUserEditedWithNextData($arg1, TableNode $table)
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
        $grid->clickActionLink($arg1, 'Edit');
        $this->getSession()->getDriver()->waitForAjax();

        /** @var Form $form */
        $form = $this->createElement('OroForm');
        $form->fill($table);
        $form->saveAndClose();
        $this->getSession()->getDriver()->waitForAjax();

        $this->getSession('second_session')->stop();
        $this->getMink()->setDefaultSessionName('first_session');
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

    /**
     * @Then Flat Rate is non-visible for Buyer selection
     */
    public function flatRateIsNonVisibleForBuyerSelection()
    {
        throw new PendingException();
    }

    /**
     * @Given the product unit :arg1 was uploaded in database
     */
    public function test($arg1)
    {
        sleep(30);
    }
}
