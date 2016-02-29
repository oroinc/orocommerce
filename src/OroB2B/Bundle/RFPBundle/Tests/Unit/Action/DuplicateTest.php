<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

use OroB2B\Bundle\RFPBundle\Action\Duplicate;
use OroB2B\Bundle\RFPBundle\Factory\DuplicatorFactory;

class DuplicateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var Duplicate
     */
    protected $action;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();
        $this->action = new Duplicate($this->contextAccessor);
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->action->setDispatcher($this->eventDispatcher);
        $this->action->setDuplicatorFactory($this->getDuplicateFactory());
    }

    protected function tearDown()
    {
        unset($this->action);
    }

    public function testInitialize()
    {
        $options = [
            Duplicate::OPTION_KEY_TARGET => 'test_value',
            Duplicate::OPTION_KEY_SETTINGS => [],
            Duplicate::OPTION_KEY_ATTRIBUTE => ['copyResult'],
        ];

        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface',
            $this->action->initialize($options)
        );

        $this->assertAttributeEquals($options, 'options', $this->action);
    }


    /**
     * @dataProvider initializeExceptionDataProvider
     *
     * @param array $options
     * @param string $exception
     * @param string $exceptionMessage
     */
    public function testInitializeException(array $options, $exception, $exceptionMessage)
    {
        $this->setExpectedException($exception, $exceptionMessage);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
    {
        return [
            [
                'options' => [
                    Duplicate::OPTION_KEY_TARGET => ['target'],
                ],
                'expectedException' => 'Oro\Bundle\ActionBundle\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Option \'target\' should be string',
            ],
            [
                'options' => [
                    Duplicate::OPTION_KEY_SETTINGS => 'wrong settings',
                ],
                'expectedException' => 'Oro\Bundle\ActionBundle\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Option \'settings\' should be array',
            ],
            [
                'options' => [],
                'expectedException' => 'Oro\Bundle\ActionBundle\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Attribute name parameter is required',
            ],
        ];
    }

    public function testExecute()
    {
        $child = new \stdClass();
        $child->name = 'child';
        $target = new \stdClass();
        $target->name = 'parent';
        $target->child = $child;

        $data = new ActionData(['data' => $target]);
        $this->action->initialize([Duplicate::OPTION_KEY_ATTRIBUTE => 'copyResult']);
        $this->action->execute($data);
        $copyObject = $data['copyResult'];
        $this->assertNotSame($copyObject, $target);
        $this->assertEquals($copyObject, $target);
        $this->assertSame($copyObject->child, $copyObject->child);
    }

    /**
     * @return DuplicatorFactory
     */
    protected function getDuplicateFactory()
    {
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\TaggedContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $factory = new DuplicatorFactory($container);

        return $factory;
    }
}
