<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

class CollectionTableRow extends TableRow
{
    /**
     * @param string $action
     * @return NodeElement
     */
    public function getActionLink($action): NodeElement
    {
        $link = $this->find('named', ['link', ucfirst($action)]);

        static::assertNotNull($link, sprintf('Row "%s" has no "%s" action', $this->getText(), $action));

        return $link;
    }
}
