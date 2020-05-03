<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Oro\Bundle\VisibilityBundle\Async\Topics;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;

/**
 * Resolves visibility by Product.
 */
class ProductVisibilityProcessor extends AbstractVisibilityProcessor implements TopicSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::RESOLVE_PRODUCT_VISIBILITY];
    }

    /**
     * {@inheritDoc}
     */
    protected function getResolvedVisibilityClassName(): string
    {
        return ProductVisibilityResolved::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveVisibility(array $body): void
    {
        $this->cacheBuilder->resolveVisibilitySettings($this->getVisibility($body));
    }
}
