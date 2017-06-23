<?php

namespace Oro\Bundle\ProductBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
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

    protected $doctrineHelper;
    /** @var SingleItemContext */
    protected $context;
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
        $dataPointer = $this->buildPointer($pointer, self::UNIT_PRECISIONS . '/' . JsonApi::DATA);
        foreach ($unitPrecisionInfo[JsonApi::DATA] as $key => $unitPrecision) {
            $pointer = $this->buildPointer($dataPointer, $key);
            $this->validateRequiredFields($unitPrecision, $pointer);
            $this->validateProductUnitExists($unitPrecision, $pointer);
            if (in_array($unitPrecision[self::ATTR_UNIT_CODE], $existentCodes)) {
                $this->addError(
                    $this->buildPointer($pointer, self::ATTR_UNIT_CODE),
                    sprintf('Unit precision \'%s\' already exists', $unitPrecision[self::ATTR_UNIT_CODE])
                );
            }
            $existentCodes[] = $unitPrecision[self::ATTR_UNIT_CODE];
        }

        if ($this->context->hasErrors()) {
            return false;
        }

        return true;
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

    /**
     * @param $unitPrecision
     * @param $pointer
     */
    protected function validateRequiredFields($unitPrecision, $pointer)
    {
        $mandatoryFields = [
            self::ATTR_UNIT_CODE,
            self::ATTR_CONVERSION_RATE,
            self::ATTR_UNIT_PRECISION,
            self::ATTR_SELL
        ];

        $absentProperties = array_diff($mandatoryFields, array_keys($unitPrecision));
        foreach ($absentProperties as $property) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property is required', $property)
            );
        }
    }

    protected function validateProductUnitExists($unitPrecision, $pointer)
    {
        if (!$this->validProductUnits) {
            /** @var ProductUnitRepository $productUnitRepo */
            $productUnitRepo = $this->doctrineHelper->getEntityRepositoryForClass(ProductUnit::class);
            $codes = $productUnitRepo->getAllUnitCodes();
            $this->validProductUnits = $codes;
        }

        if (!in_array($unitPrecision[self::ATTR_UNIT_CODE], $this->validProductUnits)) {
            $this->addError(
                $this->buildPointer($pointer, self::ATTR_UNIT_CODE),
                sprintf('Invalid value \'%s\' for unit_code', $unitPrecision[self::ATTR_UNIT_CODE])
            );
        }
    }
}
