<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\AbstractType;

class ActionCombinedButtonsType extends AbstractType
{
    const NAME = 'action_combined_buttons';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @inheritDoc
     */
    public function getParent()
    {
        return ActionLineButtonsType::NAME;
    }
}
