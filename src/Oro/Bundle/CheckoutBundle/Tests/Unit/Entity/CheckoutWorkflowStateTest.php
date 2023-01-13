<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Entity;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CheckoutWorkflowStateTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var CheckoutWorkflowState */
    private $entity;

    protected function setUp(): void
    {
        $this->entity = new CheckoutWorkflowState();
    }

    public function testAccessors()
    {
        $this->assertPropertyAccessors($this->entity, [
            ['id', 42],
            ['token', 'some string', false],
            ['entityId', 42],
            ['entityClass', 'some string'],
            ['stateData', ['data']],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['updatedAtSet', 1]
        ]);
    }

    public function testGetTokenDefaultValue()
    {
        $this->assertEquals(36, strlen($this->entity->getToken()));
    }
}
