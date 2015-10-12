<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;

use OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSetDataListener;
use OroB2B\Bundle\AccountBundle\Form\EventListener\CategoryPostSubmitListener;
use OroB2B\Bundle\AccountBundle\Provider\VisibilityChoicesProvider;
use OroB2B\Bundle\AccountBundle\Validator\Constraints\VisibilityChangeSet;

class EntityVisibilityType extends AbstractType
{
    const NAME = 'orob2b_account_entity_visibility_type';

    /** @var CategoryPostSetDataListener */
    protected $postSetDataListener;

    /** @var CategoryPostSubmitListener */
    protected $postSubmitListener;

    /** @var VisibilityChoicesProvider */
    protected $visibilityChoicesProvider;

    /** @var string */
    protected $accountClass;

    /** @var string */
    protected $accountGroupClass;

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
        /**
         * TODO: CategoryPostSetDataListener/CategoryPostSubmitListener to PostSetDataListener/PostSubmitListener
         */
        $this->postSetDataListener = $postSetDataListener;
        $this->postSubmitListener = $postSubmitListener;
        $this->visibilityChoicesProvider = $visibilityChoicesProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'website' => null,
            ]
        );
        $resolver->setRequired(
            [
                'visibilityToAllClass',
                'visibilityToAccountGroupClass',
                'visibilityToAccountClass',
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
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setMapped(false);

        $target = isset($options['data']) ? $options['data'] : null;
        $choices = $this->visibilityChoicesProvider->getFormattedChoices($options['visibilityToAllClass'], $target);

        $builder
            ->add(
                'all',
                'choice',
                [
                    'required' => true,
                    'mapped' => false,
                    'label' => 'orob2b.account.visibility.to_all.label',
                    'choices' => $choices
                ]
            )
            ->add(
                'account',
                EntityChangesetType::NAME,
                [
                    'class' => $this->accountClass,
                    'constraints' => [new VisibilityChangeSet(['entityClass' => $this->accountClass])],
                ]
            )
            ->add(
                'accountGroup',
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
