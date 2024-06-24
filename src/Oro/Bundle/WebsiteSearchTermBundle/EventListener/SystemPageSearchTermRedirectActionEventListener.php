<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\EventListener;

use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Event\SearchTermRedirectActionEvent;
use Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType\BasicRedirectActionHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Forwards/redirects user to a system page specified in a {@see SearchTerm}.
 */
class SystemPageSearchTermRedirectActionEventListener
{
    public function __construct(
        private readonly BasicRedirectActionHandler $basicRedirectActionHandler,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function onRedirectAction(SearchTermRedirectActionEvent $event): void
    {
        $searchTerm = $event->getSearchTerm();
        if (!$this->supports($searchTerm)) {
            return;
        }

        $systemPage = $searchTerm->getRedirectSystemPage();
        if (!$systemPage) {
            return;
        }

        $requestEvent = $event->getRequestEvent();
        $redirectUrl = $this->urlGenerator->generate($systemPage);

        $response = $this->basicRedirectActionHandler->getResponse(
            $requestEvent->getRequest(),
            $searchTerm,
            $redirectUrl
        );

        if ($response !== null) {
            $requestEvent->setResponse($response);
        }
    }

    private function supports(SearchTerm $searchTerm): bool
    {
        return $searchTerm->getActionType() === 'redirect'
            && $searchTerm->getRedirectActionType() === 'system_page';
    }
}
