<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class AllCoupons extends Page
{
    #[\Override]
    public function open(array $parameters = [])
    {
        $this->getMainMenu()->openAndClick('Marketing/Promotions/Coupons');
    }
}
