<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\FirstPageLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\NextPageLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\PrevPageLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder as ApiDoc;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Adds "first", "prev" and "next" pagination links
 * to "products" association of ProductCollection entity.
 * @link https://jsonapi.org/format/#fetching-pagination
 */
class AddPaginationLinksForProductCollectionProducts implements ProcessorInterface
{
    private const PRODUCTS_ASSOCIATION = 'products';

    private RestRoutesRegistry $routesRegistry;
    private FilterNamesRegistry $filterNamesRegistry;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        RestRoutesRegistry $routesRegistry,
        FilterNamesRegistry $filterNamesRegistry,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->routesRegistry = $routesRegistry;
        $this->filterNamesRegistry = $filterNamesRegistry;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $entityMetadata = $context->getMetadata();
        if (null === $entityMetadata) {
            return;
        }

        $association = $entityMetadata->getAssociation(self::PRODUCTS_ASSOCIATION);
        if (null === $association) {
            return;
        }

        $requestType = $context->getRequestType();
        $baseLink = new RouteLinkMetadata(
            $this->urlGenerator,
            $this->routesRegistry->getRoutes($requestType)->getItemRouteName(),
            [
                'entity' => DataAccessorInterface::OWNER_ENTITY_TYPE,
                'id'     => DataAccessorInterface::OWNER_ENTITY_ID
            ]
        );
        $pageNumberFilterName = $this->filterNamesRegistry
            ->getFilterNames($requestType)
            ->getPageNumberFilterName();
        $queryStringAccessor = $context->getFilterValues();

        $association->addRelationshipLink(
            ApiDoc::LINK_FIRST,
            new FirstPageLinkMetadata($baseLink, $pageNumberFilterName, $queryStringAccessor)
        );
        $association->addRelationshipLink(
            ApiDoc::LINK_PREV,
            new PrevPageLinkMetadata($baseLink, $pageNumberFilterName, $queryStringAccessor)
        );
        $association->addRelationshipLink(
            ApiDoc::LINK_NEXT,
            new NextPageLinkMetadata($baseLink, $pageNumberFilterName, $queryStringAccessor)
        );
    }
}
