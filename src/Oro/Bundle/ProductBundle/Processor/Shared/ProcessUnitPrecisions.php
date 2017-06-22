<?php

namespace Oro\Bundle\ProductBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

abstract class ProcessUnitPrecisions implements ProcessorInterface
{
    const UNIT_PRECISIONS = 'unitPrecisions';
    const PRIMARY_UNIT_PRECISION = 'primaryUnitPrecision';
    const CODE = 'code';

    const ATTR_UNIT_PRECISION = 'unit_precision';
    const ATTR_CONVERSION_RATE = 'conversion_rate';
    const ATTR_SELL = 'sell';
    const ATTR_UNIT_CODE = 'unit_code';

    protected $doctrineHelper;

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
        /** @var FormContext $context */
        $requestData = $context->getRequestData();

        if (!isset($requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][self::UNIT_PRECISIONS])) {
            return;
        }

        $requestData = $this->handleUnitPrecisions($requestData);
        $context->setRequestData($requestData);
    }

    abstract public function handleUnitPrecisions(array $requestData);
}
