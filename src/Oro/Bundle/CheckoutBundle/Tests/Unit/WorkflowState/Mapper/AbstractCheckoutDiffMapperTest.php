<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;
use Oro\Component\Testing\Unit\EntityTrait;

abstract class AbstractCheckoutDiffMapperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var Checkout */
    protected $checkout;

    /** @var CheckoutStateDiffMapperInterface */
    protected $mapper;

    protected function setUp(): void
    {
        $this->checkout = $this->getEntity(Checkout::class, ['id' => 1]);
        $this->mapper = $this->getMapper();
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
