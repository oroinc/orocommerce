<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FrontendVariantEnumSelectType extends AbstractType
{
    const NAME = 'oro_product_frontend_variant_enum_select';

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            if ($data !== null) {
                return;
            }

            /** @var ChoiceListInterface $choiceList */
            $choiceList = $form->getConfig()->getOption('choice_list');

            if ($choiceList === null) {
                return;
            }

            $choices = $choiceList->getChoices();

            $event->setData(reset($choices));
        });
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
        return EnumSelectType::NAME;
    }
}
