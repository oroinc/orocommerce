<?php

namespace Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\ProductCollection;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\SearchEventListener;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\WebsiteSearchTermBundle\Provider\SearchTermProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Datagrid listener that detects a suitable {@see SearchTerm} and applies its product collection segment as query
 * condition to replace the original results with product collection segment products.
 */
class ProductCollectionSearchTermFilteringEventListener implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    private const SEARCH_TERM_PARAMETER_NAME = 'productCollectionSearchTermId';
    private const SEARCH_TERM_SEGMENT_PARAMETER_NAME = 'productCollectionSearchTermSegmentId';
    private const SEARCH_TERM_CONFIG_PATH = '[options][urlParams][productCollectionSearchTermId]';
    private const SEARCH_TERM_SEGMENT_CONFIG_PATH = '[options][urlParams][productCollectionSearchTermSegmentId]';

    private array $applicableRoutes = ['oro_product_frontend_product_search'];

    public function __construct(
        private RequestStack $requestStack,
        private SearchTermProvider $searchTermProvider
    ) {
    }

    public function setApplicableRoutes(array $applicableRoutes): void
    {
        $this->applicableRoutes = $applicableRoutes;
    }

    public function onPreBuild(PreBuild $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $parameterBag = $event->getParameters();
        if (!$request || !$this->isApplicable($request, $parameterBag)) {
            return;
        }

        [$searchTermId, $segmentId] = $this->getSearchTermId($request, $parameterBag);
        if (!$searchTermId) {
            return;
        }

        // Ensures that a segment will not be pre-filtered with the search phrase specified previously.
        $parameterBag->set(SearchEventListener::SKIP_FILTER_SEARCH_QUERY_KEY, '1');

        $datagridConfiguration = $event->getConfig();
        $datagridConfiguration->offsetSetByPath(self::SEARCH_TERM_CONFIG_PATH, $searchTermId);
        $datagridConfiguration->offsetSetByPath(self::SEARCH_TERM_SEGMENT_CONFIG_PATH, $segmentId);
    }

    private function getSearchTermId(Request $request, ParameterBag $parameterBag): array
    {
        if ($request->query->has('search')) {
            $searchTerm = $this->searchTermProvider->getMostSuitableSearchTerm($request->query->get('search'));
            if (
                !$searchTerm ||
                $searchTerm->getActionType() !== 'modify' ||
                !$searchTerm->getProductCollectionSegment()
            ) {
                return [null, null];
            }

            return [$searchTerm->getId(), $searchTerm->getProductCollectionSegment()->getId()];
        }

        $searchTermId = filter_var($parameterBag->get(self::SEARCH_TERM_PARAMETER_NAME, 0), FILTER_VALIDATE_INT);
        $segmentId = filter_var($parameterBag->get(self::SEARCH_TERM_SEGMENT_PARAMETER_NAME, 0), FILTER_VALIDATE_INT);

        return $searchTermId > 0 && $segmentId > 0 ? [$searchTermId, $segmentId] : [null, null];
    }

    public function onBuildAfter(BuildAfter $event): void
    {
        $datasource = $event->getDatagrid()->getDatasource();
        if (!$datasource instanceof SearchDatasource) {
            return;
        }

        $datagridConfiguration = $event->getDatagrid()->getConfig();
        $searchTermId = $datagridConfiguration->offsetGetByPath(self::SEARCH_TERM_CONFIG_PATH);
        $segmentId = $datagridConfiguration->offsetGetByPath(self::SEARCH_TERM_SEGMENT_CONFIG_PATH);
        if (!$searchTermId || !$segmentId) {
            return;
        }

        $datasource
            ->getSearchQuery()
            ->addWhere(Criteria::expr()->eq(sprintf('integer.assigned_to.search_term_%s', $searchTermId), $segmentId));
    }

    private function isApplicable(Request $request, ParameterBag $parameterBag): bool
    {
        if (!\in_array($request->get('_route'), $this->applicableRoutes, true)) {
            return false;
        }

        return $this->isFeaturesEnabled() &&
            ($request->query->has('search') || $parameterBag->has(self::SEARCH_TERM_PARAMETER_NAME));
    }
}
