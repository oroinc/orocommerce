<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use OroB2B\Bundle\CheckoutBundle\EventListener\AbstractCheckoutEntityListener;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;
use OroB2B\Bundle\CheckoutBundle\EventListener\CheckoutEntityListener;

class CheckoutEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractCheckoutEntityListener
     */
    protected $listener;

    /**
     * @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        /** @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\RegistryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $this->listener = new CheckoutEntityListener($this->workflowManager, $registry);
    }

    /**
     * @dataProvider onGetCheckoutEntityDataProvider
     *
     * @param bool $isStartWorkflowAllowed
     * @param string $type
     * @param int $id
     * @param CheckoutSource $source
     * @param CheckoutInterface $expected
     */
    public function testOnGetCheckoutEntity(
        $isStartWorkflowAllowed,
        $type,
        $id,
        $source,
        CheckoutInterface $expected = null
    ) {
        $this->workflowManager->expects($this->any())
            ->method('isStartTransitionAvailable')
            ->willReturn($isStartWorkflowAllowed);

        $event = new CheckoutEntityEvent();
        $event->setType($type);

        if ($id) {
            $this->repository->expects($this->once())
                ->method('find')
                ->with($id)
                ->willReturn($expected);

            $event->setCheckoutId($id);
        }

        if ($source) {
            $this->repository->expects($this->once())
                ->method('findOneBy')
                ->with(['source' => $source])
                ->willReturn($expected);

            $event->setSource($source);
        }

        $this->listener->onGetCheckoutEntity($event);
        $actual = $event->getCheckoutEntity();
        $this->assertEquals($expected, $actual);
        if ($source) {
            $this->assertSame($source, $actual->getSource());
        }
    }

    /**
     * @return array
     */
    public function onGetCheckoutEntityDataProvider()
    {
        return [
            'start workflow not allowed' => [
                'isStartWorkflowAllowed' => false,
                'type' => '',
                'id' => null,
                'source' => null,
                'expected' => null
            ],
            'other checkout type' => [
                'isStartWorkflowAllowed' => true,
                'type' => 'alternative',
                'id' => null,
                'source' => null,
                'expected' => null
            ],
            'find existing by id' => [
                'isStartWorkflowAllowed' => true,
                'type' => '',
                'id' => 1,
                'source' => null,
                'expected' => new Checkout()
            ],
            'find existing by source' => [
                'isStartWorkflowAllowed' => true,
                'type' => '',
                'id' => null,
                'source' => new CheckoutSource(),
                'expected' => new Checkout()
            ],
            'new instance' => [
                'isStartWorkflowAllowed' => true,
                'type' => '',
                'id' => null,
                'source' => null,
                'expected' => new Checkout()
            ]
        ];
    }
}
