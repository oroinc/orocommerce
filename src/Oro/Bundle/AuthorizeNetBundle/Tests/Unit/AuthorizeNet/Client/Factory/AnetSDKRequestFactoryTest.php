<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Client\Factory;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\Factory\AnetSDKRequestFactory;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\RequestConfigurator\RequestConfiguratorInterface;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\RequestConfigurator\RequestConfiguratorRegistry;

class AnetSDKRequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var AnetSDKRequestFactory */
    protected $factory;

    /** @var RequestConfiguratorRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestConfiguratorRegistry;

    protected function setUp()
    {
        $this->requestConfiguratorRegistry = $this->createMock(RequestConfiguratorRegistry::class);
        $this->factory = new AnetSDKRequestFactory($this->requestConfiguratorRegistry);
    }

    protected function tearDown()
    {
        unset($this->factory, $this->requestConfiguratorRegistry);
    }

    public function testCreateRequest()
    {
        $options = [];

        $requestConfigurator1 = $this->createMock(RequestConfiguratorInterface::class);
        $requestConfigurator1->expects($this->once())
            ->method('isApplicable')
            ->with($options)
            ->willReturn(true);

        $requestConfigurator1->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(AnetAPI\CreateTransactionRequest::class), $options);

        $requestConfigurator2 = $this->createMock(RequestConfiguratorInterface::class);
        $requestConfigurator2->expects($this->once())
            ->method('isApplicable')
            ->with($options)
            ->willReturn(false);

        $requestConfigurator2->expects($this->never())
            ->method('handle');

        $this->requestConfiguratorRegistry->expects($this->once())
            ->method('getRequestConfigurators')
            ->willReturn([$requestConfigurator1, $requestConfigurator2]);

        $request = $this->factory->createRequest($options);

        $this->assertInstanceOf(AnetAPI\CreateTransactionRequest::class, $request);
    }

    public function testCreateController()
    {
        // Create mocks to successfully create CreateTransactionController
        $merchantAuthType = $this->createMock(AnetAPI\MerchantAuthenticationType::class);

        $request = $this->createMock(AnetAPI\CreateTransactionRequest::class);
        $request->expects($this->any())
            ->method('getMerchantAuthentication')
            ->willReturn($merchantAuthType);

        $controller = $this->factory->createController($request);

        $this->assertInstanceOf(AnetController\CreateTransactionController::class, $controller);
    }
}
