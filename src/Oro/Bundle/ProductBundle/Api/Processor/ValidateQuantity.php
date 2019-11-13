<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Validates quantity value based on product's precision.
 */
class ValidateQuantity implements ProcessorInterface
{
    /** @var RoundingServiceInterface */
    private $roundingService;

    /** @var string */
    private $quantityFieldName;

    /** @var string */
    private $productFieldName;

    /** @var string */
    private $productUnitFieldName;

    /**
     * @param RoundingServiceInterface $roundingService
     * @param string                   $quantityFieldName
     * @param string                   $productFieldName
     * @param string                   $productUnitFieldName
     */
    public function __construct(
        RoundingServiceInterface $roundingService,
        string $quantityFieldName = 'quantity',
        string $productFieldName = 'product',
        string $productUnitFieldName = 'unit'
    ) {
        $this->roundingService = $roundingService;
        $this->quantityFieldName = $quantityFieldName;
        $this->productFieldName = $productFieldName;
        $this->productUnitFieldName = $productUnitFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        $form = $context->getForm();
        if (!$form->has($this->quantityFieldName)) {
            return;
        }
        $quantityField = $form->get($this->quantityFieldName);
        if (!FormUtil::isSubmittedAndValid($quantityField)) {
            return;
        }
        $product = $this->getProduct($form);
        if (null === $product) {
            return;
        }
        $productUnit = $this->getProductUnit($form);
        if (null === $productUnit) {
            return;
        }

        $this->handle($quantityField, $product, $productUnit);
    }

    /**
     * @param FormInterface $quantityField
     * @param Product       $product
     * @param ProductUnit   $productUnit
     */
    private function handle(FormInterface $quantityField, Product $product, ProductUnit $productUnit): void
    {
        $scale = $this->getScale($product, $productUnit);
        if (null === $scale) {
            return;
        }

        $quantity = $quantityField->getData();
        if (empty($quantity)) {
            return;
        }

        $formattedQuantity = $this->roundingService->round($quantity, $scale);
        if ($quantity !== $formattedQuantity) {
            FormUtil::addFormError($quantityField, 'The quantity is not valid.');
        }
    }

    /**
     * @param Product     $product
     * @param ProductUnit $productUnit
     *
     * @return int|null
     */
    private function getScale(Product $product, ProductUnit $productUnit): ?int
    {
        $scale = $product->getUnitPrecision($productUnit->getCode());
        if ($scale) {
            return $scale->getPrecision();
        }

        return $productUnit->getDefaultPrecision();
    }

    /**
     * @param FormInterface $form
     *
     * @return Product|null
     */
    private function getProduct(FormInterface $form): ?Product
    {
        if (!$form->has($this->productFieldName)) {
            return null;
        }

        $productField = $form->get($this->productFieldName);
        if ($productField->isSubmitted() && !$productField->isValid()) {
            return null;
        }

        return $productField->getData();
    }

    /**
     * @param FormInterface $form
     *
     * @return ProductUnit|null
     */
    private function getProductUnit(FormInterface $form): ?ProductUnit
    {
        if (!$form->has($this->productUnitFieldName)) {
            return null;
        }

        $productUnitField = $form->get($this->productUnitFieldName);
        if ($productUnitField->isSubmitted() && !$productUnitField->isValid()) {
            return null;
        }

        return $productUnitField->getData();
    }
}
