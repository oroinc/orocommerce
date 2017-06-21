<?php

namespace Oro\Bundle\ProductBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class ProcessUnitPrecisions implements ProcessorInterface
{
    const UNIT_PRECISIONS = 'unitPrecisions';
    const PRIMARY_UNIT_PRECISION = 'primaryUnitPrecision';
    const CODE = 'code';

    const ATTR_UNIT_PRECISION = 'unit_precision';
    const ATTR_CONVERSION_RATE = 'conversion_rate';
    const ATTR_SELL = 'sell';
    const ATTR_UNIT_CODE = 'unit_code';

    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */
        $requestData = $context->getRequestData();

        if (!isset($requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][self::UNIT_PRECISIONS])) {
            return;
        }

        $requestData = $this->handleUnitPrecisions($requestData);
        $context->setRequestData($requestData);
    }

    private function handleUnitPrecisions(array $requestData)
    {
        $additionalUnitPrecisions = $primaryUnitPrecision = [];
        $unitPrecisionInfo = $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][self::UNIT_PRECISIONS];
        $primaryUnitPrecisionCode = isset(
                $requestData[JsonApi::DATA][JsonApi::RELATIONSHIPS][self::PRIMARY_UNIT_PRECISION][self::CODE]
            ) ?: null;
        $k = 0;
        foreach ($unitPrecisionInfo[JsonApi::DATA] as $info) {
            if ($primaryUnitPrecisionCode === $info[self::ATTR_UNIT_CODE] ||
                ($primaryUnitPrecisionCode === null && $k === 0)
            ) {
                $primaryUnitPrecision = $this->handlePrimaryUnitPrecision($info);
                $k++;
                continue;
            }
            $additionalUnitPrecisions[] = $this->handleAdditionalUnitPrecisions($info);
            $k++;
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

    private function handlePrimaryUnitPrecision(array $primaryUnitPrecisionInfo)
    {
        unset($primaryUnitPrecisionInfo[self::ATTR_UNIT_PRECISION]);
        unset($primaryUnitPrecisionInfo[self::ATTR_CONVERSION_RATE]);
        unset($primaryUnitPrecisionInfo[self::ATTR_SELL]);

        $primaryUnitPrecisionId = $this->createProductUnitPrecision($primaryUnitPrecisionInfo);

        return [JsonApi::TYPE => 'productunitprecisions', JsonApi::ID => (string)$primaryUnitPrecisionId];
    }

    private function handleAdditionalUnitPrecisions(array $unitPrecisionInfo)
    {
       $unitPrecisionId = $this->createProductUnitPrecision($unitPrecisionInfo);

       return [JsonApi::TYPE => 'productunitprecisions', JsonApi::ID => (string)$unitPrecisionId];
    }

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
