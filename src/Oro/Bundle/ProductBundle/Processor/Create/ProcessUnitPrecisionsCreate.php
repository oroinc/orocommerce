<?php

namespace Oro\Bundle\ProductBundle\Processor\Create;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Processor\Shared\ProcessUnitPrecisions;

class ProcessUnitPrecisionsCreate extends ProcessUnitPrecisions
{
    /**
     * @param array $requestData
     * @return array
     */
    public function handleUnitPrecisions(array $requestData)
    {
        $additionalUnitPrecisions = $primaryUnitPrecision = [];
        $unitPrecisionInfo = $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][parent::UNIT_PRECISIONS];
        $relationships = $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS];
        $primaryUnitPrecisionCode = isset($relationships[parent::PRIMARY_UNIT_PRECISION][parent::ATTR_UNIT_CODE]) ?
            $relationships[parent::PRIMARY_UNIT_PRECISION][parent::ATTR_UNIT_CODE] : null;
        $hasPrimaryUnit = false;
        foreach ($unitPrecisionInfo[JsonApi::DATA] as $info) {
            if ($primaryUnitPrecisionCode === $info[parent::ATTR_UNIT_CODE] ||
                ($primaryUnitPrecisionCode === null && $hasPrimaryUnit === false)
            ) {
                $primaryUnitPrecision = $this->handlePrimaryUnitPrecision($info);
                $hasPrimaryUnit = true;
                continue;
            }
            $additionalUnitPrecisions[] = $this->handleAdditionalUnitPrecisions($info);
        }
        if ($hasPrimaryUnit === false) {
            $primaryUnitPrecision = $this->handlePrimaryUnitPrecision(
                $relationships[parent::PRIMARY_UNIT_PRECISION]
            );
        }

        $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][parent::UNIT_PRECISIONS] = [
             JsonApi::DATA => $additionalUnitPrecisions
        ];
        $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][parent::PRIMARY_UNIT_PRECISION] = [
            JsonApi::DATA => $primaryUnitPrecision
        ];
        unset($relationships[parent::PRIMARY_UNIT_PRECISION][parent::ATTR_UNIT_CODE]);

        return $requestData;
    }
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

        if ($this->context->hasErrors()) {
            return false;
        }

        return true;
    }

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
     * @param array $primaryUnitPrecisionInfo
     * @return array
     */
    private function handlePrimaryUnitPrecision(array $primaryUnitPrecisionInfo)
    {
        unset($primaryUnitPrecisionInfo[parent::ATTR_UNIT_PRECISION]);
        unset($primaryUnitPrecisionInfo[parent::ATTR_CONVERSION_RATE]);
        unset($primaryUnitPrecisionInfo[parent::ATTR_SELL]);

        $primaryUnitPrecisionId = $this->createProductUnitPrecision($primaryUnitPrecisionInfo);

        return [JsonApi::TYPE => 'productunitprecisions', JsonApi::ID => (string)$primaryUnitPrecisionId];
    }

    /**
     * @param array $unitPrecisionInfo
     * @return array
     */
    private function handleAdditionalUnitPrecisions(array $unitPrecisionInfo)
    {
       $unitPrecisionId = $this->createProductUnitPrecision($unitPrecisionInfo);

       return [JsonApi::TYPE => 'productunitprecisions', JsonApi::ID => (string)$unitPrecisionId];
    }

    /**
     * @param array $unitPrecisionInfo
     * @return int
     */
    private function createProductUnitPrecision(array $unitPrecisionInfo)
    {
        $em = $this->doctrineHelper->getEntityManagerForClass(ProductUnitPrecision::class);
        $productUnitRepo = $this->doctrineHelper->getEntityRepositoryForClass(ProductUnit::class);
        $productUnit = $productUnitRepo->find($unitPrecisionInfo['unit_code']);
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setConversionRate(
            isset($unitPrecisionInfo[parent::ATTR_CONVERSION_RATE]) ?
                $unitPrecisionInfo[parent::ATTR_CONVERSION_RATE] : 1
        );
        $unitPrecision->setPrecision(
            isset($unitPrecisionInfo[parent::ATTR_UNIT_PRECISION]) ? $unitPrecisionInfo[parent::ATTR_UNIT_PRECISION] : 0
        );
        $unitPrecision->setSell(
            (bool)(isset($unitPrecisionInfo[parent::ATTR_SELL]) ? $unitPrecisionInfo[parent::ATTR_SELL] : true)
        );
        $unitPrecision->setUnit($productUnit);

        $em->persist($unitPrecision);
        $em->flush($unitPrecision);

        return $unitPrecision->getId();
    }
}
