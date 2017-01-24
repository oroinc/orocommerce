<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DPDBundle\Entity\DPDTransaction;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class DPDTransactionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new DPDTransaction(), [
            ['order', new Order()],
            ['labelFile', new File()],
            ['parcelNumbers', array()],
        ]);
    }
}
