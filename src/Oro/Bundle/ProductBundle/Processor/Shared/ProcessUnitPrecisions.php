<?php

namespace Oro\Bundle\ProductBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

abstract class ProcessUnitPrecisions implements ProcessorInterface
{
    const UNIT_PRECISIONS = 'unitPrecisions';
    const PRIMARY_UNIT_PRECISION = 'primaryUnitPrecision';

    const ATTR_UNIT_PRECISION = 'unit_precision';
    const ATTR_CONVERSION_RATE = 'conversion_rate';
    const ATTR_SELL = 'sell';
    const ATTR_UNIT_CODE = 'unit_code';

    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /** @var SingleItemContext */
    protected $context;

    /** @var  array */
    protected $validProductUnits;

    /**
     * ProcessUnitPrecisions constructor.
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ContextInterface $context
     */
    public function process(ContextInterface $context)
    {
        $this->context = $context;
        /** @var FormContext $context */
        $requestData = $context->getRequestData();

        $relationships = $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS];
        if (!isset($relationships[self::UNIT_PRECISIONS])) {
            return;
        }

        $pointer = $this->buildPointer('', JsonApi::DATA . '/' . JsonApi::RELATIONSHIPS);
        if (!$this->validateUnitPrecisions($relationships[self::UNIT_PRECISIONS], $pointer) ||
            !$this->validatePrimaryUnitPrecision($relationships, $pointer)
        ) {
            return;
        }

        $requestData = $this->handleUnitPrecisions($requestData);
        $context->setRequestData($requestData);
    }

    /**
     * @param array $requestData
     * @return mixed
     */
    abstract public function handleUnitPrecisions(array $requestData);

    abstract public function validateUnitPrecisions($unitPrecisionInfo, $pointer);

    abstract public function validatePrimaryUnitPrecision($primaryUnitPrecisionInfo, $pointer);

    /**
     * @param string $parentPath
     * @param string $property
     *
     * @return string
     */
    protected function buildPointer($parentPath, $property)
    {
        return $parentPath . '/' . $property;
    }
}
