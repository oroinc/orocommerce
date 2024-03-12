<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Type\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\Tools\EntityStateChecker;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Updates "kitItemLineItems" and "price" form fields according to the selected product.
 */
class OrderLineItemProductListener implements EventSubscriberInterface
{
    private EntityStateChecker $entityStateChecker;

    public function __construct(EntityStateChecker $entityStateChecker)
    {
        $this->entityStateChecker = $entityStateChecker;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SET_DATA => 'onPostSetData',
            FormEvents::POST_SUBMIT => 'onPostSubmit',
        ];
    }

    public function onPostSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var Product|null $product */
        $product = $event->getData();

        FormUtils::replaceField($form->getParent(), 'kitItemLineItems', [
            'required' => $product?->isKit() === true,
            'product' => $product,
        ]);

        $this->setReadonlyForPriceField($product, $form);
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var Product|null $product */
        $product = $form->getData();
        /** @var OrderLineItem|null $orderLineItem */
        $orderLineItem = $form->getParent()?->getData();
        $modifyOptions = [
            'required' => $product?->isKit() === true,
            'product' => $product,
        ];

        if ($orderLineItem !== null) {
            /** @var Product|null $originalProduct */
            $originalProduct = $this->entityStateChecker->getOriginalEntityFieldData($orderLineItem, 'product');
            // Checks if the selected product is changed.
            if ($originalProduct !== $product) {
                // Sets empty collection of kit item line items if the product is changed.
                $modifyOptions['data'] = new ArrayCollection();
            }
        }

        $this->setReadonlyForPriceField($product, $form);
        FormUtils::replaceField($form->getParent(), 'kitItemLineItems', $modifyOptions);
    }

    private function setReadonlyForPriceField(?Product $product, FormInterface $form): void
    {
        if ($product?->isKit()) {
            FormUtils::replaceField($form->getParent(), 'price', [
                'readonly' => true
            ]);
        }
    }
}
