<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SaveAddressType extends AbstractType
{
    const NAME = 'oro_save_address';

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        $type = HiddenType::class;

        if ($this->authorizationChecker->isGranted('CREATE;entity:OroCustomerBundle:CustomerUserAddress')
            && $this->authorizationChecker->isGranted('CREATE;entity:OroCustomerBundle:CustomerAddress')
        ) {
            $type = CheckboxType::class;
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        if (!$this->authorizationChecker->isGranted('CREATE;entity:OroCustomerBundle:CustomerUserAddress')
            && !$this->authorizationChecker->isGranted('CREATE;entity:OroCustomerBundle:CustomerAddress')
        ) {
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
        return self::NAME;
    }
}
