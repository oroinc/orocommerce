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
     * @When I add price list :priceListName into price lists collection
     * @param string $priceListName
     */
    public function addPriceListToCollection($priceListName)
    {
        $priceLists = $this->elementFactory->createElement('PriceListCollection');
        $priceLists->find('named', ['content', 'Add Price List'])->click();
        $priceLists->find('named', ['content' ,'Choose a Price List...'])->click();
        $this->waitForAjax();
        $this->getSession()->getPage()->find('named', ['content', $priceListName])->click();
        $this->waitForAjax();
    }

    /**
     * @When I set priority :priority to price list :priceListName
     * @param int $priority
     * @param string $priceListName
     */
    public function setPriorityToPriceList($priority, $priceListName)
    {
        $priceLists = $this->elementFactory->createElement('PriceListCollection');
        /** @var NodeElement $line */
        foreach ($priceLists->findAll('css', 'tr.pricing-price-list-item') as $line) {
            if ($line->has('named', ['content', $priceListName])) {
                $priorityInput = $line->find('css', 'input.priority');
                $priorityInput->focus();
                $priorityInput->setValue($priority);
                $priorityInput->blur();
                return;
            }
        }
    }
}
