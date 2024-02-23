<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Behat\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\ApplicationBundle\Tests\Behat\Context\CommerceMainContext;
use Oro\Bundle\FrontendLocalizationBundle\Tests\Behat\Element\LocalizationCurrencySwitcherElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class LocalizationSwitcherContext extends OroFeatureContext implements OroPageObjectAware
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
     * @Then /^(?:|I )should see that localization switcher contains localizations:$/
     */
    public function iSeeThatLocalizationSwitcherContainLocalizations(TableNode $table)
    {
        $this->commerceMainContext->openMainMenu();
        /** @var LocalizationCurrencySwitcherElement $switcher */
        $switcher = $this->createElement('LocalizationCurrencySwitcher');
        $actualOptions = $switcher->getAvailableLocalizationOptions();

        $expectedOptions = array_map(function (array $row) {
            list($value) = $row;

            return $value;
        }, $table->getRows());
        sort($expectedOptions);

        self::assertEquals($expectedOptions, $actualOptions);

        $this->commerceMainContext->closeMainMenu();
    }

    /**
     * @param string $localizationName
     *
     * @Then /^(?:|I )should see that "(?P<localizationName>[^"]*)" localization is active$/
     */
    public function localizationIsActive(string $localizationName)
    {
        $this->commerceMainContext->openMainMenu();

        /** @var LocalizationCurrencySwitcherElement $switcher */
        $switcher = $this->createElement('LocalizationCurrencySwitcher');

        self::assertEquals($localizationName, $switcher->getActiveLocalizationOption());
        $this->commerceMainContext->closeMainMenu();
    }

    /**
     * @param string $localizationName
     *
     * @Given /^(?:|I )select "(?P<localizationName>[^"]*)" localization$/
     */
    public function iSelectLocalization(string $localizationName)
    {
        $this->commerceMainContext->openMainMenu();

        /** @var LocalizationCurrencySwitcherElement $switcher */
        $switcher = $this->createElement('LocalizationCurrencySwitcher');
        $switcher->setLocalizationValue($localizationName);

        $this->waitForAjax();
        $this->commerceMainContext->closeMainMenu();
    }
}
