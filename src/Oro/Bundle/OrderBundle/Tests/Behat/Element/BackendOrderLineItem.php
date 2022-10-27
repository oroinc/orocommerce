<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

/**
 * Backend Order Line Item Row elment representation
 */
class BackendOrderLineItem extends TableRow
{
    /**
     * @param string $action
     * @return NodeElement
     */
    public function getActionLink($action)
    {
        $link = $this->findActionLink($action);
        self::assertNotNull($link, sprintf('Row "%s" has no "%s" action', $this->getText(), $action));

        return $link;
    }

    /**
     * @param string $action text of button - Create, Edit, Delete etc.
     * @return NodeElement|null
     */
    public function findActionLink($action)
    {
        $link = $this->find('xpath', sprintf('//button[contains(@class,"%s")]', strtolower($action)));

        static::assertNotNull(
            $link,
            sprintf('BackendOrderLineItem "%s" has no "%s" action', $this->getText(), $action)
        );

        return $link;
    }
}
