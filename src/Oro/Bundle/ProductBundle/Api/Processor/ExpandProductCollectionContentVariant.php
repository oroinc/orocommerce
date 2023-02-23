<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\TargetConfigExtraBuilder;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\ProductBundle\Api\Model\ProductSearch;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Expands data for "product collection" content variants.
 */
class ExpandProductCollectionContentVariant implements ProcessorInterface
{
    private const ID_FIELD = 'id';
    private const PRODUCTS_ASSOCIATION = 'products';

    private ActionProcessorBagInterface $processorBag;
    private ValueNormalizer $valueNormalizer;

    public function __construct(
        ActionProcessorBagInterface $processorBag,
        ValueNormalizer $valueNormalizer
    ) {
        $this->processorBag = $processorBag;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        if (!$context->isFieldRequested(self::PRODUCTS_ASSOCIATION, $data)) {
            return;
        }

        $productSearchContext = $this->searchProducts($context, $data[self::ID_FIELD]);

        $products = $productSearchContext->getResult();
        $infoRecords = $productSearchContext->getInfoRecords();
        if ($infoRecords && isset($infoRecords[''])) {
            $products[ConfigUtil::INFO_RECORD_KEY] = $infoRecords[''];
        }

        $data[self::PRODUCTS_ASSOCIATION] = $products;
        $context->setData($data);
    }

    private function searchProducts(CustomizeLoadedDataContext $context, int $contentVariantId): GetListContext
    {
        $productSearchProcessor = $this->processorBag->getProcessor(ApiAction::GET_LIST);
        /** @var GetListContext $productSearchContext */
        $productSearchContext = $productSearchProcessor->createContext();
        $productSearchContext->setVersion($context->getVersion());
        $productSearchContext->getRequestType()->set($context->getRequestType());
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
            Criteria::expr()->eq(sprintf('integer.assigned_to.variant_%s', $contentVariantId), 1)
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
