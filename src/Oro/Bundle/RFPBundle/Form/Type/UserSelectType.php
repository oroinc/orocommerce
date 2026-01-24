<?php

namespace Oro\Bundle\RFPBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RFPBundle\Form\DataTransformer\UserIdToEmailTransformer;
use Oro\Bundle\UserBundle\Form\Type\UserSelectType as BaseUserSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting users with email transformation in RFP context.
 *
 * This form type extends the base {@see BaseUserSelectType} and applies a custom data transformer
 * ({@see UserIdToEmailTransformer}) to convert between user IDs and email addresses. This is
 * particularly useful in RFP workflows where user assignments need to be handled via email identifiers.
 */
class UserSelectType extends AbstractType
{
    const NAME = 'oro_rfp_user_select';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'transformer' => new UserIdToEmailTransformer($this->registry)
        ]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return BaseUserSelectType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
