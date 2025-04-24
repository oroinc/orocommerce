<?php

namespace Oro\Bundle\CommerceBundle\ContentWidget\Provider;

/**
 * Abstraction for a registry that returns scorecard providers.
 */
interface ScorecardsRegistryInterface
{
    public function getProviders(): iterable;

    public function getProviderByName(string $name): ?ScorecardInterface;
}
