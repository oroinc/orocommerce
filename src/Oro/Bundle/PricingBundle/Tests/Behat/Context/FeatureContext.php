<?php

namespace Oro\Bundle\PricingBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApplicationBundle\Tests\Behat\Context\CommerceMainContext;
use Oro\Bundle\FormBundle\Tests\Behat\Element\Select2Entity;
use Oro\Bundle\FrontendLocalizationBundle\Tests\Behat\Element\LocalizationCurrencySwitcherElement;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListIdentifierProviderInterface;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\ProductBundle\Tests\Behat\Element\ProductPriceCollection;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @var CommerceMainContext
     */
    private $commerceMainContext;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->commerceMainContext = $environment->getContext(CommerceMainContext::class);
    }

    /**
     * @Then /^(?:|I )set (?P<collectionFieldName>[^"]+) collection element values in (?P<number>\d+) row:$/
     *
     * @param string $collectionFieldName
     * @param int $number
     * @param TableNode $table
     */
    public function changeCollectionElement($collectionFieldName, $number, TableNode $table)
    {
        /** @var Form $form */
        $form = $this->createElement('OroForm');

        /** @var ProductPriceCollection $collection */
        $collection = $form->findField($collectionFieldName);

        $collection->changeRow($number, $table->getRowsHash());
    }

    /**
     * @Then /^(?:|I )should see following data for (?P<collectionFieldName>[^"]+) collection:$/
     *
     * @param string $collectionFieldName
     * @param TableNode $table
     */
    public function assertProductPriceValues($collectionFieldName, TableNode $table)
    {
        /** @var Form $form */
        $form = $this->createElement('OroForm');

        /** @var ProductPriceCollection $collection */
        $collection = $form->findField($collectionFieldName);

        $collection->assertRows($table->getColumnsHash());
    }

    /**
     * @Then /^(?:|I )select price list with name "(?P<name>[\w\d\s]+)" on sidebar$/
     *
     * @param string $name
     */
    public function selectPriceListWithNameOnSidebar($name)
    {
        /** @var Select2Entity $input */
        $input = $this->createElement('PriceListSidebarSelector');
        $input->setValue($name);
    }

    /**
     * @Then /^There are (?P<count>[\d]+) prices in combined price list:$/
     *
     * @param int $count
     * @param TableNode $table
     */
    public function combinedPriceListPricesCount($count, TableNode $table)
    {
        $count = (int)$count;
        $em = $this->getAppContainer()->get('doctrine')->getManagerForClass(CombinedPriceList::class);

        $priceLists = [];
        /** @var PriceListRepository $plRepo */
        $plRepo = $em->getRepository(PriceList::class);
        foreach ($table->getRows() as $row) {
            /** @var PriceList $priceList */
            $priceList = $plRepo->findOneBy(['name' => $row[0]]);
            $priceLists[] = new PriceListSequenceMember($priceList, true);
        }
        $identifier = $this->getCplIdentifier($priceLists);

        /** @var CombinedPriceListRepository $repo */
        $cplRepo = $em->getRepository(CombinedPriceList::class);
        $cpl = $cplRepo->findOneBy(['name' => $identifier]);

        // If we expect 0 prices CPL may not exist
        if ($count === 0 && !$cpl) {
            return;
        }

        $this->assertNotNull($cpl, 'Expected Combined Price List does not exist');

        /** @var CombinedProductPriceRepository $cplPriceRepo */
        $cplPriceRepo = $em->getRepository(CombinedProductPrice::class);
        $prices = $cplPriceRepo->findBy(['priceList' => $cpl]);

        $this->assertCount($count, $prices, 'Unexpected number of combined prices found');
    }

    /**
     * @When /^(?:|I )select "(?P<currencyName>[^"]*)" currency$/
     */
    public function iChangeCurrencyInCurrencySwitcher(string $currency): void
    {
        $this->commerceMainContext->openMainMenu();

        /** @var LocalizationCurrencySwitcherElement $switcher */
        $switcher = $this->createElement('LocalizationCurrencySwitcher');
        $switcher->setCurrencyValue($currency);

        $this->waitForAjax();
        $this->commerceMainContext->closeMainMenu();
    }

    /**
     * @param string $currencyName
     *
     * @Then /^(?:|I )should see that "(?P<currencyName>[^"]*)" currency is active$/
     */
    public function localizationIsActive(string $currencyName)
    {
        $this->commerceMainContext->openMainMenu();

        /** @var LocalizationCurrencySwitcherElement $switcher */
        $switcher = $this->createElement('LocalizationCurrencySwitcher');

        self::assertEquals($currencyName, $switcher->getActiveCurrencyOption());
        $this->commerceMainContext->closeMainMenu();
    }

    /**
     * @Then /^(?:|I )should see that currency switcher contains currencies:$/
     */
    public function iSeeThatLocalizationSwitcherContainLocalizations(TableNode $table)
    {
        $this->commerceMainContext->openMainMenu();
        /** @var LocalizationCurrencySwitcherElement $switcher */
        $switcher = $this->createElement('LocalizationCurrencySwitcher');
        $actualOptions = $switcher->getAvailableCurrencyOptions();

        $expectedOptions = array_map(function (array $row) {
            list($value) = $row;

            return $value;
        }, $table->getRows());
        sort($expectedOptions);

        self::assertEquals($expectedOptions, $actualOptions);

        $this->commerceMainContext->closeMainMenu();
    }

    /**
     * @Then /^(?:|I )recalculate combined prices$/
     */
    public function recalculateCombinedPrices()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getAppContainer()->get('doctrine')->getManagerForClass(CombinedPriceList::class);
        $qb = $em->createQueryBuilder()
            ->delete()
            ->from(CombinedPriceList::class, 'cpl');
        $qb->getQuery()->execute();

        $this->getAppContainer()->get('oro_pricing.price_list_relation_trigger_handler')->handleFullRebuild();
    }

    /**
     * Example: I select "Product" option in selection results
     *
     * @When /^(?:|I )select "(?P<name>[^"]+)" option in selection results$/
     */
    public function iSelectOptionInSelectionResults(string $name): void
    {
        $detachedSelect2Result = $this->elementFactory->createElement('DetachedSelect2Result');
        $detachedSelect2Result
            ->find('xpath', sprintf(
                '//div[contains(@class, "select2-result-label") and contains(., "%s")]',
                $name
            ))
            ->click();
    }

    /**
     * @param array|PriceListSequenceMember[] $priceLists
     * @return string
     */
    private function getCplIdentifier(array $priceLists): string
    {
        $strategy = $this
            ->getAppContainer()
            ->get('oro_pricing.pricing_strategy.strategy_register')
            ->getCurrentStrategy();

        if ($strategy instanceof CombinedPriceListIdentifierProviderInterface) {
            return $strategy->getCombinedPriceListIdentifier($priceLists);
        }

        $map = [false => 'f', true => 't'];
        $idParts = array_map(static function (PriceListSequenceMember $member) use ($map) {
            return $member->getPriceList()->getId() . $map[$member->isMergeAllowed()];
        }, $priceLists);

        return md5(implode('_', $idParts));
    }
}
