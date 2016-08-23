<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Component\PropertyAccess\PropertyAccessor;

class ProductBySkuValidator extends ConstraintValidator
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
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
                $valid = isset($products[strtoupper($value)]);
            } else {
                $product = $this->registry->getRepository('OroProductBundle:Product')->findOneBySku($value);
                $valid = !empty($product);
            }

            if (!$valid) {
                $this->context->addViolation($constraint->message);
            }
        }
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = new PropertyAccessor();
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
