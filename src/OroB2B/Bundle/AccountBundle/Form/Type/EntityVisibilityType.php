<?php

namespace Oro\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Bundle\AccountBundle\Form\EventListener\VisibilityPostSetDataListener;
use Oro\Bundle\AccountBundle\Form\EventListener\VisibilityPostSubmitListener;
use Oro\Bundle\AccountBundle\Provider\VisibilityChoicesProvider;
use Oro\Bundle\AccountBundle\Validator\Constraints\VisibilityChangeSet;

class EntityVisibilityType extends AbstractType
{
    const NAME = 'orob2b_account_entity_visibility_type';

    const VISIBILITY = 'visibility';

    /** @var VisibilityPostSetDataListener */
    protected $visibilityPostSetDataListener;

    /** @var VisibilityChoicesProvider */
    protected $visibilityChoicesProvider;

    /** @var string */
    protected $accountClass;

    /** @var string */
    protected $accountGroupClass;

    /**
     * @param VisibilityPostSetDataListener $visibilityPostSetDataListener
     * @param VisibilityChoicesProvider $visibilityChoicesProvider
     */
    public function __construct(
        VisibilityPostSetDataListener $visibilityPostSetDataListener,
        VisibilityChoicesProvider $visibilityChoicesProvider
    ) {
        $this->visibilityPostSetDataListener = $visibilityPostSetDataListener;
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
                'targetEntityField',
                'allClass',
                'accountGroupClass',
                'accountClass',
            ]
        );
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setMapped(false);

        $target = isset($options['data']) ? $options['data'] : null;
        $choices = $this->visibilityChoicesProvider->getFormattedChoices($options['allClass'], $target);

        $builder
            ->add(
                'all',
                'choice',
                [
                    'required' => true,
                    'mapped' => false,
                    'label' => 'oro.account.visibility.to_all.label',
                    'choices' => $choices,
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

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this->visibilityPostSetDataListener, 'onPostSetData']);
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
