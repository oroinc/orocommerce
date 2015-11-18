<?php

namespace Oro\Component\Testing\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityType extends AbstractType
{
    /** @var ChoiceList */
    protected $choiceList = [];

    /** @var string */
    protected $name;

    /** @var array|null */
    protected $options;

    /**
     * @param array $choices
     * @param string $name
     * @param array $options
     */
    public function __construct(array $choices, $name = 'entity', array $options = null)
    {
        $this->name = $name;
        $this->options = $options;

        $keys = array_map('strval', array_keys($choices));
        $values = array_values($choices);

        $this->choiceList = new ChoiceList([], []);

        $keysReflection = new \ReflectionProperty(get_class($this->choiceList), 'values');
        $keysReflection->setAccessible(true);
        $keysReflection->setValue($this->choiceList, $keys);

        $valuesReflection = new \ReflectionProperty(get_class($this->choiceList), 'choices');
        $valuesReflection->setAccessible(true);
        $valuesReflection->setValue($this->choiceList, $values);

        $remainingViews = [];
        foreach ($choices as $key => $value) {
            $remainingViews[] = new ChoiceView($value, $key, $key);
        }

        $valuesReflection = new \ReflectionProperty(get_class($this->choiceList), 'remainingViews');
        $valuesReflection->setAccessible(true);
        $valuesReflection->setValue($this->choiceList, $remainingViews);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaultOptions = [
            'choice_list' => $this->choiceList,
            'query_builder' => null,
            'create_enabled' => false,
            'class' => null
        ];

        if ($this->options) {
            $defaultOptions = array_merge($defaultOptions, $this->options);
        }

        $resolver->setDefaults($defaultOptions);
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
        return $this->name;
    }
}
