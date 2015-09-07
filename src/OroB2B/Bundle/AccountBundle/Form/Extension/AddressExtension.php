<?php

namespace OroB2B\Bundle\AccountBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AddressExtension extends AbstractTypeExtension
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
        if ($this->isFrontend()) {
            $resolver->setDefault('region_route', 'orob2b_api_frontend_country_get_regions');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'oro_address';
    }

    /**
     * @return bool
     */
    protected function isFrontend()
    {
        $token = $this->tokenStorage->getToken();

        return $token && $token->getUser() instanceof AccountUser;
    }
}
