<?php

namespace Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\EventListener\QuoteStatusListener;
use Oro\Bundle\SaleBundle\Tests\Unit\Stub\QuoteStub;
use PHPUnit\Framework\TestCase;

class QuoteStatusListenerTest extends TestCase
{
    private QuoteStatusListener $listener;
    private ManagerRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = self::createMock(ManagerRegistry::class);
        $this->listener = new QuoteStatusListener($this->registry);
    }

    public function testPrePersistWithInternalStatus(): void
    {
        $status = new TestEnumValue(Quote::INTERNAL_STATUS_DRAFT, 'draft');
        $quote = new QuoteStub();
        $quote->setInternalStatus($status);

        $this->listener->prePersist($quote);

        self::assertEquals($quote->getInternalStatus(), $status);
    }

    public function testPrePersistWithoutInternalStatus(): void
    {
        $status = new TestEnumValue(Quote::INTERNAL_STATUS_DRAFT, 'draft');

        $manager = self::createMock(ObjectManager::class);
        $manager
            ->expects(self::once())
            ->method('find')
            ->with('Extend\Entity\EV_Quote_Internal_Status', Quote::INTERNAL_STATUS_DRAFT)
            ->willReturn($status);

        $this->registry
            ->expects(self::once())
            ->method('getManager')
            ->willReturn($manager);

        $quote = new QuoteStub();
        $this->listener->prePersist($quote);

        self::assertEquals($quote->getInternalStatus(), $status);
    }
}
