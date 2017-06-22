<?php

namespace Oro\Bundle\ProductBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
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
    /** @var SingleItemContext */
    protected $context;

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

        if (!isset($requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][self::UNIT_PRECISIONS])) {
            return;
        }

        $pointer = $this->buildPointer('', JsonApi::DATA);
        if (!$this->validateUnitPrecisions(
            $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][self::UNIT_PRECISIONS],
            $pointer)
        ) {
            return;
        }

        $requestData = $this->handleUnitPrecisions($requestData);
        $context->setRequestData($requestData);
    }

    abstract public function handleUnitPrecisions(array $requestData);

    protected function validateUnitPrecisions($unitPrecisionInfo, $pointer)
    {
        $existentCodes = [];
        $mandatoryFields = [
            self::ATTR_UNIT_CODE,
            self::ATTR_CONVERSION_RATE,
            self::ATTR_UNIT_PRECISION,
            self::ATTR_SELL
        ];
        $isValid = true;
        $pointer = $this->buildPointer($pointer, self::UNIT_PRECISIONS . '/' . JsonApi::DATA);
        foreach ($unitPrecisionInfo[JsonApi::DATA] as $key => $unitPrecision) {
            $absentProperties = array_diff($mandatoryFields, array_keys($unitPrecision));
            foreach ($absentProperties as $property) {
                $this->addError(
                    $this->buildPointer($pointer, $key . '/' . $property),
                    sprintf('The \'%s\' property is required', $property)
                );
                $isValid = false;
            }
            if (in_array($unitPrecision[self::ATTR_UNIT_CODE], $existentCodes)) {
                $this->addError(
                    $this->buildPointer($pointer, $key . '/' . self::ATTR_UNIT_CODE),
                    sprintf('Unit precision \'%s\' already exists', $unitPrecision[self::ATTR_UNIT_CODE])
                );
                $isValid = false;
            }
            $existentCodes[] = $unitPrecision[self::ATTR_UNIT_CODE];
        }

        return $isValid;
    }

    /**
     * @param string $pointer
     * @param string $message
     */
    protected function addError($pointer, $message)
    {
        $error = Error::createValidationError(Constraint::REQUEST_DATA, $message)
            ->setSource(ErrorSource::createByPointer($pointer));

        $this->context->addError($error);
    }

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
