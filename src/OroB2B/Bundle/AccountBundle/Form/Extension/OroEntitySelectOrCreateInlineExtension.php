<?php

namespace OroB2B\Bundle\AccountBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class OroEntitySelectOrCreateInlineExtension extends AbstractTypeExtension
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
            $resolver->setDefault('grid_widget_route', 'orob2b_account_frontend_datagrid_widget');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OroEntitySelectOrCreateInlineType::NAME;
    }
}
