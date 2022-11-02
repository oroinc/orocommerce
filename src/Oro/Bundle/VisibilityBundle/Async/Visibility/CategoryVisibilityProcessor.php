<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Oro\Bundle\VisibilityBundle\Async\Topic\ResolveCategoryVisibilityTopic;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;

/**
 * Resolves visibility by a category.
 */
class CategoryVisibilityProcessor extends AbstractVisibilityProcessor implements TopicSubscriberInterface
{
    public static function getSubscribedTopics(): array
    {
        return [ResolveCategoryVisibilityTopic::getName()];
    }

    protected function getResolvedVisibilityClassName(): string
    {
        return CategoryVisibilityResolved::class;
    }

    protected function resolveVisibility(array $body): void
    {
        $this->cacheBuilder->resolveVisibilitySettings($this->getVisibility($body));
    }
}
