<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

abstract class ProductTemplate extends Element
{
    use PageObjectDictionary;

    const ELEMENT_PREFIX = '';

    /**
     * @param string $groupName
     * @param TableNode $table
     */
    abstract public function assertGroupWithValue($groupName, TableNode $table);

    abstract public function assertPrices(TableNode $table);

    protected function getPricesElement()
    {
        return $this->createElement(sprintf('%s %s', static::ELEMENT_PREFIX, 'Prices'));
    }
}
