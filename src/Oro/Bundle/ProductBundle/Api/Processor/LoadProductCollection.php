<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\TargetConfigExtraBuilder;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\ProductBundle\Api\Model\ProductSearch;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\SerializationHelper;

/**
 * Loads product collection data using website search query.
 */
class LoadProductCollection implements ProcessorInterface
{
    private const ID_FIELD = 'id';
    private const PRODUCTS_ASSOCIATION = 'products';

    private ActionProcessorBagInterface $processorBag;
    private SerializationHelper $serializationHelper;
    private ValueNormalizer $valueNormalizer;

    public function __construct(
        ActionProcessorBagInterface $processorBag,
        SerializationHelper $serializationHelper,
        ValueNormalizer $valueNormalizer
    ) {
        $this->processorBag = $processorBag;
        $this->serializationHelper = $serializationHelper;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $productSearchContext = $this->searchProducts($context);

        $context->getConfig()->getField(self::PRODUCTS_ASSOCIATION)
            ->setTargetEntity($productSearchContext->getConfig());
        $context->getMetadata()->getAssociation(self::PRODUCTS_ASSOCIATION)
            ->setTargetMetadata($productSearchContext->getMetadata());
        $productSearchInfoRecords = $productSearchContext->getInfoRecords();
        if ($productSearchInfoRecords) {
            $context->addAssociationInfoRecords(self::PRODUCTS_ASSOCIATION, $productSearchInfoRecords);
        }

        $context->setResult($this->getNormalizedData($context, $productSearchContext->getResult()));

        // data are already normalized
        $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
    }

    private function getNormalizedData(SingleItemContext $context, array $normalizedProducts): array
    {
        $item = [
            self::ID_FIELD             => $context->getId(),
            self::PRODUCTS_ASSOCIATION => $normalizedProducts
        ];

        // execute post serialization handlers to be able to customize product collection data
        // by processors for "customize_loaded_data" action
        $items = $this->serializationHelper->processPostSerializeItems(
            [$item],
            $context->getConfig(),
            $context->getNormalizationContext()
        );

        return reset($items);
    }

    private function searchProducts(SingleItemContext $context): GetListContext
    {
        $productSearchProcessor = $this->processorBag->getProcessor(ApiAction::GET_LIST);
        /** @var GetListContext $productSearchContext */
        $productSearchContext = $productSearchProcessor->createContext();
        $productSearchContext->setVersion($context->getVersion());
        $productSearchContext->getRequestType()->set($context->getRequestType());
        $productSearchContext->setRequestHeaders($context->getRequestHeaders());
        $productSearchContext->setFilterValues($context->getFilterValues());
        $productSearchContext->setSharedData($context->getSharedData());
        $productSearchContext->setHateoas($context->isHateoasEnabled());
        $productSearchContext->setClassName(ProductSearch::class);
        $productSearchContext->setConfigExtras(TargetConfigExtraBuilder::buildGetListConfigExtras(
            $context->getConfigExtras(),
            self::PRODUCTS_ASSOCIATION,
            $this->getProductSearchEntityType($context->getRequestType()),
            $context->getConfig()->getField(self::PRODUCTS_ASSOCIATION)->getTargetEntity()->getIdentifierFieldNames()
        ));

        $productSearchContext->skipGroup(ApiActionGroup::SECURITY_CHECK);
        $productSearchContext->setLastGroup(ApiActionGroup::BUILD_QUERY);
        $productSearchProcessor->process($productSearchContext);
        $searchQuery = $productSearchContext->getQuery();
        if (!$searchQuery instanceof WebsiteSearchQuery) {
            throw new RuntimeException('The product search query was not built.');
        }

        $searchQuery->addWhere(
            Criteria::expr()->eq(sprintf('integer.assigned_to.variant_%s', $context->getId()), 1)
        );

        $productSearchContext->setLastGroup(null);
        $productSearchContext->setFirstGroup(ApiActionGroup::LOAD_DATA);
        $productSearchContext->skipGroup(ApiActionGroup::DATA_SECURITY_CHECK);
        $productSearchContext->skipGroup(ApiActionGroup::NORMALIZE_RESULT);
        $productSearchProcessor->process($productSearchContext);

        return $productSearchContext;
    }

    private function getProductSearchEntityType(RequestType $requestType): string
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            ProductSearch::class,
            $requestType
        );
    }
}
