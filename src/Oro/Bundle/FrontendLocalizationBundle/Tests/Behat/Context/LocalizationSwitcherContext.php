<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\FrontendLocalizationBundle\Tests\Behat\Element\LocalizationSwitcherElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class LocalizationSwitcherContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Then /^(?:|I )should see that localization switcher contains localizations:$/
     */
    public function iSeeThatLocalizationSwitcherContainLocalizations(TableNode $table)
    {
        /** @var LocalizationSwitcherElement $switcher */
        $switcher = $this->createElement('LocalizationSwitcher');
        $actualOptions = $switcher->getLocalizationNames();

        $expectedOptions = array_map(function (array $row) {
            list($value) = $row;

            return $value;
        }, $table->getRows());
        sort($expectedOptions);

        self::assertEquals($expectedOptions, $actualOptions);
    }

    /**
     * @param string $localizationName
     *
     * @Then /^(?:|I )should see that "(?P<localizationName>[^"]*)" localization is active$/
     */
    public function localizationIsActive($localizationName)
    {
        $activeOption = $this->createElement('LocalizationSwitcherActiveOption');
        self::assertEquals(
            trim($localizationName),
            $activeOption->getText()
        );
    }

    /**
     * @param string $localizationName
     *
     * @Given /^(?:|I )select "(?P<localizationName>[^"]*)" localization$/
     */
    public function iSelectLocalization($localizationName)
    {
        /** @var LocalizationSwitcherElement $switcher */
        $switcher = $this->createElement('LocalizationSwitcher');
        $switcher->findLocalizationLink(trim($localizationName))->click();
    }
}
