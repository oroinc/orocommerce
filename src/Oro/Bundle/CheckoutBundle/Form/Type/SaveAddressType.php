<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The form type for "Save address" checkbox.
 */
class SaveAddressType extends AbstractType
{
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->isCreateAllowed(CustomerUserAddress::class) && $this->isCreateAllowed(CustomerAddress::class)
            ? CheckboxType::class
            : HiddenType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        if (!$this->isCreateAllowed(CustomerUserAddress::class) && !$this->isCreateAllowed(CustomerAddress::class)) {
            $resolver->setDefaults([
               'data' => 0
            ]);
        }
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
        return 'oro_save_address';
    }

    private function isCreateAllowed(string $entityClass): bool
    {
        return $this->authorizationChecker->isGranted(
            'CREATE',
            ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, $entityClass)
        );
    }
}
