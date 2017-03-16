<?php

namespace Oro\Bundle\RFPBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class SortIncludedDataProcessor implements ProcessorInterface
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     *
     * @param FormContext $context
     */
    public function process(ContextInterface $context)
    {
        if (null !== $context->getIncludedData()) {
            return;
        }

        $requestData = $this->sortIncludedRequestData(
            $context->getRequestData(),
            $context->getRequestType()
        );

        $context->setRequestData($requestData);
    }

    /**
     * @param array $requestData
     * @param RequestType $requestType
     *
     * @return array
     */
    protected function sortIncludedRequestData(array $requestData, RequestType $requestType)
    {
        if (!empty($requestData[JsonApiDoc::INCLUDED])) {
            $includedData = $requestData[JsonApiDoc::INCLUDED];
            usort(
                $includedData,
                function (array $first, array $second) use ($requestType) {
                    if ($first[JsonApiDoc::TYPE] === $second[JsonApiDoc::TYPE]) {
                        return 0;
                    }

                    $entityClass = $this->getEntityClass($first[JsonApiDoc::TYPE], $requestType);
                    if ($entityClass === RequestProduct::class) {
                        return 1;
                    }

                    return -1;
                }
            );

            $requestData[JsonApiDoc::INCLUDED] = $includedData;
        }

        return $requestData;
    }

    /**
     * @param $entityType
     * @param RequestType $requestType
     *
     * @return null|string
     */
    protected function getEntityClass($entityType, RequestType $requestType)
    {
        $entityClass = ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $requestType,
            false
        );

        return $entityClass;
    }
}
