<?php

namespace Oro\Bundle\PricingBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class PriceCalculationDetailsContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Then /^(?:|I )should see next prices for "(?P<pricesBlock>[^"]+)":$/
     */
    public function assertPricesCollection(string $pricesBlock, TableNode $table)
    {
        $actualPrices = $this->getActualPrices($pricesBlock);
        $expectedPrices = $this->tableToArray($table);

        self::assertEqualsCanonicalizing(
            $expectedPrices,
            $actualPrices,
            sprintf('Prices are not equal for %s', $pricesBlock)
        );
    }

    /**
     * @Then /^(?:|I )should see next prices selected for "(?P<pricesBlock>[^"]+)":$/
     */
    public function assertSelectedPricesCollection(string $pricesBlock, TableNode $table)
    {
        $actualPrices = $this->getActualPrices($pricesBlock, true);
        $expectedPrices = $this->tableToArray($table);

        self::assertEquals(
            $expectedPrices,
            $actualPrices,
            sprintf('Selected prices are not expected for %s', $pricesBlock)
        );
    }

    private function tableToArray(TableNode $table): array
    {
        $result = [];
        $header = $table->getRow(0);
        for ($i = 0; $i < count($header); $i++) {
            $colValue = array_filter($table->getColumn($i));
            array_shift($colValue);
            $result[$header[$i]] = $colValue;
        }

        return $result;
    }

    private function getPriceDetailsSections(string $pricesBlock): array
    {
        $label = $this->findElementContains('Price Calculation Details Element Label', $pricesBlock);
        if (null === $label || !$label->isValid()) {
            $label = $this->findElementContains('Price Calculation Details Price List Label', $pricesBlock);
        }

        $priceCollections = $label->getElements('Price Calculation Details Prices Collection');
        self::assertNotEmpty($priceCollections);
        $pricesCollection = reset($priceCollections);
        self::assertTrue($pricesCollection?->isValid(), sprintf('Price block "%s" was not found', $pricesBlock));

        return $pricesCollection->getElements('Price Calculation Details Price Block');
    }

    private function getActualPrices(string $pricesBlock, bool $onlySelected = false): array
    {
        $priceDetailsSections = $this->getPriceDetailsSections($pricesBlock);
        $actualPrices = [];
        foreach ($priceDetailsSections as $priceDetailsSection) {
            $unitEl = $priceDetailsSection->getElement('Price Calculation Details Price Unit');
            $priceElements = $priceDetailsSection->getElements('Price Calculation Details Price Item');

            $unit = trim(strip_tags($unitEl->getHtml()));

            if ($onlySelected) {
                $priceElements = array_filter(
                    $priceElements,
                    fn (Element $el) => (bool)$el->find('css', '.selected')
                );
            }

            $priceValues = array_map(
                fn (Element $el) => preg_replace('/\s+/', ' ', trim(strip_tags($el->getHtml()))),
                array_values($priceElements)
            );

            $actualPrices[$unit] = array_unique($priceValues);
        }

        return $actualPrices;
    }
}
