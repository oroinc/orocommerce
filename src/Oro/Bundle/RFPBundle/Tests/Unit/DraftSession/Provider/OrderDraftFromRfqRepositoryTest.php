<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\DraftSession\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\RFPBundle\DraftSession\Provider\OrderDraftFromRfqRepository;
use Oro\Bundle\RFPBundle\Entity\Request;
use PHPUnit\Framework\TestCase;

final class OrderDraftFromRfqRepositoryTest extends TestCase
{
    public function testSupportsReturnsTrueForRequest(): void
    {
        $repository = new OrderDraftFromRfqRepository();

        self::assertTrue($repository->supports(Request::class));
    }

    public function testSupportsReturnsFalseForOtherClass(): void
    {
        $repository = new OrderDraftFromRfqRepository();

        self::assertFalse($repository->supports(Order::class));
    }

    public function testHasEntityDraftReturnsFalse(): void
    {
        $repository = new OrderDraftFromRfqRepository();

        self::assertFalse($repository->hasEntityDraft(new Request(), 'draft-session-uuid'));
    }

    public function testFindEntityDraftReturnsNull(): void
    {
        $repository = new OrderDraftFromRfqRepository();

        self::assertNull($repository->findEntityDraft(new Request(), 'draft-session-uuid'));
    }
}
