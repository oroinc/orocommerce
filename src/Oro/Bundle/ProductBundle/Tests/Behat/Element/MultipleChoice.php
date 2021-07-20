<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\MultipleChoice as BaseMultipleChoice;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class MultipleChoice extends BaseMultipleChoice
{
    /**
     * Get visible miltiselect checkboxes widget.
     * There are only one visible widget can be on the page
     *
     * @return NodeElement
     */
    protected function getWidget()
    {
        $widgets = $this->getPage()
            ->findAll(
                'css',
                'body div.filter-box div.dropdown-menu ul.ui-multiselect-checkboxes'
            );

        /** @var NodeElement $widget */
        foreach ($widgets as $widget) {
            if ($widget->isVisible()) {
                return $widget;
            }
        }

        self::fail('Can\'t find widget on page or it\'s not visible');
    }

    public function close()
    {
        if ($dropDownMask = $this->getPage()->find('css', '.oro-dropdown-mask')) {
            $dropDownMask->click();
        } elseif ($this->isOpen()) {
            $this->find('css', '.filter-criteria-selector span.filter-criteria-selector-icon')->click();
        }
    }

    public function getChoices(): array
    {
        $this->open();
        // Wait for open widget
        $this->getDriver()->waitForAjax();

        $widget = $this->getWidget();
        $inputs = $widget->findAll('css', 'li span.custom-checkbox__text');

        $choices = [];
        /** @var Element $input */
        foreach ($inputs as $input) {
            $choices[] = trim($input->getText());
        }

        $this->close();

        return $choices;
    }
}
