<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;
use Oro\Component\Testing\Unit\EntityTrait;

abstract class AbstractCheckoutDiffMapperTest extends \PHPUnit\Framework\TestCase
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

    protected function setUp(): void
    {
        $this->checkout = $this->getEntity(Checkout::class, ['id' => 1]);
        $this->mapper = $this->getMapper();
    }

    protected function tearDown(): void
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
