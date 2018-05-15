<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Symfony\Component\DomCrawler\Crawler;

class InlineMatrixRowHeader extends Element
{
    /**
     * Try to guess header and return row number
     *
     * @param string $headerText Header of table row
     * @return int row number
     */
    public function getRowNumber($headerText)
    {
        $crawler = new Crawler($this->getHtml());

        $i = 0;
        $headers = [];

        /** @var \DOMElement $th */
        foreach ($crawler->filter('.matrix-order-widget__form__row') as $th) {
            $currentHeader = trim($th->textContent);
            if (strtolower($currentHeader) === strtolower($headerText)) {
                return $i;
            }

            $i++;
            $headers[] = $currentHeader;
        }

        self::fail(sprintf(
            'Can\'t find link with "%s" header, available headers: %s',
            $headerText,
            implode(', ', $headers)
        ));
    }

    /**
     * Checks if table header has such row name
     *
     * @param $headerText
     * @return bool
     */
    public function hasColumn($headerText)
    {
        $crawler = new Crawler($this->getHtml());

        /** @var \DOMElement $th */
        foreach ($crawler->filter('.matrix-order-widget__form__row') as $th) {
            if (strtolower($th->textContent) === strtolower($headerText)) {
                return true;
            }
        }

        return false;
    }
}
