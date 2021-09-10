<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Provider\DiscountFormTypeProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiscountConfigurationType extends AbstractType
{
    const NAME = 'oro_promotion_discount_configuration';
    const TYPE = 'type';
    const OPTIONS = 'options';

    /**
     * @var DiscountFormTypeProvider
     */
    private $discountFormTypeProvider;

    public function __construct(DiscountFormTypeProvider $discountFormTypeProvider)
    {
        $this->discountFormTypeProvider = $discountFormTypeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => DiscountConfiguration::class,
                'label' => false,
                'discount_choices' => $this->getDiscountChoices()
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::TYPE,
                ChoiceType::class,
                [
                    'choices' => $options['discount_choices'],
                    'label' => 'oro.discount.type.label',
                    'required' => false,
                    'placeholder' => false
                ]
            );

        $builder->setAttribute('discount_prototypes', $this->getOptionFormPrototypes($builder));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $discountPrototypes = $form->getConfig()->getAttribute('discount_prototypes');

        $prototypes = [];
        /** @var FormInterface $discountPrototype */
        foreach ($discountPrototypes as $name => $discountPrototype) {
            $prototypes[$name] = $discountPrototype->setParent($form)->createView($view);
        }

        $view->vars['prototypes'] = $prototypes;
    }

    public function preSetData(FormEvent $event)
    {
        /** @var DiscountConfiguration $discountConfiguration */
        $discountConfiguration = $event->getData();
        $form = $event->getForm();

        $discountOptionsFormType = $this->discountFormTypeProvider->getDefaultFormType();
        if ($discountConfiguration instanceof DiscountConfiguration) {
            $type = $discountConfiguration->getType();
            $discountOptionsFormType = $this->discountFormTypeProvider->getFormType($type);
        }

        $form->add(self::OPTIONS, $discountOptionsFormType);
    }

    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (array_key_exists(self::TYPE, $data)) {
            $discountConfigFormType = $this->discountFormTypeProvider->getFormType($data[self::TYPE]);

            $form->add(self::OPTIONS, $discountConfigFormType);
        }
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
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * @return array
     */
    private function getDiscountChoices()
    {
        $formTypes = $this->discountFormTypeProvider->getFormTypes();
        $choices = [];
        foreach ($formTypes as $type => $formType) {
            $choices['oro.discount.type.choices.' . $type] = $type;
        }

        return $choices;
    }

    private function getOptionFormPrototypes(FormBuilderInterface $builder): array
    {
        $prototypes = [];
        foreach ($this->discountFormTypeProvider->getFormTypes() as $name => $type) {
            $prototypes[$name] = $builder->create(self::OPTIONS, $type)->getForm();
        }

        return $prototypes;
    }
}
