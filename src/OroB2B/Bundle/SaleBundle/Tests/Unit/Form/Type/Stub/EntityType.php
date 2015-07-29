<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
