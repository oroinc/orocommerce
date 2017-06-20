<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class SaveAddressType extends AbstractType
{
    const NAME = 'oro_save_address';

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        $type = HiddenType::class;

        if ($this->securityFacade->isGranted('CREATE;entity:OroCustomerBundle:CustomerUserAddress') &&
            $this->securityFacade->isGranted('CREATE;entity:OroCustomerBundle:CustomerAddress')
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
        if (!$this->securityFacade->isGranted('CREATE;entity:OroCustomerBundle:CustomerUserAddress') &&
            !$this->securityFacade->isGranted('CREATE;entity:OroCustomerBundle:CustomerAddress')
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
