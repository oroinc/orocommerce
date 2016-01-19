<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Action\FindEntities;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\WorkflowBundle\Model\Action\RequestEntity;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class FindEntitiesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestEntity
     */
    protected $function;

    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();

        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->function = new FindEntities($this->contextAccessor, $this->registry);
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->function->setDispatcher($dispatcher);
    }

    protected function tearDown()
    {
        unset($this->contextAccessor, $this->registry, $this->function);
    }

    /**
     * @return PropertyPath
     */
    protected function getPropertyPath()
    {
        return $this->getMockBuilder('Symfony\Component\PropertyAccess\PropertyPath')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param array $options
     * @param string $expectedMessage
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, $expectedMessage)
    {
        $this->setExpectedException(
            '\Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
            $expectedMessage
        );

        $this->function->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
    {
        return [
            'no class name' => [
                'options' => [
                    'some' => 1,
                ],
                'message' => 'Class name parameter is required'
            ],
            'no attribute' => [
                'options' => [
                    'class' => 'stdClass',
                ],
                'message' => 'Attribute name parameter is required'
            ],
            'invalid attribute' => [
                [
                    'class' => 'stdClass',
                    'attribute' => 'string',
                ],
                'message' => 'Attribute must be valid property definition.'
            ],
            'invalid where' => [
                'options' => [
                    'class' => 'stdClass',
                    'attribute' => $this->getPropertyPath(),
                    'where' => 'scalar_data'
                ],
                'message' => 'Parameter "where" must be array'
            ],
            'invalid order_by' => [
                'options' => [
                    'class' => 'stdClass',
                    'attribute' => $this->getPropertyPath(),
                    'order_by' => 'scalar_data'
                ],
                'message' => 'Parameter "order_by" must be array'
            ],
        ];
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\NotManageableEntityException
     * @expectedExceptionMessage Entity class "\stdClass" is not manageable.
     */
    public function testExecuteNotManageableEntity()
    {
        $options = [
            'class' => '\stdClass',
            'attribute' => $this->getPropertyPath(),
            'where' => ['and' => []]
        ];
        $context = new ItemStub([]);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('\stdClass')
            ->will($this->returnValue(null));

        $this->function->initialize($options);
        $this->function->execute($context);
    }

    /**
     * @param array $source
     * @param array $expected
     * @dataProvider initializeDataProvider
     */
    public function testInitialize(array $source, array $expected)
    {
        $this->assertEquals($this->function, $this->function->initialize($source));
        $this->assertAttributeEquals($expected, 'options', $this->function);
    }

    /**
     * @return array
     */
    public function initializeDataProvider()
    {
        return [
            'where and order by' => [
                'source' => [
                    'class' => 'stdClass',
                    'where' => ['name' => 'qwerty'],
                    'order_by' => ['date' => 'asc'],
                    'attribute' => $this->getPropertyPath(),
                    'case_insensitive' => true,
                ],
                'expected' => [
                    'class' => 'stdClass',
                    'where' => ['name' => 'qwerty'],
                    'order_by' => ['date' => 'asc'],
                    'attribute' => $this->getPropertyPath(),
                    'case_insensitive' => true,
                ],
            ]
        ];
    }

    /**
     * @param array $where
     * @param array $orderBy
     * @param array $parameters
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $where, array $orderBy, array $parameters)
    {
        $options = [
            'class' => '\stdClass',
            'where' => $where,
            'attribute' => new PropertyPath('entities'),
            'order_by' => $orderBy,
            'query_parameters' => $parameters,
        ];

        $context = new ItemStub();
        $entity = new \stdClass();

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')->disableOriginalConstructor()
            ->setMethods(['getResult'])->getMockForAbstractClass();
        $query->expects($this->once())->method('getResult')->will($this->returnValue([$entity]));

        $expectedField = 'e.name';
        $expectedParameter = 'name';
        $expectedOrder = 'e.createdDate';

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
        $queryBuilder->expects($this->once())->method('andWhere')
            ->with("$expectedField = :$expectedParameter")->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('setParameters')
            ->with($parameters)->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('orderBy')
            ->with($expectedOrder, $options['order_by']['createdDate'])->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('getQuery')->will($this->returnValue($query));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())->method('createQueryBuilder')
            ->with('e')->will($this->returnValue($queryBuilder));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())->method('getRepository')
            ->with($options['class'])->will($this->returnValue($repository));

        $this->registry->expects($this->once())->method('getManagerForClass')
            ->with($options['class'])->will($this->returnValue($em));

        $this->function->initialize($options);
        $this->function->execute($context);

        $attributeName = (string)$options['attribute'];
        $this->assertEquals([$entity], $context->$attributeName);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'full data' => [
                'where' => ['and' => ['e.name = :name']],
                'orderBy' => ['createdDate' => 'asc'],
                'query_parameters' => ['name' => 'Test Name'],
                'expected' => [],
            ]
        ];
    }
}
