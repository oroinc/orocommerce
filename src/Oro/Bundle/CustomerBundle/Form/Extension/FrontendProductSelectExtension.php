<?php

namespace Oro\Bundle\CustomerBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;

class FrontendProductSelectExtension extends AbstractTypeExtension
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $token = $this->tokenStorage->getToken();
        if ($token && $token->getUser() instanceof AccountUser) {
            $resolver->setDefault('grid_name', 'products-select-grid-frontend');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductSelectType::NAME;
    }
}
