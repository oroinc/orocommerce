<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;

class EntityType extends StubEntityType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => '',
            'property' => '',
            'choice_list' => $this->choiceList,
            'configs' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();
                if ($data instanceof ArrayCollection) {
                    $event->setData($data->toArray());
                }
            }
        );
    }
}
