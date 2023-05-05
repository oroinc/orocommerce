<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Metadata\DataAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\NextPageLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder as ApiDoc;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Adds metadata for "next" pagination link to "products" association of ProductCollection entity
 * to be able to return this link when a product collection is expanded (by the "include" filter).
 * @link https://jsonapi.org/format/#fetching-pagination
 */
class AddPaginationLinksForProductCollectionProductsAssociation implements ProcessorInterface
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
        /** @var MetadataContext $context */

        $entityMetadata = $context->getResult();
        if (null === $entityMetadata) {
            // metadata is not loaded
            return;
        }

        $association = $entityMetadata->getAssociation(self::PRODUCTS_ASSOCIATION);
        if (null === $association) {
            return;
        }

        if (!$association->hasRelationshipLink(ApiDoc::LINK_NEXT)) {
            $requestType = $context->getRequestType();
            $association->addRelationshipLink(
                ApiDoc::LINK_NEXT,
                new NextPageLinkMetadata(
                    new RouteLinkMetadata(
                        $this->urlGenerator,
                        $this->routesRegistry->getRoutes($requestType)->getItemRouteName(),
                        [
                            'entity' => DataAccessorInterface::OWNER_ENTITY_TYPE,
                            'id'     => DataAccessorInterface::OWNER_ENTITY_ID
                        ]
                    ),
                    $this->filterNamesRegistry->getFilterNames($requestType)->getPageNumberFilterName()
                )
            );
        }
    }
}
