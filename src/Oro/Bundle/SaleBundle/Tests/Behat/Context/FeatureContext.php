<?php

namespace Oro\Bundle\SaleBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CheckoutBundle\Tests\Behat\Element\CheckoutStep;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\EmailBundle\Tests\Behat\Context\EmailContext;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\NavigationBundle\Tests\Behat\Element\MainMenu;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderDictionary;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeatureContext extends OroFeatureContext implements
    OroPageObjectAware,
    FixtureLoaderAwareInterface
{
    use PageObjectDictionary, FixtureLoaderDictionary;

    /**
     * @var OroMainContext
     */
    private $oroMainContext;

    /**
     * @var EmailContext
     */
    private $emailContext;

    /**
     * @var \Oro\Bundle\WorkflowBundle\Tests\Behat\Context\FeatureContext
     */
    private $workflowContext;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->oroMainContext = $environment->getContext(OroMainContext::class);
        $this->emailContext = $environment->getContext(EmailContext::class);
        $this->workflowContext = $environment
            ->getContext(\Oro\Bundle\WorkflowBundle\Tests\Behat\Context\FeatureContext::class);
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
        $this->waitForAjax();

        $this->getPage()->pressButton('Save and Close');

        // Click on "Save" button in the confirmation dialog.
        $saveLink = $this->getPage()->find('css', '.oro-modal-normal .ok.btn-primary');
        self::assertNotNull($saveLink, "Can't find modal window or 'Save' button");
        $saveLink->click();
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

    /**
     * @When /^(?:|I )open Quote with qid (?P<qid>[\w\s]+)/
     *
     * @param string $qid
     */
    public function openQuote($qid)
    {
        $url = $this->getAppContainer()
            ->get('router')
            ->generate('oro_sale_quote_view', ['id' => $qid]);

        $this->visitPath($url);
        $this->waitForAjax();
    }

    /**
     * Example: And I should see "Sales Representative Info" block with:
     * |Charlie Sheen       |
     *
     * @Then /^I should see "(?P<block>[^"]*)" block with:$/
     * @param string    $block
     * @param TableNode $table
     */
    public function iShouldSeeOnFrontendRequestPageStatus($block, TableNode $table)
    {
        $elements = $this->findAllElements($block);
        foreach ($elements as $element) {
            $html = $element->getHtml();
            foreach ($table->getColumn(0) as $item) {
                static::assertStringContainsString($item, $html);
            }
        }
    }

    /**
     * Example: I should see truncated to 10 symbols link for quote qid "Quote1"
     *
     * @Then /^(?:|I )should see truncated to (?P<truncateTo>(?:\d+)) symbols link for quote qid (?P<qid>[\w\s]+)$/
     * @param string $qid
     * @param integer $truncateTo
     */
    public function iShouldSeeTruncatedGuestLink(string $qid, int $truncateTo): void
    {
        $this->assertSession()->pageTextContains($this->getTruncatedGuestLink($qid, $truncateTo));
    }

    /**
     * Example: I visit guest quote link for quote 123
     *
     * @When /^I visit guest quote link for quote (?P<qid>[\w\s]+)$/
     */
    public function iVisitGuestQuoteLinkForQuote(string $qid): void
    {
        $this->visitPath($this->getGuestLink($qid));
    }

    /**
     * Example: Then Guest Quote "123" email has been sent to "somebody@examle.com"
     *
     * @Then /^Guest Quote "(?P<qid>[\w\s]+)" email has been sent to "(?P<email>\S+)"/
     */
    public function guestQuoteEmailHasBeenSend(string $qid, string $email): void
    {
        $guestLink = $this->getGuestLink($qid);

        $guestQuoteTableNode = new TableNode([
            ['To', $email],
            ['Body', $guestLink]
        ]);

        $this->emailContext->emailShouldContainsTheFollowing($guestQuoteTableNode);
    }

    /**
     * Example: Then I click truncated to 10 symbols Guest Quote 123 link
     *
     * @Then /^(?:|I )click truncated to (?P<truncateTo>(?:\d+)) symbols Guest Quote (?P<qid>[\w\s]+) link/
     * @param string $qid
     * @param integer $truncateTo
     */
    public function clickToGuestQuoteLink(string $qid, int $truncateTo): void
    {
        $guestLink = $this->getTruncatedGuestLink($qid, $truncateTo);
        self::assertTrue($this->getSession()->getPage()->hasLink($guestLink), sprintf(
            'Link "%s" not found.',
            $guestLink
        ));

        $this->oroMainContext->clickLink($guestLink);
    }

    /**
     * @When automatic expiration of old quotes has been performed
     */
    public function processAutomaticOldQuotesExpirationProcess()
    {
        $this->workflowContext->processScheduledCronTriggers();
    }

    /**
     * Example: When Quote "9" is marked as accepted by customer
     *
     * @When /^Quote "(?P<quoteId>(?:\d+))" is marked as accepted by customer/
     */
    public function markQuoteAsAcceptedByCustomer(int $quoteId)
    {
        $managerRegistry = $this->getAppContainer()->get('doctrine');
        $manager = $managerRegistry->getManagerForClass(Quote::class);

        $className = ExtendHelper::buildEnumValueClassName(Quote::CUSTOMER_STATUS_CODE);
        $enumValue = $manager->getReference($className, 'accepted');

        $quote = $manager->getRepository(Quote::class)->find($quoteId);
        $quote->setCustomerStatus($enumValue);

        $manager->flush();
    }

    /**
     * @param string $qid
     *
     * @return Quote
     */
    protected function getQuote($qid)
    {
        return $this->getRepository(Quote::class)->findOneBy(['qid' => $qid]);
    }

    /**
     * @param string $className
     *
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
     * @param string $qid
     * @param integer $truncateTo
     * @return string
     */
    private function getTruncatedGuestLink(string $qid, int $truncateTo): string
    {
        $guestLink = $this->getGuestLink($qid);

        return substr($guestLink, 0, $truncateTo);
    }

    private function getGuestLink(string $qid): string
    {
        $quote = $this->getQuote($qid);

        return $this->getAppContainer()
            ->get('oro_website.resolver.website_url_resolver')
            ->getWebsitePath(
                'oro_sale_quote_frontend_view_guest',
                ['guest_access_id' => $quote->getGuestAccessId()],
                $quote->getWebsite()
            );
    }
}
