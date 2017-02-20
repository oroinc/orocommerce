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
        $row->find('css', 'div.entity-create-or-select-container')->click();

        /** @var NodeElement|null $priceList */
        $priceList = null;
        $this->spin(function (FeatureContext $context) use ($priceListName, &$priceList) {
            $priceList = $this->getSession()->getPage()->find('named', ['content', $priceListName]);
            return (bool)$priceList;
        });
        $priceList->click();
        $this->waitForAjax();
    }

    /**
     * @When I drag :rowNum row on top in price lists collection
     * @param int $rowNum
     */
    public function dragPriceListOnTop($rowNum)
    {
        --$rowNum;
        $this->getPriceListRow($rowNum);
        $this->getSession()->executeScript('
            $(document).ready(function(){
                var lastRow = $("div.pricing-price-list tbody tr.pricing-price-list-item").eq(' . $rowNum . ');
                $("div.pricing-price-list tbody").prepend(lastRow);
                $("div.pricing-price-list .sortable-wrapper").sortable("option", "stop")();
            })
        ');
    }

    /**
     * @Then I should see Drag-n-Drop icon present on price list line
     */
    public function assertDragNDropIconOnPriceListLine()
    {
        $row = $this->getPriceListRow(0);
        self::assertNotEmpty($row->find('css', 'i.handle'));
    }

    /**
     * @Then I should see that :priceListName price list is in :rowNum row
     * @param string $priceListName
     * @param int $rowNum
     */
    public function assertPriceListNameInRow($priceListName, $rowNum)
    {
        $row = $this->getPriceListRow(--$rowNum);
        self::assertTrue($row->has('named', ['content', $priceListName]));
    }

    /**
     * @param int $rowNum
     * @return NodeElement
     */
    protected function getPriceListRow($rowNum)
    {
        $priceLists = $this->elementFactory->createElement('PriceListCollection');
        $rows = $priceLists->findAll('css', 'tbody tr');
        self::assertNotEmpty($rows[$rowNum]);
        return $rows[$rowNum];
    }
}
