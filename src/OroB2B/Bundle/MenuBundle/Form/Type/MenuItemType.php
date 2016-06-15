<?php

namespace OroB2B\Bundle\MenuBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use OroB2B\Bundle\MenuBundle\Entity\MenuItem;

class MenuItemType extends AbstractType
{
    const NAME = 'orob2b_menu_item';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                /** @var MenuItem $data */
                $data = $event->getData();
                if (!$data || !$data->getParent()) { // root menu item
                    $form->add(
                        'defaultTitle',
                        'text',
                        [
                            'label' => 'orob2b.menu.menuitem.titles.label',
                            'constraints' => [new NotBlank()],
                        ]
                    );
                } else {
                    $form
                        ->add(
                            'titles',
                            LocalizedFallbackValueCollectionType::NAME,
                            [
                                'required' => true,
                                'label' => 'orob2b.menu.menuitem.titles.label',
                                'options' => ['constraints' => [new NotBlank()]],
                            ]
                        )
                        ->add(
                            'uri',
                            'text',
                            [
                                'required' => false,
                                'label' => 'orob2b.menu.menuitem.uri.label',
                            ]
                        )
                        ->add(
                            'condition',
                            'text',
                            [
                                'required' => false,
                                'label' => 'orob2b.menu.menuitem.condition.label',
                                'tooltip'=> 'orob2b.menu.form.tooltip.menu_item_condition'
                            ]
                        )
                        ->add(
                            'image',
                            'oro_image',
                            [
                                'label' => 'orob2b.menu.menuitem.image.label',
                                'required' => false
                            ]
                        );
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'intention' => 'menuitem',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }
}
