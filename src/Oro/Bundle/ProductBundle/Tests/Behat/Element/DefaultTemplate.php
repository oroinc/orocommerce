<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Tabs;

class DefaultTemplate extends ProductTemplate
{
    const ELEMENT_PREFIX = 'Default Page';

    public function assertGroupWithValue($groupName, TableNode $table)
    {
        $tabContainer = null;
        $tabContainers = $this->findAllElements('Tab Container');
        /** @var Tabs $element */
        foreach ($tabContainers as $element) {
            if ($element->hasTab($groupName)) {
                $tabContainer = $element;
                break;
            }
        }
        self::assertNotEmpty($tabContainer);

        $tabContainer->switchToTab($groupName);

        $activeTab = $tabContainer->getActiveTab();
        self::assertNotEmpty($activeTab);

        foreach ($table->getRows() as $row) {
            list($label, $value) = $row;
            static::assertStringContainsStringIgnoringCase(\sprintf('%s: %s', $label, $value), $activeTab->getText());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function assertPrices(TableNode $table)
    {
        $prices = $this->getPricesElement();

        foreach ($table->getRows() as $row) {
            list($label, $values) = $row;

            $priceLabel = $prices->find(
                'css',
                $this->selectorManipulator->addContainsSuffix('tr', $label)
            );

            if ($priceLabel === null) {
                self::fail(sprintf('Can\'t find "%s" price label', $label));
            }

            $values = Form::normalizeValue($values);
            $values = is_array($values) ? $values : [$values];

            foreach ($values as $value) {
                $priceValue = $priceLabel->getParent()->find(
                    'css',
                    $this->selectorManipulator->addContainsSuffix('td', $value)
                );

                if ($priceValue === null) {
                    self::fail(sprintf('Found "%s" price label, but it doesn\'t have "%s" value', $label, $value));
                }
            }
        }
    }
}
