<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;

class CheckoutWorkflowStateTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var CheckoutWorkflowState */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new CheckoutWorkflowState();
    }

    protected function tearDown()
    {
        unset($this->entity);
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
