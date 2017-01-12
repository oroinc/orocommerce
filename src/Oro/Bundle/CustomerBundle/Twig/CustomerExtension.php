<?php

namespace Oro\Bundle\CustomerBundle\Twig;

use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;

class CustomerExtension extends \Twig_Extension
{
    const NAME = 'customer_extension';

    /**
     * @var CustomerUserProvider
     */
    protected $securityProvider;

    /**
     * @param CustomerUserProvider $securityProvider
     */
    public function __construct(CustomerUserProvider $securityProvider)
    {
        $this->securityProvider = $securityProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'is_granted_view_customer_user' => new \Twig_Function_Method($this, 'isGrantedViewCustomerUser'),
        );
    }

    /**
     * @param string $object
     * @return bool
     */
    public function isGrantedViewCustomerUser($object)
    {
        return $this->securityProvider->isGrantedViewCustomerUser($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
