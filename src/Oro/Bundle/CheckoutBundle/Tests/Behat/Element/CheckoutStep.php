<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Behat\Element;

use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\LineItemsAwareInterface;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\SubtotalAwareInterface;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\Subtotals;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

/**
 * CheckoutStep element with getSubtotal, assertTitle and getLineItems methods
 */
class CheckoutStep extends Element implements LineItemsAwareInterface, SubtotalAwareInterface
{
    /**
     * @param string $subtotalName
     * @return string
     */
    public function getSubtotal($subtotalName)
    {
        /** @var Subtotals $subtotals */
        $subtotals = $this->getElement('Subtotals');

        return $subtotals->getSubtotal($subtotalName);
    }

    /**
     * @param $title
     * @return bool
     */
    public function assertTitle($title)
    {
        $currentTitle = $this->getElement('CheckoutStepTitle');
        self::assertTrue($currentTitle->isValid(), 'Checkout step title not found, maybe you are on another page?');

        $currentTitleText = $currentTitle->getText();
        self::assertContains(
            $title,
            $currentTitleText,
            sprintf('Expected title "%s", does not contains in "%s" current title', $title, $currentTitleText)
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        return $this->getElements('CheckoutStepLineItem');
    }
}
