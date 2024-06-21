<?php

namespace Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchTermBundle\Event\SearchTermRedirectActionEvent;
use Oro\Bundle\WebsiteSearchTermBundle\Provider\SearchTermProvider;
use Oro\Bundle\WebsiteSearchTermBundle\RedirectActionType\BasicRedirectActionHandler;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches {@see SearchTermRedirectActionEvent} when a search event appears.
 */
class RedirectActionTypeSearchPageKernelListener implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    public const WEBSITE_SEARCH_TERM = 'website_search_term';

    private array $applicableRoutes = ['oro_product_frontend_product_search'];

    public function __construct(
        private SearchTermProvider $searchTermProvider,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function setApplicableRoutes(array $applicableRoutes): void
    {
        $this->applicableRoutes = $applicableRoutes;
    }

    public function onKernelEvent(RequestEvent $event): void
    {
        if (!$this->isApplicable($event)) {
            return;
        }

        $searchTerm = $this->searchTermProvider->getMostSuitableSearchTerm(
            $event->getRequest()->query->get('search')
        );

        $event->getRequest()->attributes->set(self::WEBSITE_SEARCH_TERM, $searchTerm);

        if (!$searchTerm || $searchTerm->getActionType() !== 'redirect') {
            return;
        }

        $this->eventDispatcher->dispatch(new SearchTermRedirectActionEvent(Product::class, $searchTerm, $event));
    }

    private function isApplicable(KernelEvent $event): bool
    {
        if ($event->getRequest()->attributes->has(self::WEBSITE_SEARCH_TERM)) {
            // Not applicable as a search term is already found and triggered.
            return false;
        }

        if ($event->getRequest()->query->get(BasicRedirectActionHandler::SKIP_SEARCH_TERM)) {
            // Not applicable as search term skip flag is found.
            return false;
        }

        if (!\in_array($event->getRequest()->get('_route'), $this->applicableRoutes, true)) {
            // Not applicable route.
            return false;
        }

        if (!$event->getRequest()->query->has('search')) {
            // Not applicable as there is no search phrase.
            return false;
        }

        return $this->isFeaturesEnabled();
    }
}
