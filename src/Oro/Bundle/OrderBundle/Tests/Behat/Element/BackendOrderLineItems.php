<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;

/**
 * Backend Order Line Items Table element representation
 */
class BackendOrderLineItems extends Table
{
    const TABLE_ROW_ELEMENT = 'BackendOrderLineItem';
    const TABLE_ROW_STRICT_ELEMENT = 'BackendOrderLineItem';

    /**
     * @param string $content
     * @param string $action
     */
    public function clickActionLink($content, $action)
    {
        /** @var BackendOrderLineItem $row */
        $row = $this->getRowByContent($content);
        $link = $row->getActionLink($action);
        $link->click();
    }
}
