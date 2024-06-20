<?php

declare(strict_types=1);

namespace Oro\Bundle\WebsiteSearchTermBundle\Event;

use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when a search term redirect action occurs.
 */
class SearchTermRedirectActionEvent extends Event
{
    public function __construct(
        private readonly string $entityClass,
        private readonly SearchTerm $searchTerm,
        private readonly RequestEvent $requestEvent
    ) {
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getSearchTerm(): SearchTerm
    {
        return $this->searchTerm;
    }

    public function getRequestEvent(): RequestEvent
    {
        return $this->requestEvent;
    }
}
