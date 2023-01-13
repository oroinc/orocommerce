<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Oro\Bundle\VisibilityBundle\Async\Topic\ResolveProductVisibilityTopic;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;

/**
 * Resolves visibility by Product.
 */
class ProductVisibilityProcessor extends AbstractVisibilityProcessor implements TopicSubscriberInterface
{
    public static function getSubscribedTopics(): array
    {
        return [ResolveProductVisibilityTopic::getName()];
    }

    protected function getResolvedVisibilityClassName(): string
    {
        return ProductVisibilityResolved::class;
    }

    protected function resolveVisibility(array $body): void
    {
        $this->cacheBuilder->resolveVisibilitySettings($this->getVisibility($body));
    }
}
