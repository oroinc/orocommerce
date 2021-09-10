<?php

namespace Oro\Bundle\ShippingBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutStep;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    private ?OroMainContext $oroMainContext = null;

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
     * @When /^Buyer is on Checkout step on "(?P<shoppingListName>[\w\s]+)"$/
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
     * @param string $shippingType
     */
    public function shippingTypeFlatRateIsShownForBuyerSelection($shippingType)
    {
        $elements = $this->findAllElements('CheckoutFormRow');
        foreach ($elements as $element) {
            if (str_contains($element->getText(), $shippingType)) {
                return;
            }
        }

        self::fail(sprintf('Shipping type "%s" not found on checkout form', $shippingType));
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
        $this->oroMainContext->iShouldSeeFlashMessage(
            'No shipping methods are available, please contact us to complete the order submission.',
            'CreateOrderFlashMessage'
        );
    }

    /**
     * @Then There is no shipping method available for this order
     */
    public function noShippingMethodsAvailable()
    {
        $this->oroMainContext->iShouldSeeFlashMessage(
            'No shipping methods are available, please contact us to complete the order submission.',
            'Notification Alert'
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
        self::assertFalse($this->getMink()->isSessionStarted('system_session'));
        $this->getMink()->setDefaultSessionName('system_session');
        $this->getSession()->resizeWindow(1920, 1080, 'current');

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

        $this->getSession('system_session')->stop();
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
        self::assertFalse($this->getMink()->isSessionStarted('system_session'));
        $this->getMink()->setDefaultSessionName('system_session');
        $this->getSession()->resizeWindow(1920, 1080, 'current');

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

        foreach ($table->getColumn(0) as $columnItem) {
            if (str_contains($columnItem, 'Country')) {
                $destinationAdd = $form->find('css', '.add-list-item');
                $destinationAdd->click();
            }
        }

        $form->fill($table);
        $form->saveAndClose();

        $this->waitForAjax();
        $this->getSession('system_session')->stop();
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
     * @Then Buyer is on Shipping Method Checkout step
     */
    public function assertBuyerIsOnShippingMethodCheckoutStep()
    {
        /** @var CheckoutStep $checkoutStep */
        $checkoutStep = $this->createElement('CheckoutStep');
        $checkoutStep->assertTitle('Shipping Method');
    }

    /**
     * @When Buyer is again on Shipping Method Checkout step on :shoppingListName
     */
    public function buyerIsAgainOnShippingMethodCheckoutStepOn($shoppingListName)
    {
        $this->createOrderFromShoppingList($shoppingListName);
        /** @var CheckoutStep $checkoutStep */
        $checkoutStep = $this->createElement('CheckoutStep');
        $checkoutStep->assertTitle('Shipping Method');
    }

    /**
     * @param string $shoppingListName
     */
    protected function createOrderFromShoppingList($shoppingListName)
    {
        /** @var ObjectManager $manager */
        $manager = $this->getAppContainer()->get('doctrine')->getManagerForClass(ShoppingList::class);
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
        $form->fillField('Select Shipping Address', 'New address');
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
     * Verify that Existing Shipping Rules popup appears
     *
     * Example: I should see Existing Shipping Rules popup
     *
     * @Then /^(?:|I )should see Existing Shipping Rules popup$/
     */
    public function iShouldSeeExistingShippingRulesPopup()
    {
        $this->assertSession()->elementTextContains(
            'css',
            'div.modal-header',
            'Deactivate integration'
        );
    }

    /**
     * Verify that page contains disabled Shipping Method Config
     *
     * Example: I should see Disabled Shipping Method Configuration$/
     *
     * @Then /^(?:|I )should see Disabled Shipping Method Configuration$/
     */
    public function assertDisabledShippingMethodConfig()
    {
        $this->assertSession()->elementTextContains(
            'css',
            'div[data-name="field__method-configs"]',
            'Disabled'
        );
    }

    /**
     * Press agree in pop-up
     *
     * @Given /^(?:|I )agree that shipping cost may have changed$/
     */
    public function iAgreeThatShippingCostMayHaveChanged()
    {
        $saveLink = $this->getPage()->find('css', '.oro-modal-normal .ok.btn-primary');
        self::assertNotNull($saveLink, "Can't find modal window or 'Save' button");
        $saveLink->click();
    }

    /**
     * Assert product shipping options
     * Example: And I should see following product shipping options:
     *            | item | 9 lbs | 9 x 8 x 6 cm | parcel |
     *            | set  | 3 lbs | 2 x 5 x 3 cm | parcel |
     *
     * @Then /^(?:|I )should see following product shipping options:$/
     */
    public function iShouldSeeFollowingAdditionalUnits(TableNode $table)
    {
        $element = $this->getPage()->find('xpath', '//table[contains(@class, "shipping-options-result-grid")]/tbody');

        self::assertNotNull($element, 'Shipping options table not found on the page.');

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
}
