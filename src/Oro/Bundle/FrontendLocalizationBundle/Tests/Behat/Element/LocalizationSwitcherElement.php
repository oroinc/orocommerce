<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class LocalizationSwitcherElement extends Element
{
    /**
     * @return array
     */
    public function getLocalizationNames()
    {
        $switcherOptions = $this->elementFactory->createElement('LocalizationSwitcherOptions');
        $options = array_map(function (NodeElement $a) {
            return $a->getText();
        }, $switcherOptions->findAll('css', 'li'));
        sort($options);

        return $options;
    }

    /**
     * @param $localizationName
     *
     * @return NodeElement|null
     */
    public function findLocalizationLink($localizationName)
    {
        return $this->elementFactory->createElement('LocalizationSwitcherOptions')->findLink($localizationName);
    }
}
