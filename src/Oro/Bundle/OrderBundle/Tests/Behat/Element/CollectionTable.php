<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;

class CollectionTable extends Table
{
    public const TABLE_ROW_ELEMENT = 'CollectionTableRow';
    public const TABLE_ROW_STRICT_ELEMENT = 'CollectionTableRow';

    public function clickActionLink($content, $action)
    {
        /** @var CollectionTableRow $row */
        $row = $this->getRowByContent($content);
        $link = $row->getActionLink($action);
        $link->click();
    }
}
