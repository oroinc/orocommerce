<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

/**
 * Ensures the checkout summary/footer panel is expanded before interacting with elements inside it.
 */
class CheckoutSummaryPanelElement extends Element
{
    #[\Override]
    protected function init()
    {
        $this->ensureCheckoutSummaryExpanded();
    }

    private function ensureCheckoutSummaryExpanded(): void
    {
        $page = $this->getPage();
        $toggle = $page->find('css', '.summary-collapse__toggle');
        if (null === $toggle || !$toggle->isVisible()) {
            return;
        }

        $ariaExpanded = $toggle->getAttribute('aria-expanded');
        $isCollapsed = $toggle->hasClass('collapsed') || 'false' === $ariaExpanded;
        if (!$isCollapsed) {
            return;
        }

        $toggle->click();

        // Wait until the toggle reflects the expanded state.
        $this->spin(function () {
            $toggle = $this->getPage()->find('css', '.summary-collapse__toggle');
            if (null === $toggle) {
                return true;
            }

            $ariaExpanded = $toggle->getAttribute('aria-expanded');
            return !$toggle->hasClass('collapsed') && 'true' === $ariaExpanded;
        }, 3);
    }
}
