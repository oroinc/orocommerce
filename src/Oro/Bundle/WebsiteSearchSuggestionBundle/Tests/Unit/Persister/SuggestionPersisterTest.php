<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\Persister;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\SuggestionRepository;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Event\SuggestionPersistEvent;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Persister\SuggestionPersister;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class SuggestionPersisterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private SuggestionPersister $suggestionPersister;

    private SuggestionRepository&MockObject $suggestionRepository;

    private EventDispatcherInterface&MockObject $eventDispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->suggestionRepository = $this->createMock(SuggestionRepository::class);

        $doctrine
            ->expects(self::any())
            ->method('getRepository')
            ->willReturn($this->suggestionRepository);

        $this->suggestionPersister = new SuggestionPersister($doctrine, $this->eventDispatcher);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testThatSuggestionPersisted(): void
    {
        $this->suggestionRepository
            ->expects(self::exactly(3))
            ->method('saveSuggestions')
            ->withConsecutive(
                [
                    1,
                    1,
                    [
                        'phrase1 localization1' => [
                            'phrase' => 'phrase1 localization1',
                            'words_count' => 2,
                        ],
                        'phrase2_localization1' => [
                            'phrase' => 'phrase2_localization1',
                            'words_count' => 1,
                        ]
                    ]
                ],
                [
                    1,
                    2,
                    [
                        'phrase1_localization2' => [
                            'phrase' => 'phrase1_localization2',
                            'words_count' => 1,
                        ],
                        'phrase2_localization2' => [
                            'phrase' => 'phrase2_localization2',
                            'words_count' => 1,
                        ]
                    ]
                ],
                [
                    1,
                    3,
                    [
                        12345 => [
                            'phrase' => '12345',
                            'words_count' => 1,
                        ],
                        'phrase2_localization3' => [
                            'phrase' => 'phrase2_localization3',
                            'words_count' => 1,
                        ]
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'inserted' => [
                        ['id' => 1, 'phrase' => 'phrase1 localization1']
                    ],
                    'skipped' => [
                        ['id' => 2, 'phrase' => 'phrase2_localization1']
                    ]
                ],
                [
                    'inserted' => [
                        ['id' => 3, 'phrase' => 'phrase1_localization2'],
                        ['id' => 4, 'phrase' => 'phrase2_localization2']
                    ],
                    'skipped' => []
                ],
                [
                    'inserted' => [
                        ['id' => 3, 'phrase' => '12345'],
                        ['id' => 4, 'phrase' => 'phrase2_localization3']
                    ],
                    'skipped' => []
                ],
            );

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(function (SuggestionPersistEvent $event) {
                    self::assertEquals(
                        [1, 3, 4],
                        $event->getPersistedSuggestionIds()
                    );
                    return true;
                })
            );

        $result = $this->suggestionPersister->persistSuggestions(
            1,
            [
                1 => [
                    'phrase1 localization1' => [1, 2],
                    'phrase2_localization1' => [5, 7]
                ],
                2 => [
                    'phrase1_localization2' => [1, 2],
                    'phrase2_localization2' => [3, 4]
                ],
                3 => [
                    12345 => [1, 2],
                    'phrase2_localization3' => [3, 4]
                ]
            ]
        );

        self::assertEquals([
                1 => [1, 2],
                3 => [1, 2],
                4 => [3, 4],
                2 => [5, 7]
        ], $result);
    }
}
