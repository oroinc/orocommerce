<?php

namespace OroB2B\Bundle\AccountBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;

use OroB2B\Bundle\AccountBundle\Provider\VisibilityChoicesProvider;
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

    /** @var string */
    protected $categoryVisibilityClass;

    /** @var string */
    protected $accountClass;

    /** @var string */
    protected $accountGroupClass;

    /** @var VisibilityChoicesProvider */
    protected $visibilityChoicesProvider;

    /**
     * @param CategoryPostSetDataListener $postSetDataListener
     * @param CategoryPostSubmitListener $postSubmitListener
     * @param VisibilityChoicesProvider $visibilityChoicesProvider
     */
    public function __construct(
        CategoryPostSetDataListener $postSetDataListener,
        CategoryPostSubmitListener $postSubmitListener,
        VisibilityChoicesProvider $visibilityChoicesProvider
    ) {
        $this->postSetDataListener = $postSetDataListener;
        $this->postSubmitListener = $postSubmitListener;

        $this->visibilityChoicesProvider = $visibilityChoicesProvider;
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
        $category = isset($options['data']) ? $options['data'] : null;
        $choices = $this->visibilityChoicesProvider->getFormattedChoices($this->categoryVisibilityClass, $category);
        $builder
            ->add(
                'categoryVisibility',
                'choice',
                [
                    'required' => true,
                    'mapped' => false,
                    'label' => 'orob2b.account.visibility.categoryvisibility.entity_label',
                    'choices' => $choices
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
     * @param string $categoryVisibilityClass
     */
    public function setCategoryVisibilityClass($categoryVisibilityClass)
    {
        $this->categoryVisibilityClass = $categoryVisibilityClass;
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
