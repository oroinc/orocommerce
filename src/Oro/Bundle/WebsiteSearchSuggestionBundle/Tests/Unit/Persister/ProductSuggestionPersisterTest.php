<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\Persister;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\ProductSuggestionRepository;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Event\ProductSuggestionPersistEvent;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Persister\ProductSuggestionPersister;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ProductSuggestionPersisterTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private ProductSuggestionRepository&MockObject $repository;

    private ProductSuggestionPersister $persister;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->repository = $this->createMock(ProductSuggestionRepository::class);

        $this->doctrine
            ->expects(self::any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->persister = new ProductSuggestionPersister($this->doctrine, $this->eventDispatcher);
    }

    public function testThatProductSuggestionsPersisted(): void
    {
        $productsBySuggestions = [1 => [1, 2, 3], 2 => [1, 3, 5]];

        $this->repository
            ->expects(self::once())
            ->method('insertProductSuggestions')
            ->with($productsBySuggestions)
            ->willReturn([11, 12, 13, 21, 23, 25]);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(function (ProductSuggestionPersistEvent $event) {
                self::assertEquals(
                    [11, 12, 13, 21, 23, 25],
                    $event->getPersistedProductSuggestionIds()
                );
                return true;
            }));


        $this->persister->persistProductSuggestions($productsBySuggestions);
    }

    public function testPersistProductSuggestionsWhenNoInsertedSuggestions(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('insertProductSuggestions')
            ->with([])
            ->willReturn([]);

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');


        $this->persister->persistProductSuggestions([]);
    }
}
