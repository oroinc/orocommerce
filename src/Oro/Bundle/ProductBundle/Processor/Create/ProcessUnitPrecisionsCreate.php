<?php

namespace Oro\Bundle\ProductBundle\Processor\Create;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
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
        $primaryUnitPrecisionCode = $relationships[parent::PRIMARY_UNIT_PRECISION][parent::ATTR_UNIT_CODE] ?: null;
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
