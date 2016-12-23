<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class SingleUnitModeProductUnitSelectionOwnerTypeExtension extends AbstractTypeExtension
{
    /**
     * @var SingleUnitModeService
     */
    private $singleUnitModeService;

    /**
     * @var string
     */
    private $childName;

    /**
     * @var string
     */
    private $extendedType;

    /**
     * @param string $childName
     * @param string $extendedType
     * @param SingleUnitModeService $singleUnitModeService
     */
    public function __construct($childName, $extendedType, SingleUnitModeService $singleUnitModeService)
    {
        $this->childName = $childName;
        $this->extendedType = $extendedType;
        $this->singleUnitModeService = $singleUnitModeService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->singleUnitModeService->isSingleUnitMode()) {
            $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'setSingleModeUnits']);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function setSingleModeUnits(FormEvent $event)
    {
        $form = $event->getForm();
        $child = $form->get($this->childName);
        if (!$child) {
            throw new \InvalidArgumentException(
                sprintf('Unknown %s child in %s', $this->childName, $this->getExtendedType())
            );
        }
        $product = $this->getProduct($child);
        if (!$product) {
            return;
        }

        $options = $child->getConfig()->getOptions();
        $options['choices'] = $this->getSingleUnitModeProductUnits($product);
        $options['choices_updated'] = true;
        $options['choice_loader'] = null;
        $options['choice_list'] = null;

        $form->add($child->getName(), $child->getConfig()->getType()->getName(), $options);
    }

    /**
     * @param Product $product
     * @return ProductUnit[]
     */
    protected function getSingleUnitModeProductUnits(Product $product)
    {
        $units = [];
        $primaryUnitPrecision = $product->getPrimaryUnitPrecision();

        if ($primaryUnitPrecision) {
            $units[] = $primaryUnitPrecision->getUnit();
            $primaryUnitCode = $primaryUnitPrecision->getUnit()->getCode();
            $defaultUnit = $this->singleUnitModeService->getConfigDefaultUnit();
            if ($defaultUnit && $defaultUnit->getCode() !== $primaryUnitCode) {
                $units[] = $defaultUnit;
            }
        }

        return $units;
    }

    /**
     * @param FormInterface $form
     * @return null|Product
     */
    protected function getProduct(FormInterface $form)
    {
        $options = $form->getConfig()->getOptions();
        $productField = $options['product_field'];

        $parent = $form->getParent();
        while ($parent && !$parent->has($productField)) {
            $parent = $parent->getParent();
        }

        if ($parent && $parent->has($productField)) {
            $productData = $parent->get($productField)->getData();
            if ($productData instanceof Product) {
                return $productData;
            }

            if ($productData instanceof ProductHolderInterface) {
                return $productData->getProduct();
            }
        }

        /** @var Product $product */
        $product = $options['product'];
        if ($product) {
            return $product;
        }

        /** @var ProductHolderInterface $productHolder */
        $productHolder = $options['product_holder'];
        if ($productHolder) {
            return $productHolder->getProduct();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return $this->extendedType;
    }
}
