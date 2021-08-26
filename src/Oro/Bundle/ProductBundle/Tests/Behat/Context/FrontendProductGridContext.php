<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\ProductBundle\Tests\Behat\Element\FrontendProductGrid;
use Oro\Bundle\ProductBundle\Tests\Behat\Element\FrontendProductGridFilters;
use Oro\Bundle\ProductBundle\Tests\Behat\Element\FrontendProductGridRow;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FrontendProductGridContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Updates line item form for a given row in frontend product grid.
     * Example: I fill line item with "PSKU1" in frontend product grid:
     *            | Unit    | item |
     *            | Quanity | 3    |
     *
     * @When /^(?:I |)fill line item with "(?P<content>\S+)" in frontend product grid:$/
     */
    public function fillLineItemFormForProductRow(string $content, TableNode $values)
    {
        /** @var FrontendProductGrid $frontendProductGrid */
        $frontendProductGrid = $this->elementFactory->createElement('ProductFrontendGrid');

        /** @var FrontendProductGridRow $gridRow */
        $gridRow = $frontendProductGrid->getRowByContent($content);

        /** @var Form $lineItemForm */
        $lineItemForm = $this->elementFactory->createElement('FrontendLineItemForm', $gridRow);
        $lineItemForm->fill($values);
    }

    /**
     * Resets frontend product grid filter.
     * Example: I reset "ManyToOneField" filter in frontend product grid
     * Example: I reset "ManyToOneField" filter in "product" sidebar
     *
     * @When /^(?:|I )reset "(?P<filterName>[\w\s\:\(\)]+)" filter in frontend product grid$/
     * @When /^(?:|I )reset "(?P<filterName>[\w\s\:\(\)]+)" filter in "(?P<filterPosition>[\w\s]+)" sidebar$/
     *
     * @param string $filterName
     * @param string $filterPosition
     */
    public function resetFilterOfGrid(
        string $filterName,
        $filterPosition = 'FrontendProductGridFilters'
    ) {
        /** @var FrontendProductGridFilters $gridFilters */
        $gridFilters = $this->createElement($filterPosition);

        $gridFilters->resetFilter($filterName);
    }

    /**
     * Asserts frontend product grid filter hint value.
     * Example: I should see "One, Two" hint for "CountField" filter in frontend product grid
     * Example: I should see "One, Two" hint for "CountField" filter in "product" sidebar
     *
     * @codingStandardsIgnoreStart
     *
     * @When /^(?:|I )should see "(?P<filterHint>[\w\s\,\"\:\(\)]+)" hint for "(?P<filterName>[\w\s\:\(\)]+)" filter in frontend product grid$/
     * @When /^(?:|I )should see "(?P<filterHint>[\w\s\,\"\:\(\)]+)" hint for "(?P<filterName>[\w\s\:\(\)]+)" filter in "(?P<filterPosition>[\w\s]+)" sidebar$/
     *
     * @codingStandardsIgnoreEnd
     *
     * @param string $filterName
     * @param string $filterHint
     * @param string $filterPosition
     */
    public function assertsFilterHintValue(
        string $filterName,
        string $filterHint,
        $filterPosition = 'FrontendProductGridFilters'
    ) {
        /** @var FrontendProductGridFilters $gridFilters */
        $gridFilters = $this->createElement($filterPosition);

        self::assertEquals(
            $filterHint,
            $gridFilters->getAppliedFilterHint($filterName),
            sprintf('Can not see "%s" hint for "%s" filter in frontend product grid', $filterHint, $filterName)
        );
    }

    //@codingStandardsIgnoreStart
    /**
     * Asserts that there's no hint shown for a filter in frontend product grid.
     * Example: I should not see hint for "TagField" filter in frontend product grid
     * Example: I should not see hint for "TagField" filter in "product" sidebar
     *
     * @When /^(?:|I )should not see hint for "(?P<filterName>[\w\s\:\(\)]+)" filter in frontend product grid$/
     * @When /^(?:|I )should not see hint for "(?P<filterName>[\w\s\:\(\)]+)" filter in "(?P<filterPosition>[\w\s]+)" sidebar$/
     * @param string $filterName
     * @param string $filterPosition
     */
    //@codingStandardsIgnoreEnd
    public function assertsFilterHasNoHint(
        string $filterName,
        $filterPosition = 'FrontendProductGridFilters'
    ) {
        /** @var FrontendProductGridFilters $gridFilters */
        $gridFilters = $this->createElement($filterPosition);

        self::assertFalse(
            $gridFilters->hasFilterHint($filterName),
            sprintf('There is a hint for "%s" filter in frontend product grid', $filterName)
        );
    }

    /**
     * Example: (?:|I )open page number 3 of frontend product grid
     *
     * @When /^(?:|I )open page number (?P<pageNumber>[\w]+) of frontend product grid$/
     *
     * @param string $pageNumber
     */
    public function generateUrlForFrontendProductSearchGrid($pageNumber)
    {
        $path = $this->getAppContainer()
            ->get('oro_datagrid.helper.route')
            ->generate(
                'oro_product_frontend_product_index',
                'frontend-product-search-grid',
                [
                    'i' => $pageNumber,
                    'p' => 25
                ]
            );

        $this->visitPath($path);
    }
}
