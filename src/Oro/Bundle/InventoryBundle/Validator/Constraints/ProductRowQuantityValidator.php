<?php

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Symfony\Component\Translation\TranslatorInterface;
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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param QuantityToOrderValidatorService $quantityValidatorService
     * @param DoctrineHelper $doctrineHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        QuantityToOrderValidatorService $quantityValidatorService,
        DoctrineHelper $doctrineHelper,
        TranslatorInterface $translator
    ) {
        $this->quantityValidatorService = $quantityValidatorService;
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
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

        $minLimit = $this->quantityValidatorService->getMinimumLimit($product);
        $maxLimit = $this->quantityValidatorService->getMaximumLimit($product);

        if ($this->quantityValidatorService->isHigherThanMaxLimit($maxLimit, $value->productQuantity)) {
            $this->addViolation($product, $maxLimit, 'quantity_over_max_limit');
        }
        if ($this->quantityValidatorService->isLowerThenMinLimit($minLimit, $value->productQuantity)) {
            $this->addViolation($product, $minLimit, 'quantity_below_min_limit');
        }
    }

    /**
     * @param Product $product
     * @param int $limit
     * @param string $errorSuffix
     */
    protected function addViolation(Product $product, $limit, $errorSuffix)
    {
        $message = $this->translator->trans(
            'oro.inventory.product.error.' . $errorSuffix,
            [
                '%limit%' => $limit,
                '%sku%' => $product->getSku(),
                '%product_name%' => $product->getName(),
            ]
        );
        $this->context->buildViolation($message)
            ->atPath('productQuantity')
            ->addViolation();
    }
}
