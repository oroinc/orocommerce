<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type to select order internal statuses based on 'select2' form type.
 */
class OrderInternalStatusType extends AbstractType
{
    private EnumValueProvider $enumValueProvider;

    public function __construct(EnumValueProvider $enumValueProvider)
    {
        $this->enumValueProvider = $enumValueProvider;
    }

    /**
     * {@inheritSoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'multiple' => true,
            'choices' => $this->enumValueProvider->getEnumChoicesByCode(Order::INTERNAL_STATUS_CODE)
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): ?string
    {
        return Select2ChoiceType::class;
    }
}
