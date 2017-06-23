<?php

namespace Oro\Bundle\ProductBundle\Processor\Create;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitPrecisionRepository;
use Oro\Bundle\ProductBundle\Processor\Shared\ProcessPrecisionsAfterValidation;

class ProcessPrecisionsAfterValidationCreate extends ProcessPrecisionsAfterValidation
{
    /**
     * Removes the product unit precisions that were created and the validation failed
     *
     * @param FormContext $context
     */
    public function handleProductUnitPrecisions(FormContext $context)
    {
        /** @var ProductUnitPrecisionRepository $productUnitPrecisionRepo */
        $productUnitPrecisionRepo = $this->doctrineHelper->getEntityRepositoryForClass(ProductUnitPrecision::class);
        $requestData = $context->getRequestData();
        $productUnitPrecisionIds = [];
        foreach ($requestData['unitPrecisions'] as $productUnitPrecision) {
            $productUnitPrecisionIds[] = $productUnitPrecision[JsonApi::ID];
        }
        $productUnitPrecisionIds[] = $requestData['primaryUnitPrecision'][JsonApi::ID];
        $productUnitPrecisionRepo->deleteProductUnitPrecisionsById($productUnitPrecisionIds);
    }
}
