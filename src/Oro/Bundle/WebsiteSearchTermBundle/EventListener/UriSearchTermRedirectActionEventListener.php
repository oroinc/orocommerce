<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\EventListener;

use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Event\SearchTermRedirectActionEvent;
use Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType\BasicRedirectActionHandler;

/**
 * Forwards/redirects user to a URI specified in a {@see SearchTerm}.
 */
class UriSearchTermRedirectActionEventListener
{
    public function __construct(private readonly BasicRedirectActionHandler $basicRedirectActionHandler)
    {
    }

    public function onRedirectAction(SearchTermRedirectActionEvent $event): void
    {
        $searchTerm = $event->getSearchTerm();
        if (!$this->supports($searchTerm)) {
            return;
        }

        $redirectUri = $searchTerm->getRedirectUri();
        if (!$redirectUri) {
            return;
        }

        $requestEvent = $event->getRequestEvent();

        $response = $this->basicRedirectActionHandler->getResponse(
            $requestEvent->getRequest(),
            $searchTerm,
            $redirectUri
        );

        if ($response !== null) {
            $requestEvent->setResponse($response);
        }
    }

    private function supports(SearchTerm $searchTerm): bool
    {
        return $searchTerm->getActionType() === 'redirect'
            && $searchTerm->getRedirectActionType() === 'uri';
    }
}
