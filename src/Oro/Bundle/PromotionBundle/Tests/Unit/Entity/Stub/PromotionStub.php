<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\PromotionBundle\Entity\Promotion;

class PromotionStub extends Promotion
{
    public function __construct(?int $id = null)
    {
        parent::__construct();

        $this->id = $id;
    }
}
