<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if product with given SKU exists.
 */
class ProductBySkuValidator extends ConstraintValidator
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var AclHelper
     */
    private $aclHelper;

    protected ?PropertyAccessorInterface $propertyAccessor = null;

    public function __construct(ManagerRegistry $registry, AclHelper $aclHelper)
    {
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param string $value
     * @param Constraint|ProductBySku $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value) {
            $products = $this->getProducts();
            if ($products !== null) {
                $valid = isset($products[mb_strtoupper($value)]);
            } else {
                $repository = $this->registry->getRepository(Product::class);

                $qb = $repository->getBySkuQueryBuilder($value);
                $product = $this->aclHelper->apply($qb)->getOneOrNullResult();
                $valid = !empty($product);
            }

            if (!$valid) {
                $this->context->addViolation($constraint->message);
            }
        }
    }

    /**
     * @return PropertyAccessorInterface
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }
        return $this->propertyAccessor;
    }

    /**
     * @return array|null
     */
    protected function getProducts()
    {
        $products = null;

        $form = $this->getForm();
        while ($form) {
            if ($form->getConfig()->hasOption('products')) {
                $products = $form->getConfig()->getOption('products');
                break;
            }
            $form = $form->getParent();
        }

        return $products;
    }

    /**
     * @return FormInterface
     */
    protected function getForm()
    {
        return $this->getPropertyAccessor()->getValue($this->context->getRoot(), $this->getFormPath());
    }

    /**
     * @return string
     */
    protected function getFormPath()
    {
        $path = $this->context->getPropertyPath();
        $path = str_replace(['children', '.'], ['', ''], $path);
        $path = preg_replace('/\][^\]]*$/', ']', $path);
        return $path;
    }
}
