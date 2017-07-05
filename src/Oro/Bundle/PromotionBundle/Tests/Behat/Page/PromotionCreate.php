<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class PromotionCreate extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        $this->getMainMenu()->openAndClick('Marketing/Promotions/Promotions');
        $this->elementFactory->getPage()->clickLink('Create Promotion');
    }
}
