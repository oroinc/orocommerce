<?php

namespace Oro\Bundle\RFPBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

class RequestForQuote extends Element
{
    /**
     * @param string $title
     */
    public function assertTitle($title)
    {
        $titleElement = $this->findElementContains('RequestForQuoteTitle', $title);
        self::assertTrue($titleElement->isValid(), sprintf('Title "%s", was not match to current title', $title));
    }

    /**
     * Workflow status (Customer Status) matching on frontend RFQ view page
     * @param string $text
     */
    public function assertStatus($text)
    {
        $el = $this->find('css', 'section.page-content');
        preg_match('/Status: ([^\n]+)/', $el->getHtml(), $matches);

        self::assertArrayHasKey(1, $matches, 'No status present on page.');

        self::assertEquals(
            $text,
            $matches[1],
            sprintf('Request status is not equal to %s', $text)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function assertPageContainsValue($label, $value)
    {
        /* @var TableRow $rowElement */
        $rowElement = $this->findElementContains('TableRow', $label);

        if (!$rowElement->isIsset()) {
            self::fail(sprintf('Can\'t find "%s" label', $label));
        }

        if ($rowElement->getCellByNumber(1)->getText() === Form::normalizeValue($value)) {
            return;
        }

        self::fail(sprintf('Found "%s" label, but it doesn\'t have "%s" value', $label, $value));
    }
}
