<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\EventListener\TaxValueListener;
use Oro\Bundle\TaxBundle\Manager\TaxValueManager;

class TaxValueListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaxValueManager|\PHPUnit\Framework\MockObject\MockObject */
    private $taxValueManager;

    /** @var TaxValueListener */
    private $listener;

    protected function setUp(): void
    {
        $this->taxValueManager = $this->createMock(TaxValueManager::class);

        $this->listener = new TaxValueListener($this->taxValueManager);
    }

    public function testPostRemove()
    {
        $this->taxValueManager->expects($this->once())
            ->method('clear');

        $taxValue = new TaxValue();

        $objectManager = $this->createMock(ObjectManager::class);

        $event = new LifecycleEventArgs($taxValue, $objectManager);

        $this->listener->postRemove($taxValue, $event);
    }
}
