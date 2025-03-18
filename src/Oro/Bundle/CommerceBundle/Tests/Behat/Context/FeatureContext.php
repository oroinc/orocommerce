<?php

namespace Oro\Bundle\CommerceBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\PyStringNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use PHPUnit\Framework\Assert;
use WebDriver\Exception\NoSuchElement;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Extracts and validates the chart tooltip (hint) data with dynamic date.
     *
     * Example:
     * Then chart tooltip should contain:
     * """
     * {
     *   "price": "$50"
     * }
     * """
     *
     * @Then /^chart tooltip should contain:$/
     */
    public function chartTooltipShouldContain(PyStringNode $string): void
    {
        $expectedData = json_decode($string->getRaw(), true);

        $element = $this->elementFactory->createElement('PurchaseVolumeChart');

        if (!$element->isValid()) {
            throw new NoSuchElement('Chart element "PurchaseVolumeChart" not found.');
        }

        $chartContent = $element->find('css', '.chart-content');

        if (!$chartContent->isValid()) {
            throw new \Exception('Chart content not found.');
        }

        $chartContent->mouseOver();

        $this->spin(function () use ($element) {
            return $element->find('css', '.flotr-hint__data')->isValid();
        }, 5);

        $monthElement = $element->find('css', '.flotr-hint__data');
        $priceElement = $element->find('css', '.flotr-hint__volume');

        if (!$monthElement || !$priceElement) {
            throw new \Exception('Chart tooltip data is missing.');
        }

        $actualPrice = trim($priceElement->getText());

        if (isset($expectedData['price']) && $actualPrice !== $expectedData['price']) {
            throw new \Exception(sprintf(
                'Expected price "%s", but found "%s"',
                $expectedData['price'],
                $actualPrice
            ));
        }
    }

    //@codingStandardsIgnoreStart
    /**
     * Example: I should see that "Dashboard Widget Count" contains "5" for "My Checkouts"
     *
     * @Then /^I should see that "Dashboard Widget Count" contains "(?P<expectedCount>\d+)" for "(?P<widgetTitle>[^"]+)"$/
     */
    //@codingStandardsIgnoreEnd
    public function assertWidgetCount($expectedCount, $widgetTitle)
    {
        $locator = sprintf(
            '//div[contains(@class, "dashboard-widget")]' .
            '[.//h2[contains(normalize-space(), "%s")]]' .
            '//div[contains(@class, "dashboard-widget__items")]' .
            '//strong[@data-role="items-count-value"]',
            $widgetTitle
        );

        $element = $this->getSession()->getPage()->find('xpath', $locator);

        if (!$element) {
            $session = $this->getSession();
            $page = $session->getPage();

            $titles = $page->findAll('xpath', '//h2[contains(@class, "dashboard-widget__title")]');
            $titleTexts = array_map(function ($title) {
                return trim($title->getText());
            }, $titles);

            throw new \Exception(sprintf(
                'Widget with title "%s" not found. Available titles: %s',
                $widgetTitle,
                implode(', ', $titleTexts)
            ));
        }

        $actualCount = trim($element->getText());

        Assert::assertEquals($expectedCount, $actualCount, sprintf(
            'Expected %s to contain "%s", but got "%s".',
            $widgetTitle,
            $expectedCount,
            $actualCount
        ));
    }
}
