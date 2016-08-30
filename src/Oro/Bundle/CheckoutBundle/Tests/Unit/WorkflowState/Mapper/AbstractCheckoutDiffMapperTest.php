<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;

abstract class AbstractCheckoutDiffMapperTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var CheckoutStateDiffMapperInterface
     */
    protected $mapper;

    /**
     * @var Checkout
     */
    protected $checkout;

    protected function setUp()
    {
        $this->checkout = $this->getEntity(Checkout::class, ['id' => 1]);
        $this->mapper = $this->getMapper();
    }

    protected function tearDown()
    {
        unset($this->mapper, $this->checkout);
    }

    public function testIsEntitySupported()
    {
        $this->assertTrue($this->mapper->isEntitySupported($this->checkout));
    }

    public function testIsEntitySupportedNotObject()
    {
        $this->assertFalse($this->mapper->isEntitySupported('string'));
    }

    public function testIsEntitySupportedUnsupportedEntity()
    {
        $this->assertFalse($this->mapper->isEntitySupported(new \stdClass()));
    }

    /**
     * @return CheckoutStateDiffMapperInterface
     */
    abstract protected function getMapper();
}
