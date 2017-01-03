<?php

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductRow;

class ProductRowQuantityValidator extends ConstraintValidator
{
    /**
     * @var QuantityToOrderValidatorService
     */
    protected $quantityValidatorService;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param QuantityToOrderValidatorService $quantityValidatorService
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        QuantityToOrderValidatorService $quantityValidatorService,
        DoctrineHelper $doctrineHelper
    ) {
        $this->quantityValidatorService = $quantityValidatorService;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof ProductRow) {
            return;
        }
        /** @var Product $product */
        $product = $this->doctrineHelper->getEntityRepository(Product::class)->findOneBySku($value->productSku);
        if (!$product || !is_numeric($value->productQuantity)) {
            return;
        }

        if ($maxError = $this->quantityValidatorService->getMaximumErrorIfInvalid($product, $value->productQuantity)) {
            $this->addViolation($maxError);

            return;
        }

        if ($minError = $this->quantityValidatorService->getMinimumErrorIfInvalid($product, $value->productQuantity)) {
            $this->addViolation($minError);
        }
    }

    /**
     * @param string $message
     */
    protected function addViolation($message)
    {
        $this->context->buildViolation($message)
            ->atPath('productQuantity')
            ->addViolation();
    }
}
