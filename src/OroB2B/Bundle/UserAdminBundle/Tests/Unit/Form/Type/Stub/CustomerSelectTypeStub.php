<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerSelectType;

class CustomerSelectTypeStub extends AbstractType
{
    /**
     * @var array
     */
    protected $choiceList = [];

    /**
     * @param array $choices
     */
    public function __construct(array $choices)
    {
        $keys = array_map('strval', array_keys($choices));
        $values = array_values($choices);

        $this->choiceList = new ChoiceList([], []);

        $keysReflection = new \ReflectionProperty(get_class($this->choiceList), 'values');
        $keysReflection->setAccessible(true);
        $keysReflection->setValue($this->choiceList, $keys);

        $valuesReflection = new \ReflectionProperty(get_class($this->choiceList), 'choices');
        $valuesReflection->setAccessible(true);
        $valuesReflection->setValue($this->choiceList, $values);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['choice_list' => $this->choiceList]);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return CustomerSelectType::NAME;
    }
}
