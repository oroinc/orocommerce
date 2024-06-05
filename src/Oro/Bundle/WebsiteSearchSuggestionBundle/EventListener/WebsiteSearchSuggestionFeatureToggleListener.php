<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Event\FeatureChange;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Initiates the generation of website search suggestions.
 */
class WebsiteSearchSuggestionFeatureToggleListener
{
    public function __construct(private MessageProducerInterface $producer)
    {
    }

    public function onChange(FeatureChange $event): void
    {
        if (!$event->isEnabled()) {
            return;
        }

        $this->producer->send(GenerateSuggestionsTopic::getName(), []);
    }
}
