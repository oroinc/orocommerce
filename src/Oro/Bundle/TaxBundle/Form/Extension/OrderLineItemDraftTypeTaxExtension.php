<?php

namespace Oro\Bundle\TaxBundle\Form\Extension;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemDraftType;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Adds freeFormTaxCode field to OrderLineItemDraftType form when in free form mode.
 */
class OrderLineItemDraftTypeTaxExtension extends AbstractTypeExtension
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, $this->onPostSetData(...));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->onPreSubmit(...));
    }

    /**
     * Adds freeFormTaxCode field based on whether the line item is in free form mode.
     */
    private function onPostSetData(FormEvent $event): void
    {
        $form = $event->getForm();

        if ($form->get('isFreeForm')->getData()) {
            $this->addFreeFormTaxCodeField($form);
        }
    }

    /**
     * Adds or removes freeFormTaxCode field before form submission based on the submitted data and the trigger field.
     * Clears the freeFormTaxCode value from the entity and submitted data when the trigger field is isFreeForm,
     * or the submitted isFreeForm flag is false.
     */
    private function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();
        $drySubmitTrigger = $data['drySubmitTrigger'] ?? null;

        if ($drySubmitTrigger) {
            $drySubmitTriggerPropertyPath = new PropertyPath($drySubmitTrigger);
            $triggerField = $drySubmitTriggerPropertyPath->getElement(0);
            if ($triggerField === 'isFreeForm') {
                /** @var OrderLineItem $orderLineItem */
                $orderLineItem = $form->getData();
                $orderLineItem->setFreeFormTaxCode(null);

                unset($data['freeFormTaxCode']);
                $event->setData($data);
            }
        }

        if ($data['isFreeForm'] ?? false) {
            $this->addFreeFormTaxCodeField($form);
        } else {
            $form->remove('freeFormTaxCode');

            /** @var OrderLineItem $orderLineItem */
            $orderLineItem = $form->getData();
            $orderLineItem->setFreeFormTaxCode(null);

            unset($data['freeFormTaxCode']);
            $event->setData($data);
        }
    }

    private function addFreeFormTaxCodeField($form): void
    {
        $form->add(
            'freeFormTaxCode',
            ProductTaxCodeAutocompleteType::class,
            [
                'required' => false,
                'create_enabled' => false,
                'label' => 'oro.order.orderlineitem.free_form_tax_code.label',
                'tooltip' => 'oro.order.orderlineitem.free_form_tax_code.description',
            ]
        );
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [OrderLineItemDraftType::class];
    }
}
