<?php

namespace Oro\Component\Testing\Unit\Form\Type\Stub;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EnumSelectType extends EntityType
{
    const NAME = 'oro_enum_select';
    /**
     * @param array $choices
     */
    public function __construct(array $choices)
    {
        $choices = $this->getEnumChoices($choices);
        parent::__construct($choices, static::NAME);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'choice_list' => $this->choiceList,
                'enum_code' => null,
                'configs' => []
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param array $choices
     * @return array
     */
    protected function getEnumChoices($choices)
    {
        $enumChoices = [];
        foreach ($choices as $choice) {
            $enumChoices[$choice->getId()] = $choice;
        }
        return $enumChoices;
    }
}
