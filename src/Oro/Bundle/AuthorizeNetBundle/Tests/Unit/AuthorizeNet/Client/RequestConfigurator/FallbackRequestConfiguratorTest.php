<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Client\RequestConfigurator;

use net\authorize\api\contract\v1 as AnetAPI;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\RequestConfigurator\FallbackRequestConfigurator;

class FallbackRequestConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var FallbackRequestConfigurator
     */
    protected $fallbackRequestConfigurator;

    protected function setUp()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->fallbackRequestConfigurator = new FallbackRequestConfigurator($this->propertyAccessor);
    }

    protected function tearDown()
    {
        unset($this->fallbackRequestConfigurator, $this->propertyAccessor);
    }

    public function testGetPriority()
    {
        $this->assertEquals(-10, $this->fallbackRequestConfigurator->getPriority());
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->fallbackRequestConfigurator->isApplicable([]));
    }

    public function testHandle()
    {
        $request = new AnetAPI\CreateTransactionRequest();

        $transactionRequestType = $this->createMock(AnetAPI\TransactionRequestType::class);
        $clientId = 'client_id';

        $options = ['transactionRequest' => $transactionRequestType, 'clientId' => $clientId];

        $this->fallbackRequestConfigurator->handle($request, $options);

        $this->assertEquals($request->getTransactionRequest(), $transactionRequestType);
        $this->assertEquals($request->getClientId(), $clientId);
    }
}
