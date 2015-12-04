<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ActionContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ActionBundle\Model\ContextHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ContextHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RequestStack */
    protected $requestStack;

    /** @var ContextHelper */
    protected $helper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new ContextHelper($this->doctrineHelper, $this->requestStack);
    }

    protected function tearDown()
    {
        unset($this->helper, $this->doctrineHelper, $this->requestStack);
    }

    /**
     * @dataProvider getContextDataProvider
     *
     * @param Request|null $request
     * @param array $expected
     */
    public function testGetContext($request, array $expected)
    {
        $this->requestStack->expects($this->exactly(3))
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertEquals($expected, $this->helper->getContext());
    }

    /**
     * @return array
     */
    public function getContextDataProvider()
    {
        return [
            [
                'request' => null,
                'expected' => [
                    'route' => null,
                    'entityId' => null,
                    'entityClass' => null
                ]
            ],
            [
                'request' => new Request(),
                'expected' => [
                    'route' => null,
                    'entityId' => null,
                    'entityClass' => null
                ]
            ],
            [
                'request' => new Request(
                    [
                        'route' => 'test_route',
                        'entityId' => '42',
                        'entityClass' => 'stdClass'
                    ]
                ),
                'expected' => [
                    'route' => 'test_route',
                    'entityId' => '42',
                    'entityClass' => 'stdClass'
                ]
            ]
        ];
    }

    /**
     * @dataProvider getActionContextDataProvider
     *
     * @param Request|null $request
     * @param int $requestStackCalls
     * @param ActionContext $expected
     * @param array $context
     */
    public function testGetActionContext($request, $requestStackCalls, ActionContext $expected, array $context = null)
    {
        $entity = new \stdClass();
        $entity->id = 42;

        $this->requestStack->expects($this->exactly($requestStackCalls))
            ->method('getCurrentRequest')
            ->willReturn($request);

        if ($expected->getEntity()) {
            $this->doctrineHelper->expects($this->once())
                ->method('isManageableEntity')
                ->with('stdClass')
                ->willReturn(true);

            if ($request->get('entityId') || ($expected->getEntity() && isset($expected->getEntity()->id))) {
                $this->doctrineHelper->expects($this->once())
                    ->method('getEntityReference')
                    ->with('stdClass', 42)
                    ->willReturn($entity);
            } else {
                $this->doctrineHelper->expects($this->once())
                    ->method('createEntityInstance')
                    ->with('stdClass')
                    ->willReturn(new \stdClass());
            }
        }

        $this->assertEquals($expected, $this->helper->getActionContext($context));
    }

    /**
     * @return array
     */
    public function getActionContextDataProvider()
    {
        $entity = new \stdClass();
        $entity->id = 42;

        return [
            'without request' => [
                'request' => null,
                'requestStackCalls' => 3,
                'expected' => new ActionContext()
            ],
            'empty request' => [
                'request' => new Request(),
                'requestStackCalls' => 3,
                'expected' => new ActionContext()
            ],
            'route1 without entity id' => [
                'request' => new Request(
                    [
                        'route' => 'test_route',
                        'entityClass' => 'stdClass'
                    ]
                ),
                'requestStackCalls' => 3,
                'expected' => new ActionContext(['data' => new \stdClass()])
            ],
            'entity' => [
                'request' => new Request(),
                'requestStackCalls' => 0,
                'expected' => new ActionContext(['data' => $entity]),
                'context' => [
                    'route' => 'test_route',
                    'entityId' => '42',
                    'entityClass' => 'stdClass'
                ]
            ]
        ];
    }
}
