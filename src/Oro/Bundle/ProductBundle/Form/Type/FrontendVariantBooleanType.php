<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FrontendVariantBooleanType extends AbstractType
{
    const NAME = 'oro_product_frontend_variant_boolean';

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
//        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'setDefaultValues']);
//        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'setDefaultValues']);
    }

    /**
     * @param FormEvent $event
     */
    public function setDefaultValues(FormEvent $event)
    {
        // Set default (first) value of list
        $form = $event->getForm();
        $data = $event->getData();

        /** @var ChoiceListInterface $choiceList */
        $choiceList = $form->getConfig()->getOption('choice_list');

        if ($choiceList === null) {
            return;
        }

        $choices = $choiceList->getChoices();

        // Select first available key as default
        $choiceKeys = array_keys($choices);
        $disabledChoiceKeys = $form->getConfig()->getOption('non_default_options');

        $availableKeys = array_diff($choiceKeys, $disabledChoiceKeys);

        if (!$availableKeys) {
            return;
        }

        // Current selection in available list
        if (in_array($data, $availableKeys, true)) {
            return;
        }

        $event->setData(reset($availableKeys));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'non_default_options' => []
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
