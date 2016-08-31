<?php

namespace Oro\Bundle\ShippingBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class CheckoutForm extends Element
{
    /**
     * @param string $shippingType
     * @return boolean
     */
    public function assertHas($shippingType)
    {
        $items = $this->getPage()->findAll('css', '.checkout__form__row');
        self::assertNotCount(0, $items, 'There are no shipping type');

        /** @var NodeElement $item */
        foreach ($items as $item) {
            $currentType = $item->find('css', '.input-widget')->getText();
            if (preg_match(sprintf('/%s/i', $shippingType), $currentType)) {
                return true;
            }
        }

        self::fail(sprintf('There are no shipping type with "%s" name', $shippingType));

    }
}
