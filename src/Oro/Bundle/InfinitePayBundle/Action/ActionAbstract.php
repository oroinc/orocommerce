<?php

namespace Oro\Bundle\InfinitePayBundle\Action;

use Oro\Bundle\InfinitePayBundle\Action\Mapper\ResponseMapperInterface;
use Oro\Bundle\InfinitePayBundle\Gateway\GatewayInterface;
use Oro\Bundle\InfinitePayBundle\Method\Config\Provider\InfinitePayConfigProviderInterface;

abstract class ActionAbstract implements ActionInterface
{
    /** @var GatewayInterface */
    protected $gateway;

    /**
     * @var InfinitePayConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @var ResponseMapperInterface
     */
    protected $responseMapper;

    /**
     * @var RequestMapperInterface
     */
    protected $requestMapper;

    public function __construct(
        GatewayInterface $gateway,
        InfinitePayConfigProviderInterface $configProvider
    ) {
        $this->gateway = $gateway;
        $this->configProvider = $configProvider;
    }

    /**
     * @param RequestMapperInterface $requestMapper
     *
     * @return ActionInterface
     */
    public function setRequestMapper(RequestMapperInterface $requestMapper)
    {
        $this->requestMapper = $requestMapper;

        return $this;
    }

    /**
     * @param ResponseMapperInterface $responseMapper
     *
     * @return ActionInterface
     */
    public function setResponseMapper(ResponseMapperInterface $responseMapper)
    {
        $this->responseMapper = $responseMapper;

        return $this;
    }

    protected function getPaymentMethodConfig($paymentMethodIdentifier)
    {
        return $this->configProvider->getPaymentConfig($paymentMethodIdentifier);
    }
}
