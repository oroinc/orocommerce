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
     * @param string $title
     */
    public function assertTitle($title)
    {
        $currentTitleText = $this->getStepTitle();
        static::assertStringContainsString(
            $title,
            $currentTitleText,
            sprintf('Current title "%s" does not contain expected "%s" ', $currentTitleText, $title)
        );
    }

    /**
     * @param string $title
     */
    public function assertNotTitle($title)
    {
        $currentTitleText = $this->getStepTitle();
        static::assertStringNotContainsString(
            $title,
            $currentTitleText,
            sprintf('Current title "%s" was not expected to contain "%s"', $currentTitleText, $title)
        );
    }

    public function getStepTitle(): string
    {
        $currentTitle = $this->getElement('CheckoutStepTitle');
        self::assertTrue($currentTitle->isValid(), 'Checkout step title not found, maybe you are on another page?');

        return trim($currentTitle->getText());
    }

    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        return $this->getElements('CheckoutStepLineItem');
    }
}
