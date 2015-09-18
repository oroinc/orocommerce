<?php

namespace OroB2B\Bundle\AccountBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;

use OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSetDataListener;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;

class CategoryFormExtension extends AbstractTypeExtension
{
    /** @var CategoryPostSetDataListener */
    protected $postSetDataListener;

    public function __construct(CategoryPostSetDataListener $postSetDataListener)
    {
        $this->postSetDataListener = $postSetDataListener;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CategoryType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'categoryVisibility',
                'oro_enum_select',
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'orob2b.account.categoryvisibility.entity_label',
                    'enum_code' => 'category_visibility',
                    'configs' => [
                        'allowClear' => false,
                        'placeholder' => 'orob2b.account.categoryvisibility.default.label',
                    ],
                ]
            )
            ->add(
                'visibilityForAccount',
                EntityChangesetType::NAME,
                [
                    'class' => 'OroB2B\Bundle\AccountBundle\Entity\Account',
                ]
            )
            ->add(
                'visibilityForAccountGroup',
                EntityChangesetType::NAME,
                [
                    'class' => 'OroB2B\Bundle\AccountBundle\Entity\AccountGroup',
                ]
            );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this->postSetDataListener, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this->postSubmitListener, 'onPostSubmit']);
    }
}
