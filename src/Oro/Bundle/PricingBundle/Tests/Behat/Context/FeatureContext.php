<?php

namespace Oro\Bundle\PricingBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\FormBundle\Tests\Behat\Element\Select2Entity;
use Oro\Bundle\ProductBundle\Tests\Behat\Element\ProductPriceCollection;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

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
}
