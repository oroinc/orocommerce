<?php

namespace Oro\Bundle\CommerceBundle\ContentWidget\Provider;

/**
 * Abstraction for a provider that represent scorecard.
 */
interface ScorecardInterface
{
    public function getName(): string;

    public function getLabel(): string;

    public function isVisible(): bool;

    public function getData(): ?string;
}
