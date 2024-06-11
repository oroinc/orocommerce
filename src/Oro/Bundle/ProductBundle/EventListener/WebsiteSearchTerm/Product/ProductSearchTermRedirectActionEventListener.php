<?php

namespace Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\Product;

use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Event\SearchTermRedirectActionEvent;
use Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType\BasicRedirectActionHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Forwards/redirects user to a product specified in a {@see SearchTerm}.
 */
class ProductSearchTermRedirectActionEventListener
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

        $product = $searchTerm->getRedirectProduct();
        if (!$product) {
            return;
        }

        $requestEvent = $event->getRequestEvent();
        $redirectUrl = $this->urlGenerator->generate(
            'oro_product_frontend_product_view',
            ['id' => $product->getId()]
        );

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
            && $searchTerm->getRedirectActionType() === 'product';
    }
}
