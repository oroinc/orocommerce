<?php

namespace OroB2B\Bundle\AccountBundle\Twig;

use OroB2B\Bundle\AccountBundle\SecurityFacade;

class SecurityExtension extends \Twig_Extension
{
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
    public function getFunctions()
    {
        return array(
            'account_granted_view_account_user' => new \Twig_Function_Method($this, 'isGrantedViewAccountUser'),
        );
    }

    /**
     * @param string $object
     * @return bool
     */
    public function isGrantedViewAccountUser($object)
    {
        return $this->securityFacade->isGrantedViewAccountUser($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'account_security_extension';
    }
}
