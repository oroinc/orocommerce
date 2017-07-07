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

class ProcessUnitPrecisions implements ProcessorInterface
{
    const UNIT_PRECISIONS = 'unitPrecisions';
    const PRIMARY_UNIT_PRECISION = 'primaryUnitPrecision';

    const ATTR_UNIT_PRECISION = 'precision';
    const ATTR_CONVERSION_RATE = 'conversionRate';
    const ATTR_SELL = 'sell';
    const ATTR_UNIT = 'unit';

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

        if (!isset($requestData[JsonApi::INCLUDED])) {
            return;
        }

        $pointer = $this->buildPointer('', JsonApi::INCLUDED);
        if (!$this->validateUnitPrecisions($requestData[JsonApi::INCLUDED], $pointer)) {
            return;
        }
    }

    /**
     * @param array $includedData
     * @param string $pointer
     * @return bool
     */
    public function validateUnitPrecisions($includedData, $pointer)
    {
        $existentCodes = $productPrecisionUnits = [];
        foreach ($includedData as $key => $data) {
            if ($data[JsonApi::TYPE] === 'productunitprecisions') {
                $keyPointer = $this->buildPointer($pointer, $key);
                $this->validateRequiredFields($data, $keyPointer);
                if (array_key_exists(JsonApi::RELATIONSHIPS, $data)) {
                    $unitRelation = $data[JsonApi::RELATIONSHIPS][self::ATTR_UNIT][JsonApi::DATA];
                    $unitPointer = $this->buildPointer(JsonApi::RELATIONSHIPS, self::ATTR_UNIT);
                    $dataPointer = $this->buildPointer($unitPointer, JsonApi::DATA);
                    $relationPointer = $this->buildPointer(
                        $keyPointer,
                        $dataPointer
                    );
                    $this->validateProductUnitExists($unitRelation, $relationPointer);
                    if (in_array($unitRelation[JsonApi::ID], $existentCodes)) {
                        $this->addError(
                            $this->buildPointer($relationPointer, JsonApi::ID),
                            sprintf('Unit precision "%s" already exists', $unitRelation[JsonApi::ID])
                        );
                    }
                    $existentCodes[] = $unitRelation[JsonApi::ID];
                }
            }
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
     * @param array $unitPrecision
     * @param string $pointer
     */
    protected function validateRequiredFields($unitPrecision, $pointer)
    {
        if (!array_key_exists(JsonApi::ATTRIBUTES, $unitPrecision)) {
            $this->addError(
                $this->buildPointer($pointer, JsonApi::ATTRIBUTES),
                sprintf('The "%s" property is required', JsonApi::ATTRIBUTES)
            );

            return;
        }

        $absentProperties = array_diff($this->mandatoryFields, array_keys($unitPrecision[JsonApi::ATTRIBUTES]));
        foreach ($absentProperties as $property) {
            $propertyPointer = $this->buildPointer(JsonApi::ATTRIBUTES, $property);
            $this->addError(
                $this->buildPointer($pointer, $propertyPointer),
                sprintf('The "%s" property is required', $property)
            );
        }
    }

    /**
     * @param array $unitRelation
     * @param string $pointer
     */
    protected function validateProductUnitExists($unitRelation, $pointer)
    {
        if (!$this->validProductUnits) {
            /** @var ProductUnitRepository $productUnitRepo */
            $productUnitRepo = $this->doctrineHelper->getEntityRepositoryForClass(ProductUnit::class);
            $codes = $productUnitRepo->getAllUnitCodes();
            $this->validProductUnits = $codes;
        }

        if (!in_array($unitRelation[JsonApi::ID], $this->validProductUnits)) {
            $this->addError(
                $this->buildPointer($pointer, JsonApi::ID),
                sprintf('Invalid value "%s" for product unit', $unitRelation[JsonApi::ID])
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
}
