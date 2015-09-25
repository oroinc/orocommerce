<?php

namespace OroB2B\Bundle\AccountBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;

use OroB2B\Bundle\AccountBundle\Validator\Constraints\VisibilityChangeSet;
use OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSetDataListener;
use OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSubmitListener;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;

class CategoryFormExtension extends AbstractTypeExtension
{
    /** @var CategoryPostSetDataListener */
    protected $postSetDataListener;

    /** @var CategoryPostSubmitListener */
    protected $postSubmitListener;

    /** @var  string */
    protected $accountClass;

    /** @var  string */
    protected $accountGroupClass;

    /**
     * @param CategoryPostSetDataListener $postSetDataListener
     * @param CategoryPostSubmitListener $postSubmitListener
     */
    public function __construct(
        CategoryPostSetDataListener $postSetDataListener,
        CategoryPostSubmitListener $postSubmitListener
    ) {
        $this->postSetDataListener = $postSetDataListener;
        $this->postSubmitListener = $postSubmitListener;
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
                    'required' => true,
                    'mapped' => false,
                    'label' => 'orob2b.account.categoryvisibility.entity_label',
                    'enum_code' => 'category_visibility',
                    'configs' => [
                        'allowClear' => false,
                    ],
                ]
            )
            ->add(
                'visibilityForAccount',
                EntityChangesetType::NAME,
                [
                    'class' => $this->accountClass,
                    'constraints' => [new VisibilityChangeSet(['entityClass' => $this->accountClass])],
                ]
            )
            ->add(
                'visibilityForAccountGroup',
                EntityChangesetType::NAME,
                [
                    'class' => $this->accountGroupClass,
                    'constraints' => [new VisibilityChangeSet(['entityClass' => $this->accountGroupClass])],
                ]
            );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this->postSetDataListener, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this->postSubmitListener, 'onPostSubmit']);
    }

    /**
     * @param string $accountClass
     */
    public function setAccountClass($accountClass)
    {
        $this->accountClass = $accountClass;
    }

    /**
     * @param string $accountGroupClass
     */
    public function setAccountGroupClass($accountGroupClass)
    {
        $this->accountGroupClass = $accountGroupClass;
    }
}
