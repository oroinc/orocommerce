<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Form\Extension;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemDraftType;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Form\Type\OffersType;
use Oro\Bundle\RFPBundle\Provider\OffersFromRequestProductProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Adds an `offers` field to {@see OrderLineItemDraftType} when the line item is linked to an RFQ RequestProduct.
 */
final class OrderLineItemDraftOffersExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly OffersFromRequestProductProvider $offersFromRequestProductProvider,
    ) {
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [OrderLineItemDraftType::class];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, $this->addOffersOnPostSetData(...));
    }

    private function addOffersOnPostSetData(FormEvent $event): void
    {
        /** @var OrderLineItem|null $orderLineItem */
        $orderLineItem = $event->getData();
        /** @var RequestProduct|null $requestProduct */
        $requestProduct = $orderLineItem?->getRequestProduct();
        if (!$requestProduct) {
            return;
        }

        $offers = $this->offersFromRequestProductProvider
            ->getOffers($requestProduct, $orderLineItem->getCurrency());

        if ($offers === []) {
            return;
        }

        $event->getForm()->add(
            'offers',
            OffersType::class,
            [
                'label' => 'oro.order.orderlineitem.draft_update_form.offers.label',
                'required' => false,
                'mapped' => false,
                'placeholder' => false,
                'offers' => $offers,
            ]
        );
    }
}
