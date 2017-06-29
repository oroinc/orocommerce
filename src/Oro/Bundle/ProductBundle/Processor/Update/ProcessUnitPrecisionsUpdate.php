<?php

namespace Oro\Bundle\ProductBundle\Processor\Update;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitPrecisionRepository;
use Oro\Bundle\ProductBundle\Processor\Shared\ProcessUnitPrecisions;

class ProcessUnitPrecisionsUpdate extends ProcessUnitPrecisions
{
    /**
     * @param array $requestData
     * @return array
     */
    public function handleUnitPrecisions(array $requestData)
    {
        $additionalUnitPrecisions = $primaryUnitPrecision = [];
        /** @var ProductUnitPrecisionRepository $productUnitPrecisionRepo */
        $productUnitPrecisionRepo = $this->doctrineHelper->getEntityRepositoryForClass(ProductUnitPrecision::class);
        $productUnitPrecisions = $productUnitPrecisionRepo->getProductUnitPrecisionsByProductId(
            $this->context->get(JsonApi::ID)
        );
        $productUnitPrecisions = $this->formatProductUnitPrecisions($productUnitPrecisions);

        $unitPrecisionInfo = $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][parent::UNIT_PRECISIONS];
        $relationships = $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS];
        $primaryUnitPrecisionCode = isset($relationships[parent::PRIMARY_UNIT_PRECISION][parent::ATTR_UNIT_CODE]) ?
            $relationships[parent::PRIMARY_UNIT_PRECISION][parent::ATTR_UNIT_CODE] : null;
        $hasPrimaryUnit = false;

        foreach ($unitPrecisionInfo[JsonApi::DATA] as $info) {
            if ($primaryUnitPrecisionCode === $info[parent::ATTR_UNIT_CODE]) {
                $primaryUnitPrecision = $this->createOrUpdateUnitPrecision($info, $productUnitPrecisions);
                $additionalUnitPrecisions[] = $primaryUnitPrecision;
                $hasPrimaryUnit = true;
                continue;
            }
            $additionalUnitPrecisions[] = $this->createOrUpdateUnitPrecision($info, $productUnitPrecisions);
        }

        $additionalUnitPrecisions = $this->normalizeUnitPrecisions($additionalUnitPrecisions);

        $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][parent::UNIT_PRECISIONS] = [
            JsonApi::DATA => $additionalUnitPrecisions
        ];
        if ($hasPrimaryUnit === true) {
            $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][parent::PRIMARY_UNIT_PRECISION] = [
                JsonApi::DATA => $primaryUnitPrecision
            ];
        }
        unset($relationships[parent::PRIMARY_UNIT_PRECISION][parent::ATTR_UNIT_CODE]);

        return $requestData;
    }

    /**
     * @param $unitPrecision
     * @param $pointer
     */
    protected function validateRequiredFields($unitPrecision, $pointer)
    {
        if (isset($unitPrecision[JsonApi::ID])) {
            $this->mandatoryFields = [parent::ATTR_UNIT_CODE];
        }
        parent::validateRequiredFields($unitPrecision, $pointer);
    }

    /**
     * @param array $unitPrecisionInfo
     * @return array
     */
    protected function createOrUpdateUnitPrecision(array $unitPrecisionInfo, $productUnitPrecisions)
    {
        if (in_array($unitPrecisionInfo[parent::ATTR_UNIT_CODE], array_keys($productUnitPrecisions))) {
            $unitPrecisionId = $this->updateProductUnitPrecision(
                $unitPrecisionInfo,
                $productUnitPrecisions[$unitPrecisionInfo[parent::ATTR_UNIT_CODE]]
            );

            return [
                JsonApi::TYPE => 'productunitprecisions',
                JsonApi::ID => (string)$unitPrecisionId,
                'isNew' => false
            ];
        }
        $unitPrecisionId = $this->createProductUnitPrecision($unitPrecisionInfo);

        return [JsonApi::TYPE => 'productunitprecisions', JsonApi::ID => (string)$unitPrecisionId, 'isNew' => true];
    }

    /**
     * @return array
     */
    protected function getProductPrimaryUnitPrecision()
    {
        /** @var ProductUnitPrecisionRepository $productUnitPrecisionRepo */
        $productUnitPrecisionRepo = $this->doctrineHelper->getEntityRepositoryForClass(ProductUnitPrecision::class);
        /** @var ProductUnitPrecision $primaryUnitPrecision */
        $primaryUnitPrecision = $productUnitPrecisionRepo->getPrimaryUnitPrecisionByProductId(
            $this->context->get(JsonApi::ID)
        );

        return [
            JsonApi::TYPE => 'productunitprecisions',
            JsonApi::ID => (string)$primaryUnitPrecision->getId(),
            'isNew' => false
        ];
    }

    /**
     * @param $productUnitPrecisions
     * @return array
     */
    protected function formatProductUnitPrecisions($productUnitPrecisions)
    {
        $precisions = [];
        /** @var ProductUnitPrecision $productUnitPrecision */
        foreach ($productUnitPrecisions as $productUnitPrecision) {
            $precisions[$productUnitPrecision->getUnit()->getCode()] = $productUnitPrecision;
        }

        return $precisions;
    }

    /**
     * @param $unitPrecisionInfo
     * @param ProductUnitPrecision $productUnitPrecision
     * @return int
     */
    protected function updateProductUnitPrecision($unitPrecisionInfo, ProductUnitPrecision $productUnitPrecision)
    {
        $em = $this->doctrineHelper->getEntityManagerForClass(ProductUnitPrecision::class);
        if (isset($unitPrecisionInfo[self::ATTR_CONVERSION_RATE])) {
            $productUnitPrecision->setConversionRate($unitPrecisionInfo[self::ATTR_CONVERSION_RATE]);
        }
        if (isset($unitPrecisionInfo[self::ATTR_UNIT_PRECISION])) {
            $productUnitPrecision->setPrecision($unitPrecisionInfo[self::ATTR_UNIT_PRECISION]);
        }
        if (isset($unitPrecisionInfo[self::ATTR_SELL])) {
            $productUnitPrecision->setSell((bool)$unitPrecisionInfo[self::ATTR_SELL]);
        }

        $em->persist($productUnitPrecision);

        return $productUnitPrecision->getId();
    }

    /**
     * @param $additionalUnitPrecisions
     * @param $primaryUnitPrecision
     * @return mixed
     */
    protected function normalizeUnitPrecisions($additionalUnitPrecisions)
    {
        $addedUnits = [];
        foreach ($additionalUnitPrecisions as $key => &$unitPrecision) {
            if ($unitPrecision['isNew'] === true) {
                $addedUnits[] = $unitPrecision[JsonApi::ID];
            }
            unset($unitPrecision['isNew']);
        }

        $this->context->set('addedUnits', $addedUnits);

        return $additionalUnitPrecisions;
    }
}
