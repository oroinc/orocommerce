<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Event\FeatureChange;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\EventListener\WebsiteSearchSuggestionFeatureToggleListener;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class WebsiteSearchSuggestionFeatureToggleListenerTest extends \PHPUnit\Framework\TestCase
{
    private WebsiteSearchSuggestionFeatureToggleListener $listener;

    private MessageProducerInterface&MockObject $producer;

    #[\Override]
    protected function setUp(): void
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->listener = new WebsiteSearchSuggestionFeatureToggleListener($this->producer);
    }

    public function testThatMessagesProducedWhenFeatureEnabled(): void
    {
        $event = new FeatureChange('feature_name', true);

        $this->producer
            ->expects(self::once())
            ->method('send')
            ->with(GenerateSuggestionsTopic::getName());

        $this->listener->onChange($event);
    }

    public function testThatMessagesNotProductsWhenFeatureDisabled(): void
    {
        $event = new FeatureChange('feature_name', false);

        $this->listener->onChange($event);

        $this->producer
            ->expects(self::never())
            ->method('send');
    }
}
