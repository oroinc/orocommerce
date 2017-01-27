<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;

class CustomerUserRoleSelectType extends AbstractType
{
    const NAME = 'oro_customer_customer_user_role_select';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @var string
     */
    protected $roleClass;

    /**
     * @param string $roleClass
     */
    public function setRoleClass($roleClass)
    {
        $this->roleClass = $roleClass;
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => $this->roleClass,
            'multiple' => true,
            'expanded' => true,
            'required' => true,
            'choice_label' => function ($role) {
                if (!($role instanceof CustomerUserRole)) {
                    return (string)$role;
                }

                $roleType = 'oro.customer.customeruserrole.type.';
                $roleType .= $role->isPredefined() ? 'predefined.label' : 'customizable.label';
                return sprintf('%s (%s)', $role->getLabel(), $this->translator->trans($roleType));
            }
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'entity';
    }
}
