<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\AbstractContainerType;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ActionDropDownButtons extends AbstractContainerType
{
    const NAME = 'action_drop_down_buttons';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ActionLineButtonsType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['suffix' => 'drop_down_buttons', 'hide_icons' => true]);
        $resolver->setRequired(['exclude_action']);
    }
}
