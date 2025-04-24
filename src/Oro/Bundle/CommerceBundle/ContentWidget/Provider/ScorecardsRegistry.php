<?php

namespace Oro\Bundle\CommerceBundle\ContentWidget\Provider;

/**
 * Registry service that collect scorecards providers
 */
class ScorecardsRegistry implements ScorecardsRegistryInterface
{
    /**
     * @param iterable|ScorecardInterface[] $scorecards
     */
    public function __construct(
        private iterable $scorecards
    ) {
    }

    #[\Override]
    public function getProviders(): iterable
    {
        return $this->scorecards;
    }

    #[\Override]
    public function getProviderByName(string $name): ?ScorecardInterface
    {
        foreach ($this->scorecards as $scorecard) {
            if ($scorecard->getName() === $name) {
                return $scorecard;
            }
        }

        return null;
    }
}
