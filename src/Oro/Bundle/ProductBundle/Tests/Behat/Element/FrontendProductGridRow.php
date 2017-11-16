<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\FrontendBundle\Tests\Behat\Element\GridRow as BaseGridRow;

class FrontendProductGridRow extends BaseGridRow
{
    /**
     * @param int $number Row index number starting from 0
     * @return NodeElement
     */
    public function getCellByNumber($number)
    {
        if ($number !== 0) {
            throw new \LogicException(sprintf(
                'Frontend product grid has only one column with number 0, column with %d number not found',
                $number
            ));
        }

        return $this;
    }
}
