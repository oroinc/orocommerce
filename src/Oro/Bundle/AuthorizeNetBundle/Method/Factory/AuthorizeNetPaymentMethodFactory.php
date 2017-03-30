<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\Factory;

use Oro\Bundle\AuthorizeNetBundle\Method\AuthorizeNetPaymentMethod;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Symfony\Component\Routing\RouterInterface;

class AuthorizeNetPaymentMethodFactory implements AuthorizeNetPaymentMethodFactoryInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritDoc}
     */
    public function create(AuthorizeNetConfigInterface $config)
    {
        return new AuthorizeNetPaymentMethod(
            $config,
            $this->router
        );
    }
}
