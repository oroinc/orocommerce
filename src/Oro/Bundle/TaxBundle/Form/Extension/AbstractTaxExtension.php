<?php

namespace Oro\Bundle\TaxBundle\Form\Extension;

use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * The base class for handling tax code for different entities.
 */
abstract class AbstractTaxExtension extends AbstractTypeExtension
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addTaxCodeField($builder);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    public function onPostSetData(FormEvent $event): void
    {
        $entity = $event->getData();
        if (null === $entity) {
            return;
        }

        $taxCode = $this->getTaxCode($entity);
        $event->getForm()->get('taxCode')->setData($taxCode);
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $entity = $event->getData();
        if (null === $entity) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        $taxCodeNew = $form->get('taxCode')->getData();
        $taxCode = $this->getTaxCode($entity);
        $this->handleTaxCode($entity, $taxCode, $taxCodeNew);
    }

    abstract protected function addTaxCodeField(FormBuilderInterface $builder): void;

    abstract protected function getTaxCode(object $entity): ?AbstractTaxCode;

    abstract protected function handleTaxCode(
        object $entity,
        ?AbstractTaxCode $taxCode,
        ?AbstractTaxCode $taxCodeNew
    ): void;
}
