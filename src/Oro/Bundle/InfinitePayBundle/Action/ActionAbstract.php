<?php

namespace Oro\Bundle\InfinitePayBundle\Action;

use Oro\Bundle\InfinitePayBundle\Action\Mapper\ResponseMapperInterface;
use Oro\Bundle\InfinitePayBundle\Configuration\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Gateway\GatewayInterface;

abstract class ActionAbstract implements ActionInterface
{
    /** @var GatewayInterface */
    protected $gateway;

    /**
     * @var InfinitePayConfigInterface
     */
    protected $config;

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
        InfinitePayConfigInterface $config
    ) {
        $this->gateway = $gateway;
        $this->config = $config;
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
}
