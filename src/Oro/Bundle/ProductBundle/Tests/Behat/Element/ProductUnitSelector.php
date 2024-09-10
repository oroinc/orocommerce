<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ProductUnitSelector extends Element
{
    const TYPE_TOGGLE = 'toggle';
    const TYPE_SELECT = 'select';
    const TYPE_SINGLE = 'single';

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $values = $this->getValues();

        self::assertContains($value, $values, 'Unknown product unit');

        $selectorType = $this->getSelectorType();
        if ($selectorType === self::TYPE_TOGGLE) {
            $parent = $this->getParent()->getParent();
            $valueElement = $parent->find('xpath', "//label[contains(text(), '$value')]");
            if ($valueElement->isVisible() && $valueElement->isValid()) {
                $valueElement->focus();
                $valueElement->click();
            } else {
                self::fail('Didn\'t set toggle value for product unit');
            }
        } elseif ($selectorType === self::TYPE_SELECT) {
            $select2 = $this->elementFactory->wrapElement('Select2Offscreen', $this);
            $select2->setValue($value);
        }
    }

    public function getSelectorType(): string
    {
        if ($this->hasClass('select2-offscreen')) {
            return self::TYPE_SELECT;
        }
        if ($this->hasClass('invisible') || $this->hasClass('toggle-input')) {
            return self::TYPE_TOGGLE;
        }

        return self::TYPE_SINGLE;
    }

    public function getValues(): array
    {
        if ($this->getTagName() == 'select') {
            return array_map(
                fn (NodeElement $element) => $element->getValue(),
                $this->findAll('css', 'option')
            );
        } elseif ($this->getAttribute('type') == 'hidden') {
            return [$this->getAttribute('value')];
        }
        return array_map(
            fn (NodeElement $element) => $element->getAttribute('value'),
            $this->getParent()->findAll('css', '[type="radio"]')
        );
    }

    public function isVisible()
    {
        $selectorType = $this->getSelectorType();
        if ($selectorType === self::TYPE_TOGGLE) {
            $parent = $this->getParent()->getParent();
            $toggleContainer = $parent->find('css', '.toggle-container');

            return $toggleContainer->isVisible();
        } elseif ($selectorType === self::TYPE_SELECT) {
            $select2 = $this->elementFactory->wrapElement('Select2Offscreen', $this);

            return $select2->isVisible();
        }

        $singleIeContainer = $this->getParent()->find('css', '[data-role="unit-label"]');

        return $singleIeContainer->isVisible();
    }
}
