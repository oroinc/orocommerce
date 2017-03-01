<?php

namespace Oro\Bundle\PricingBundle\Tests\Behat\Context;

use Behat\Mink\Element\NodeElement;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @When I choose a price list :priceListName in :rowNum row
     * @param string $priceListName
     * @param int $rowNum
     */
    public function choosePriceListInRow($priceListName, $rowNum)
    {
        $row = $this->getPriceListRow(--$rowNum);
        $row->find('css', 'button.entity-select-btn')->click();
        $this->waitForAjax();
        $priceList = $this->spin(function (FeatureContext $context) use ($priceListName) {
            $priceList = $context->getPage()->find('named', ['content', $priceListName]);
            return $priceList ? $priceList : false;
        });
        $priceList->click();
        $this->waitForAjax();
    }

    /**
     * @When I drag :rowNum row to the top in price lists table
     * @param int $rowNum
     */
    public function dragPriceListToTop($rowNum)
    {
        --$rowNum;
        $this->getPriceListRow($rowNum);
        $this->getSession()->executeScript('
            $(document).ready(function() {
                var lastRow = $("div.pricing-price-list tbody tr.pricing-price-list-item").eq(' . $rowNum . ');
                $("div.pricing-price-list tbody").prepend(lastRow);
                $("div.pricing-price-list .sortable-wrapper").sortable("option", "stop")();
            })
        ');
    }

    /**
     * @Then I should not see :text in price lists table
     * @param string $text
     */
    public function iShouldNotSeeTextInPriceListCollection($text)
    {
        $priceLists = $this->elementFactory->createElement('PriceListCollection');
        self::assertFalse(
            $priceLists->has('named', ['content', $text]),
            "There is text '$text' presents in price lists table"
        );
    }

    /**
     * @Then I should see drag-n-drop icon present on price list line
     */
    public function assertDragNDropIconOnPriceListLine()
    {
        $row = $this->getPriceListRow(0);
        self::assertNotEmpty($row->find('css', 'i.handle'), 'There is no drag-n-drop icon in first price list row');
    }

    /**
     * @Then I should see that :priceListName price list is in :rowNum row
     * @param string $priceListName
     * @param int $rowNum
     */
    public function assertPriceListNameInRow($priceListName, $rowNum)
    {
        $row = $this->getPriceListRow(--$rowNum);
        self::assertTrue(
            $row->has('named', ['content', $priceListName]),
            "There is no price list with name '$priceListName' in price lists table"
        );
    }

    /**
     * @param int $rowNum
     * @return NodeElement
     */
    protected function getPriceListRow($rowNum)
    {
        $priceLists = $this->elementFactory->createElement('PriceListCollection');
        $rows = $priceLists->findAll('css', 'tbody tr');
        self::assertNotEmpty($rows[$rowNum], "There is no row '$rowNum' in price lists table");
        return $rows[$rowNum];
    }
}
