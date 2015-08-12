<?php

namespace OroB2B\Bundle\AccountBundle\Twig;

use OroB2B\Bundle\AccountBundle\Security\AccountUserProvider;

class AccountExtension extends \Twig_Extension
{
    /**
     * @var AccountUserProvider
     */
    protected $securityProvider;

    /**
     * @param AccountUserProvider
     */
    public function __construct(AccountUserProvider $securityProvider)
    {
        $this->securityProvider = $securityProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'is_granted_view_account_user' => new \Twig_Function_Method($this, 'isGrantedViewAccountUser'),
        );
    }

    /**
     * @param string $object
     * @return bool
     */
    public function isGrantedViewAccountUser($object)
    {
        return $this->securityProvider->isGrantedViewAccountUser($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'account_extension';
    }
}
