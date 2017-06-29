<?php

namespace Oro\Bundle\ProductBundle\Processor\Create;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
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
                $primaryUnitPrecision = $this->handleAndCreateUnitPrecision($info);
                $hasPrimaryUnit = true;
                continue;
            }
            $additionalUnitPrecisions[] = $this->handleAndCreateUnitPrecision($info);
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
     * @param array $unitPrecisionInfo
     * @return array
     */
    private function handleAndCreateUnitPrecision(array $unitPrecisionInfo)
    {
       $unitPrecisionId = $this->createProductUnitPrecision($unitPrecisionInfo);

       return [JsonApi::TYPE => 'productunitprecisions', JsonApi::ID => (string)$unitPrecisionId];
    }
}
