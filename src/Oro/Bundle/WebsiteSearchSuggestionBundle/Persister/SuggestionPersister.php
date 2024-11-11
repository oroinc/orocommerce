<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Persister;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\SuggestionRepository;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Event\SuggestionPersistEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Persist suggestions to database
 */
class SuggestionPersister
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @param array<int, array<string, array<int>> $localizedPhrasesByProductId
     *  [
     *      1 => [ // Localization ID
     *          'suggestion phrase' => [
     *              1, 2, 3 // Product IDs
     *          ]
     *      ]
     *  ]
     *
     * @return array<int, array<int>>
     *  [
     *      42 => [ // Suggestion id
     *          1, 2, 3 // Product IDs
     *      ],
     *      // ...
     *  ]
     */
    public function persistSuggestions(int $organizationId, array $localizedPhrasesByProductId): array
    {
        $repository = $this->getSuggestionRepository();

        $result = [
            'inserted' => [],
            'skipped' => []
        ];

        foreach ($localizedPhrasesByProductId as $localizationId => $productsByPhrase) {
            $suggestions = [];

            foreach (array_keys($productsByPhrase) as $phrase) {
                $phrase = strval($phrase);
                $suggestions[$phrase] = [
                    'phrase' => $phrase,
                    'words_count' => count(explode(' ', $phrase)),
                ];
            }

            $savedSuggestions = $repository->saveSuggestions($organizationId, $localizationId, $suggestions);

            foreach ($savedSuggestions as $type => $recordsList) {
                foreach ($recordsList as $record) {
                    $result[$type][$record['id']] = $productsByPhrase[$record['phrase']];
                }
            }
        }

        $event = new SuggestionPersistEvent();

        $insertedIds = array_keys($result['inserted']);

        $event->setPersistedSuggestionIds($insertedIds);

        $this->eventDispatcher->dispatch($event);

        $persistedSuggestions = array_intersect_key(
            $result['inserted'],
            array_flip($event->getPersistedSuggestionIds())
        );

        return $persistedSuggestions + $result['skipped'];
    }

    private function getSuggestionRepository(): SuggestionRepository
    {
        return $this->doctrine->getRepository(Suggestion::class);
    }
}
