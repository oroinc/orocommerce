<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\Tools\EntityStateChecker;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Updates "kitItemLineItems" form field according to the selected product.
 */
class RequestProductProductListener implements EventSubscriberInterface
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
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var Product|null $product */
        $product = $form->getData();
        /** @var RequestProduct|null $requestProduct */
        $requestProduct = $form->getParent()?->getData();
        $modifyOptions = [
            'required' => $product?->isKit() === true,
            'product' => $product,
        ];

        if ($requestProduct !== null) {
            /** @var Product|null $originalProduct */
            $originalProduct = $this->entityStateChecker->getOriginalEntityFieldData($requestProduct, 'product');
            // Checks if the selected product is changed.
            if ($originalProduct !== $product) {
                // Sets empty collection of kit item line items if the product is changed.
                $modifyOptions['data'] = new ArrayCollection();
            }
        }

        FormUtils::replaceField($form->getParent(), 'kitItemLineItems', $modifyOptions);
    }
}
