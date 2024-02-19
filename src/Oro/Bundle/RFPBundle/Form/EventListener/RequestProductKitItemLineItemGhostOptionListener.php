<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Form\EventListener;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Component\PhpUtils\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Adds a "ghost" option to the "product" field of the kit item line item form.
 */
class RequestProductKitItemLineItemGhostOptionListener implements EventSubscriberInterface
{
    private const GHOST_ID = PHP_INT_MIN;

    private string $ghostOptionClass = Product::class;

    private array $ghostOptionChoiceAttributes = ['data-ghost-option' => true, 'class' => 'ghost-option'];

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
        ];
    }

    public function setGhostOptionClass(string $ghostOptionClass): void
    {
        $this->ghostOptionClass = $ghostOptionClass;
    }

    public function setGhostOptionChoiceAttributes(array $ghostOptionChoiceAttributes): void
    {
        $this->ghostOptionChoiceAttributes = $ghostOptionChoiceAttributes;
    }

    /**
     * Adds a "ghost" option to the "product" field of the kit item line item form if the currently specified product in
     * an already existing kit item line item is not present in the choices array. Allows avoiding form validation
     * error when submitting an already existing line item that is not valid anymore in the current application state.
     */
    public function onPreSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var RequestProductKitItemLineItem|null $kitItemLineItem */
        $kitItemLineItem = $event->getData();
        if ($kitItemLineItem?->getId() === null) {
            return;
        }

        $product = $kitItemLineItem->getProduct();
        if ($product === null) {
            if ($kitItemLineItem->getProductSku() === null) {
                return;
            }

            // Creates a temporary product object for the ghost-option.
            $product = $this->createGhostProduct($kitItemLineItem->getProductSku(), $kitItemLineItem->getProductName());
        }

        $productChoices = $form->get('product')->getConfig()->getOption('choices');
        if (in_array($product, $productChoices, true)) {
            return;
        }

        // Adds the ghost-option to the choices.
        $ghostOptionId = $product->getId();
        FormUtils::replaceField(
            $form,
            'product',
            [
                'data' => $product,
                'setter' => function (RequestProductKitItemLineItem $kitItemLineItem, ?Product $product) {
                    if ($product?->getId() !== self::GHOST_ID) {
                        // Ensures that even null $product is set to kit item line item,
                        // except the ghost-option product - the one that is not available anymore, but
                        // must not block the submission of a form.
                        $kitItemLineItem->setProduct($product);
                    }
                },
                'choices' => array_merge([$product], $productChoices),
                'choice_attr' => function (?Product $product, string $label, int $id) use ($ghostOptionId) {
                    if ($product?->getId() === $ghostOptionId) {
                        return $this->ghostOptionChoiceAttributes;
                    }

                    return [];
                },
            ]
        );
    }

    /**
     * Creates a temporary product object for the ghost-option.
     */
    private function createGhostProduct(string $sku, string $productName): object
    {
        $product = (new ($this->ghostOptionClass)())
            ->setSku($sku)
            ->setDefaultName($productName);

        ReflectionUtil::getProperty(new \ReflectionClass(Product::class), 'id')
            ?->setValue($product, self::GHOST_ID);

        return $product;
    }
}
