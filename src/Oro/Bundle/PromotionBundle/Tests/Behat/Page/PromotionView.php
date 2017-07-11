<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Page;

use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class PromotionView extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        $this->getMainMenu()->openAndClick('Marketing/Promotions/Promotions');

        /** @var Grid $grid */
        $grid = $this->elementFactory->createElement('Grid');
        $grid->clickActionLink($parameters['title'], 'View');
    }
}
