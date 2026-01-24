<?php

namespace Oro\Bundle\VisibilityBundle\Form\Type;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Bundle\ScopeBundle\Form\FormScopeCriteriaResolver;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityPostSetDataListener;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityChoicesProvider;
use Oro\Bundle\VisibilityBundle\Validator\Constraints\VisibilityChangeSet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing entity visibility across all visibility levels.
 *
 * This form type provides fields for configuring visibility settings at three levels: all customers,
 * customer groups, and individual customers. It is used for both product and category visibility management
 * and supports scope-aware visibility configuration.
 */
class EntityVisibilityType extends AbstractType
{
    const NAME = 'oro_visibility_entity_visibility_type';

    const VISIBILITY = 'visibility';
    const ALL_FIELD = 'all';
    const ACCOUNT_FIELD = 'customer';
    const ACCOUNT_GROUP_FIELD = 'customerGroup';
    const ALL_CLASS = 'allClass';
    const ACCOUNT_GROUP_CLASS = 'customerGroupClass';
    const ACCOUNT_CLASS = 'customerClass';

    /**
     * @var VisibilityPostSetDataListener
     */
    protected $visibilityPostSetDataListener;

    /**
     * @var VisibilityChoicesProvider
     */
    protected $visibilityChoicesProvider;

    public function __construct(
        VisibilityPostSetDataListener $visibilityPostSetDataListener,
        VisibilityChoicesProvider $visibilityChoicesProvider
    ) {
        $this->visibilityPostSetDataListener = $visibilityPostSetDataListener;
        $this->visibilityChoicesProvider = $visibilityChoicesProvider;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                FormScopeCriteriaResolver::SCOPE => null,
            ]
        );
        $resolver->setRequired(
            [
                self::ALL_CLASS,
                self::ACCOUNT_GROUP_CLASS,
                self::ACCOUNT_CLASS,
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setMapped(false);

        $target = isset($options['data']) ? $options['data'] : null;
        $choices = $this->visibilityChoicesProvider->getFormattedChoices($options[self::ALL_CLASS], $target);

        $builder
            ->add(
                self::ALL_FIELD,
                ChoiceType::class,
                [
                    'required' => true,
                    'mapped' => false,
                    'label' => 'oro.visibility.to_all.label',
                    'choices' => $choices,
                ]
            )
            ->add(
                self::ACCOUNT_FIELD,
                EntityChangesetType::class,
                [
                    'class' => Customer::class,
                    'context' => ['customer' => ScopeCriteria::IS_NOT_NULL],
                    'constraints' => [new VisibilityChangeSet(['entityClass' => Customer::class])],
                ]
            )
            ->add(
                self::ACCOUNT_GROUP_FIELD,
                EntityChangesetType::class,
                [
                    'class' => CustomerGroup::class,
                    'context' => ['customerGroup' => ScopeCriteria::IS_NOT_NULL],
                    'constraints' => [new VisibilityChangeSet(['entityClass' => CustomerGroup::class])],
                ]
            );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this->visibilityPostSetDataListener, 'onPostSetData']);
    }
}
