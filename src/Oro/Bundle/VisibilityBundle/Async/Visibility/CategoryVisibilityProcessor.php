<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Oro\Bundle\VisibilityBundle\Async\Topics;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;

/**
 * Resolves visibility by a category.
 */
class CategoryVisibilityProcessor extends AbstractVisibilityProcessor implements TopicSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CHANGE_CATEGORY_VISIBILITY];
    }

    /**
     * {@inheritDoc}
     */
    protected function getResolvedVisibilityClassName(): string
    {
        return CategoryVisibilityResolved::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveVisibility(array $body): void
    {
        $this->cacheBuilder->resolveVisibilitySettings($this->getVisibility($body));
    }
}
