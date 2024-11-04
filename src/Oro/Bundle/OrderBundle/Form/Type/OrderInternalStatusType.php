<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type to select order internal statuses based on 'select2' form type.
 */
class OrderInternalStatusType extends AbstractType
{
    public function __construct(private EnumOptionsProvider $enumOptionsProvider)
    {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'multiple' => true,
            'choices' => $this->enumOptionsProvider->getEnumChoicesByCode(Order::INTERNAL_STATUS_CODE)
        ]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return Select2ChoiceType::class;
    }
}
