<?php

namespace Oro\Bundle\ProductBundle\Processor\Shared;

use Symfony\Component\Form\Form;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitPrecisionRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class ProcessPrecisionsAfterValidation implements ProcessorInterface
{
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function process(ContextInterface $context)
    {
        /** @var Form $form */
        $form = $context->getForm();
        if (!$form->isValid()) {
            $this->removeProductUnitPrecisions($context);
        }
    }

    private function removeProductUnitPrecisions(FormContext $context)
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
