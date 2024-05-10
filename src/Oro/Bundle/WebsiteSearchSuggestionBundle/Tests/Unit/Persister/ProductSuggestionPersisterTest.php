<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\Persister;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\ProductSuggestionRepository;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Event\ProductSuggestionPersistEvent;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Persister\ProductSuggestionPersister;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ProductSuggestionPersisterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testThatProductSuggestionsPersisted(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $repository = $this->createMock(ProductSuggestionRepository::class);

        $doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $repository
            ->expects(self::once())
            ->method('insertProductSuggestions')
            ->willReturnOnConsecutiveCalls([11, 12, 13, 21, 23, 25]);

        $eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(function (ProductSuggestionPersistEvent $event) {
                self::assertEquals(
                    [11, 12, 13, 21, 23, 25],
                    $event->getPersistedProductSuggestionIds()
                );
                return true;
            }));

        $productSuggestionPersister = new ProductSuggestionPersister($doctrine, $eventDispatcher);

        $productSuggestionPersister->persistProductSuggestions([
            1 => [1, 2, 3],
            2 => [1, 3, 5]
        ]);
    }
}
