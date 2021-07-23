<?php

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Model\ProductRow;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that the current product corresponds to the minimum/maximum quantity request.
 */
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
     * @var AclHelper
     */
    private $aclHelper;

    public function __construct(
        QuantityToOrderValidatorService $quantityValidatorService,
        DoctrineHelper $doctrineHelper,
        AclHelper $aclHelper
    ) {
        $this->quantityValidatorService = $quantityValidatorService;
        $this->doctrineHelper = $doctrineHelper;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof ProductRow) {
            return;
        }

        /** @var ProductRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(Product::class);
        $qb = $repository->getBySkuQueryBuilder($value->productSku);

        /** @var Product $product */
        $product = $this->aclHelper->apply($qb)->getOneOrNullResult();
        if (!$product || !is_numeric($value->productQuantity)) {
            return;
        }

        $maxError = $this->quantityValidatorService->getMaximumErrorIfInvalid($product, $value->productQuantity);
        if ($maxError) {
            $this->addViolation($maxError);

            return;
        }

        $minError = $this->quantityValidatorService->getMinimumErrorIfInvalid($product, $value->productQuantity);
        if ($minError) {
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
