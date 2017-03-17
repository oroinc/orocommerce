<?php

namespace Oro\Bundle\SaleBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutStep;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @var OroMainContext
     */
    private $oroMainContext;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
    }

    /**
     * @Given /^(?:|I )create a quote from RFQ with PO Number "(?P<poNumber>[^"]+)"$/
     * @param string $poNumber
     */
    public function iCreateAQuoteFromRFQWithPONumber($poNumber)
    {
        /** @var MainMenu $mainMenu */
        $mainMenu = $this->createElement('MainMenu');
        $mainMenu->openAndClick('Sales/Requests For Quote');
        $this->waitForAjax();

        /** @var Grid $grid */
        $grid = $this->createElement('Grid');
        $grid->clickActionLink($poNumber, 'View');
        $this->waitForAjax();

        $this->getPage()->clickLink('Create Quote');
        $this->waitForAjax();

        $unitPrice = $this->getPage()->findField(
            'oro_sale_quote[quoteProducts][0][quoteProductOffers][0][price][value]'
        );

        $unitPrice->focus();
        $unitPrice->setValue('5.0');
        $unitPrice->blur();

        $this->getPage()->pressButton('Save and Close');
    }

    /**
     * @When Buyer starts checkout for a quote with :poNumber PO Number
     * @param string $poNumber
     */
    public function buyerStartsCheckoutForAQuoteWithPONumber($poNumber)
    {
        /** @var Grid $grid */
        $grid = $this->createElement('Grid');
        $grid->clickActionLink($poNumber, 'View');
        $this->waitForAjax();

        $this->getPage()->clickLink('Accept and Submit to Order');
        $this->waitForAjax();

        $this->getPage()->pressButton('Submit');
        $this->waitForAjax();
    }

    /**
     * @Then Buyer is on enter billing information checkout step
     */
    public function buyerIsOnEnterBillingInformationCheckoutStep()
    {
        /** @var CheckoutStep $checkoutStep */
        $checkoutStep = $this->createElement('CheckoutStep');
        $checkoutStep->assertTitle('Billing Information');
    }
}
