<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\DataGridBundle\Tests\Behat\Element\GridFilters;

class FrontendProductGridFilters extends GridFilters
{
    /**
     * Resets the given filter.
     */
    public function resetFilter(string $filterName): void
    {
        $resetButton = $this->getFilterHint($filterName)->find('css', 'button.reset-filter');

        self::assertNotNull(sprintf('Could not find reset button for "%s" filter', $filterName));

        $resetButton->click();
    }

    /**
     * Returns hint for applied filter.
     */
    public function getAppliedFilterHint(string $filterName): string
    {
        $filterHintLabel = $this->getFilterHint($filterName)->find('css', 'span.filter-criteria-hint');

        self::assertNotNull(sprintf('Could not find filter hint label for "%s" filter', $filterName));

        return $filterHintLabel->getText();
    }

    /**
     * Checks if filter has hint.
     */
    public function hasFilterHint(string $filterName): bool
    {
        return $this->getFilterHint($filterName, false) ? true : false;
    }

    private function getFilterHint(string $filterName, bool $assertFound = true): ?NodeElement
    {
        $hintPrefix = sprintf('%s:', $filterName);
        $filterHint = $this->find('css', sprintf('span.filter-criteria-hint-item:contains("%s")', $hintPrefix));

        if ($assertFound) {
            self::assertNotNull(sprintf('Could not find hint for "%s" filter', $filterName));
            self::assertTrue(
                $filterHint->isVisible(),
                sprintf('Filter hint for "%s" filter is not visible', $filterName)
            );
        }

        if (!$filterHint->isVisible()) {
            return null;
        }

        return $filterHint;
    }
}
