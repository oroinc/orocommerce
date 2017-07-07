<?php

namespace Oro\Bundle\ProductBundle\Processor\Update;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitPrecisionRepository;
use Oro\Bundle\ProductBundle\Processor\Shared\ProcessUnitPrecisions;

class ProcessUnitPrecisionsUpdate extends ProcessUnitPrecisions
{
    /**
     * @param array $includedData
     * @param string $pointer
     * @return bool
     */
    public function validateUnitPrecisions($includedData, $pointer)
    {
        $isValid = parent::validateUnitPrecisions($includedData, $pointer);

        if (!$isValid) {
            return $isValid;
        }
        /** @var ProductUnitPrecisionRepository $productUnitPrecisionRepo */
        $productUnitPrecisionRepo = $this->doctrineHelper->getEntityRepositoryForClass(ProductUnitPrecision::class);
        $productUnitPrecisions = $productUnitPrecisionRepo->getProductUnitPrecisionsByProductId(
            $this->context->get(JsonApi::ID)
        );
        $productUnitPrecisions = $this->formatProductUnitPrecisions($productUnitPrecisions);
        foreach ($includedData as $key => $data) {
            $keyPointer = $this->buildPointer($pointer, $key);
            if (array_key_exists(JsonApi::META, $data)
                && array_key_exists('update', $data[JsonApi::META])
                && true === $data[JsonApi::META]['update']
            ) {
                $this->checkProductUnitPrecisionValidForUpdate($data, $productUnitPrecisions, $keyPointer);
                continue;
            }
            $this->checkProductUnitAlreadyExistsOnProduct($data, $productUnitPrecisions, $keyPointer);
        }

        return !$this->context->hasErrors();
    }

    /**
     * @param array $unitPrecision
     * @param string $pointer
     */
    protected function validateRequiredFields($unitPrecision, $pointer)
    {
        if (isset($unitPrecision[JsonApi::ID])) {
            $this->mandatoryFields = [];
        }
        parent::validateRequiredFields($unitPrecision, $pointer);
    }

    /**
     * @param array $data
     * @param ProcessUnitPrecisions[] $productUnitPrecisions
     * @param string $pointer
     */
    protected function checkProductUnitAlreadyExistsOnProduct($data, $productUnitPrecisions, $pointer)
    {
        $unitRelationCode = $data[JsonApi::RELATIONSHIPS][parent::ATTR_UNIT][JsonApi::DATA][JsonApi::ID];
        $unitPointer = $this->buildPointer(JsonApi::RELATIONSHIPS, parent::ATTR_UNIT);
        $idPointer = $this->buildPointer($unitPointer, JsonApi::ID);
        /** @var ProductUnitPrecision $unitPrecision */
        foreach ($productUnitPrecisions as $unitPrecision) {
            if ($unitPrecision->getUnit()->getCode() === $unitRelationCode) {
                $this->addError(
                    $this->buildPointer($pointer, $idPointer),
                    sprintf("Unit precision '%s' already exists for this product", $unitRelationCode)
                );
            }
        }
    }
    /**
     * @param array $data
     * @param ProductUnitPrecision[] $productUnitPrecisions
     * @param string $pointer
     * @return bool
     */
    protected function checkProductUnitPrecisionValidForUpdate($data, $productUnitPrecisions, $pointer)
    {
        /** @var ProductUnitPrecision $productUnitPrecision */
        $productUnitPrecision = $productUnitPrecisions[$data[JsonApi::ID]] ?? null;
        if (!$productUnitPrecision instanceof $productUnitPrecision) {
            return false;
        }
        $unitRelationCode = $data[JsonApi::RELATIONSHIPS][parent::ATTR_UNIT][JsonApi::DATA][JsonApi::ID];

        if ($productUnitPrecision->getUnit()->getCode() !== $unitRelationCode) {
            $this->addError(
                $this->buildPointer($pointer, JsonApi::RELATIONSHIPS.'/'.parent::ATTR_UNIT.'/'.JsonApi::ID),
                sprintf("Unit precision '%s' already exists for this product", $unitRelationCode)
            );
        }

        return true;
    }
    /**
     * @param array $productUnitPrecisions
     * @return array
     */
    protected function formatProductUnitPrecisions($productUnitPrecisions)
    {
        $precisions = [];
        /** @var ProductUnitPrecision $productUnitPrecision */
        foreach ($productUnitPrecisions as $productUnitPrecision) {
            $precisions[$productUnitPrecision->getId()] = $productUnitPrecision;
        }

        return $precisions;
    }
}
