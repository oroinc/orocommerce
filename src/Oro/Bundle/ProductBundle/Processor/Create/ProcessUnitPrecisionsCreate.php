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
        $unitPrecisionInfo = $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][self::UNIT_PRECISIONS];
        $primaryUnitPrecisionCode = isset(
                $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][self::PRIMARY_UNIT_PRECISION][self::CODE]
            ) ?: null;
        $hasPrimaryUnit = false;
        foreach ($unitPrecisionInfo[JsonApi::DATA] as $info) {
            if ($primaryUnitPrecisionCode === $info[self::ATTR_UNIT_CODE] ||
                ($primaryUnitPrecisionCode === null && $hasPrimaryUnit === false)
            ) {
                $primaryUnitPrecision = $this->handlePrimaryUnitPrecision($info);
                $hasPrimaryUnit = true;
                continue;
            }
            $additionalUnitPrecisions[] = $this->handleAdditionalUnitPrecisions($info);
        }

        $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][self::UNIT_PRECISIONS] = [
             JsonApi::DATA => $additionalUnitPrecisions
        ];
        $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][self::PRIMARY_UNIT_PRECISION] = [
            JsonApi::DATA => $primaryUnitPrecision
        ];
        unset($requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][self::PRIMARY_UNIT_PRECISION][self::CODE]);

        return $requestData;
    }

    /**
     * @param array $primaryUnitPrecisionInfo
     * @return array
     */
    private function handlePrimaryUnitPrecision(array $primaryUnitPrecisionInfo)
    {
        unset($primaryUnitPrecisionInfo[self::ATTR_UNIT_PRECISION]);
        unset($primaryUnitPrecisionInfo[self::ATTR_CONVERSION_RATE]);
        unset($primaryUnitPrecisionInfo[self::ATTR_SELL]);

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
            isset($unitPrecisionInfo[self::ATTR_CONVERSION_RATE]) ?: 1
        );
        $unitPrecision->setPrecision(
            isset($unitPrecisionInfo[self::ATTR_UNIT_PRECISION]) ?: 0
        );
        $unitPrecision->setSell((bool)isset($unitPrecisionInfo[self::ATTR_SELL]) ?: true);
        $unitPrecision->setUnit($productUnit);

        $em->persist($unitPrecision);
        $em->flush($unitPrecision);

        return $unitPrecision->getId();
    }
}
