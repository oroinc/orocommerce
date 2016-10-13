<?php

namespace Oro\Bundle\VisibilityBundle\Form\Type;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityPostSetDataListener;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityChoicesProvider;
use Oro\Bundle\VisibilityBundle\Validator\Constraints\VisibilityChangeSet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityVisibilityType extends AbstractType
{
    const NAME = 'oro_visibility_entity_visibility_type';

    const VISIBILITY = 'visibility';

    /**
     * @var VisibilityPostSetDataListener
     */
    protected $visibilityPostSetDataListener;

    /**
     * @var VisibilityChoicesProvider
     */
    protected $visibilityChoicesProvider;

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
                'scope' => null,
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
                    'label' => 'oro.visibility.to_all.label',
                    'choices' => $choices,
                ]
            )
            ->add(
                'account',
                EntityChangesetType::NAME,
                [
                    'class' => Account::class,
                    'context' => ['account' => ScopeCriteria::IS_NOT_NULL],
                    'constraints' => [new VisibilityChangeSet(['entityClass' => Account::class])],
                ]
            )
            ->add(
                'accountGroup',
                EntityChangesetType::NAME,
                [
                    'class' => AccountGroup::class,
                    'context' => ['accountGroup' => ScopeCriteria::IS_NOT_NULL],
                    'constraints' => [new VisibilityChangeSet(['entityClass' => AccountGroup::class])],
                ]
            );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this->visibilityPostSetDataListener, 'onPostSetData']);
    }
}
