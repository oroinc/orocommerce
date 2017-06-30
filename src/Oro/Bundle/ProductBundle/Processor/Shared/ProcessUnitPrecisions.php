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
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
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

    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /** @var SingleItemContext */
    protected $context;

    /** @var  array */
    protected $validProductUnits;

    /** @var  array */
    protected $mandatoryFields;

    /**
     * ProcessUnitPrecisions constructor.
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->mandatoryFields = [
            self::ATTR_UNIT_CODE,
            self::ATTR_CONVERSION_RATE,
            self::ATTR_UNIT_PRECISION,
            self::ATTR_SELL
        ];
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
            (isset($relationships[self::PRIMARY_UNIT_PRECISION]) &&
            !$this->validatePrimaryUnitPrecision($relationships, $pointer))
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

    /**
     * @param $unitPrecisionInfo
     * @param $pointer
     * @return bool
     */
    public function validateUnitPrecisions($unitPrecisionInfo, $pointer)
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

        return !$this->context->hasErrors();
    }

    /**
     * @param $relationships
     * @param $pointer
     * @return bool
     */
    public function validatePrimaryUnitPrecision($relationships, $pointer)
    {
        $codes = [];
        $pointer = $this->buildPointer($pointer, self::PRIMARY_UNIT_PRECISION);
        $this->validateProductUnitExists($relationships[self::PRIMARY_UNIT_PRECISION], $pointer);
        $primaryUnitPrecisionCode = $relationships[self::PRIMARY_UNIT_PRECISION][self::ATTR_UNIT_CODE];
        foreach ($relationships[self::UNIT_PRECISIONS][JsonApi::DATA] as $unitPrecision) {
            $codes[] = $unitPrecision[self::ATTR_UNIT_CODE];
        }

        if (!in_array($primaryUnitPrecisionCode, $codes)) {
            $this->addError(
                $this->buildPointer($pointer, self::ATTR_UNIT_CODE),
                'Primary unit precision code is not present in the unit precisions list'
            );
        }

        return !$this->context->hasErrors();
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
     * @param $unitPrecision
     * @param $pointer
     */
    protected function validateRequiredFields($unitPrecision, $pointer)
    {
        $absentProperties = array_diff($this->mandatoryFields, array_keys($unitPrecision));
        foreach ($absentProperties as $property) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property is required', $property)
            );
        }
    }

    /**
     * @param $unitPrecision
     * @param $pointer
     */
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
     * @param array $unitPrecisionInfo
     * @return int
     */
    protected function createProductUnitPrecision(array $unitPrecisionInfo)
    {
        $em = $this->doctrineHelper->getEntityManagerForClass(ProductUnitPrecision::class);
        $productUnitRepo = $this->doctrineHelper->getEntityRepositoryForClass(ProductUnit::class);
        $productUnit = $productUnitRepo->find($unitPrecisionInfo['unit_code']);
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setConversionRate($unitPrecisionInfo[self::ATTR_CONVERSION_RATE]);
        $unitPrecision->setPrecision($unitPrecisionInfo[self::ATTR_UNIT_PRECISION]);
        $unitPrecision->setSell((bool)$unitPrecisionInfo[self::ATTR_SELL]);
        $unitPrecision->setUnit($productUnit);

        $em->persist($unitPrecision);
        $em->flush($unitPrecision);

        return $unitPrecision->getId();
    }
}
