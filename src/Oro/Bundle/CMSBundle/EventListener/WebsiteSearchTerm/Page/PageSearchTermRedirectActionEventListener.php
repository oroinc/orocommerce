<?php

namespace Oro\Bundle\CMSBundle\EventListener\WebsiteSearchTerm\Page;

use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Event\SearchTermRedirectActionEvent;
use Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType\BasicRedirectActionHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Forwards/redirects user to a page specified in a {@see SearchTerm}.
 */
class PageSearchTermRedirectActionEventListener
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

        $page = $searchTerm->getRedirectCmsPage();
        if (!$page) {
            return;
        }

        $requestEvent = $event->getRequestEvent();
        $redirectUrl = $this->urlGenerator->generate('oro_cms_frontend_page_view', ['id' => $page->getId()]);

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
            && $searchTerm->getRedirectActionType() === 'cms_page';
    }
}
