<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Layout\Block\Type;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\RestrictHelper;
use Oro\Bundle\ActionBundle\Layout\Block\Type\ActionButtonType;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Layout\Block\Type\ActionLineButtonsType;
use Oro\Bundle\ActionBundle\Model\ActionManager;

class ActionLineButtonsTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActionLineButtonsType
     */
    protected $blockType;

    /**
     * @var ActionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $actionManager;

    /**
     * @var UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var ContextHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextHelper;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var ApplicationsHelper|\PHPUnit_Framework_MockObject_MockObject $actionApplicationsHelper
     */
    protected $actionApplicationsHelper;

    /** @var  RestrictHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $restrictHelper;

    public function setUp()
    {
        $this->actionManager = $this->getMockWithoutConstructor('Oro\Bundle\ActionBundle\Model\ActionManager');
        $this->contextHelper = $this->getMockWithoutConstructor('Oro\Bundle\ActionBundle\Helper\ContextHelper');
        $this->actionApplicationsHelper = $this
            ->getMockWithoutConstructor('Oro\Bundle\ActionBundle\Helper\ApplicationsHelper');
        $this->requestStack = $this->getMockWithoutConstructor('Symfony\Component\HttpFoundation\RequestStack');
        $this->router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $this->restrictHelper = $this->getMock('Oro\Bundle\ActionBundle\Helper\RestrictHelper');

        $this->blockType = new ActionLineButtonsType(
            $this->actionManager,
            $this->contextHelper,
            $this->actionApplicationsHelper,
            $this->requestStack,
            $this->router,
            $this->restrictHelper
        );
        parent::setUp();
    }

    /**
     * @dataProvider buildBlockDataProvider
     * @param string|null $groupValue
     * @param string $executionRoute
     * @param string $dialogRoute
     * @param boolean $actionHasForm
     * @param array $expectedOptions
     */
    public function testBuildBlock($groupValue, $executionRoute, $dialogRoute, $actionHasForm, array $expectedOptions)
    {
        /** @var BlockBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $blockBuilder */
        $blockBuilder = $this->getMock('Oro\Component\Layout\BlockBuilderInterface');
        /** @var Action|\PHPUnit_Framework_MockObject_MockObject $action */
        $action = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Action')
            ->disableOriginalConstructor()
            ->getMock();
        $action->expects($this->once())->method('hasForm')->willReturn($actionHasForm);
        $action->expects($this->once())->method('getDefinition')->willReturn(new ActionDefinition());
        $actionName = 'action1';
        $path = 'route_url';
        $entity = new \stdClass();
        $actions[$actionName] = $action;
        $options['actions'] = $actions;
        $options['entity'] = $entity;
        $options['executionRoute'] = $executionRoute;
        $options['dialogRoute'] = $dialogRoute;
        $options['context'] = [];
        $options['fromUrl'] = 'fromUrl';
        $options['actionData'] = 'actual_data';
        $expectedContext = array_merge($options['context'], ['entity' => $entity]);
        $this->contextHelper
            ->expects($this->once())
            ->method('getActionParameters')
            ->with($expectedContext)
            ->willReturn($expectedContext);
        if ($groupValue) {
            $options['group'] = $groupValue;
            $this->restrictHelper
                ->expects($this->once())
                ->method('restrictActionsByGroup')
                ->with($actions, $groupValue)
                ->willReturn($actions);
        } else {
            $this->actionManager
                ->expects($this->never())
                ->method('restrictActionsByGroup');
        }
        $this->router
            ->expects($this->at(0))
            ->method('generate')
            ->with(
                $options['executionRoute'],
                array_merge($options['context'], ['actionName' => $actionName, 'entity' => $entity])
            )
            ->willReturn($path);
        if ($actionHasForm) {
            $this->router
                ->expects($this->at(1))
                ->method('generate')
                ->with(
                    $options['dialogRoute'],
                    array_merge(
                        $options['context'],
                        ['actionName' => $actionName, 'fromUrl' => $options['fromUrl'], 'entity' => $entity]
                    )
                )
                ->willReturn($path);
        }
        $builderId = 'builder';
        /** @var LayoutManipulatorInterface|\PHPUnit_Framework_MockObject_MockObject $manipulator */
        $manipulator = $this->getMock('Oro\Component\Layout\LayoutManipulatorInterface');
        $manipulator->expects($this->once())->method('add')->with(
            $actionName . '_button',
            $builderId,
            ActionButtonType::NAME,
            $expectedOptions
        );
        $blockBuilder->expects($this->once())->method('getLayoutManipulator')->willReturn($manipulator);
        $blockBuilder->expects($this->once())->method('getId')->willReturn($builderId);

        $this->blockType->buildBlock($blockBuilder, $options);
    }

    /**
     * @return array
     */
    public function buildBlockDataProvider()
    {
        return [
            [
                'groupValue' => 'group1',
                'executionRoute' => 'executionRoute',
                'dialogRoute' => 'dialogRoute',
                'actionHasForm' => true,
                'expectedOptions' => [
                    'params' => [
                        'label' => null,
                        'path' => 'route_url',
                        'actionUrl' => 'route_url',
                        'buttonOptions' => [],
                        'frontendOptions' => []
                    ],
                    'context' => ['entity' => new \stdClass()],
                    'fromUrl' => 'fromUrl',
                    'actionData' => 'actual_data'
                ]
            ],
            [
                'groupValue' => null,
                'executionRoute' => null,
                'dialogRoute' => null,
                'actionHasForm' => false,
                'expectedOptions' => [
                    'params' => [
                        'label' => null,
                        'path' => 'route_url',
                        'actionUrl' => null,
                        'buttonOptions' => [],
                        'frontendOptions' => []
                    ],
                    'context' => ['entity' => new \stdClass()],
                    'fromUrl' => 'fromUrl',
                    'actionData' => 'actual_data'
                ]
            ]
        ];
    }

    public function testSetDefaultOptions()
    {
        $actions = ['actions'];
        $context = [''];
        $actionData = new ActionData();
        $dialogRoute = 'dialog_route';
        $executionRoute = 'executionRoute';
        $request = new Request();
        $formUrl = 'fromUrl';
        $request->attributes->set('fromUrl', $formUrl);

        $this->actionManager->expects($this->once())->method('getActions')->willReturn($actions);
        $this->contextHelper->expects($this->once())->method('getContext')->willReturn($context);
        $this->contextHelper->expects($this->once())->method('getActionData')->willReturn($actionData);
        $this->actionApplicationsHelper->expects($this->once())->method('getDialogRoute')->willReturn($dialogRoute);
        $this->actionApplicationsHelper->expects($this->once())
            ->method('getExecutionRoute')
            ->willReturn($executionRoute);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var OptionsResolverInterface|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())->method('setDefaults')->with(
            [
                'actions' => $actions,
                'context' => $context,
                'actionData' => $actionData,
                'dialogRoute' => $dialogRoute,
                'executionRoute' => $executionRoute,
                'fromUrl' => $formUrl
            ]
        );
        $resolver->expects($this->once())->method('setOptional')->with(['group', 'ul_class']);
        $resolver->expects($this->once())->method('setRequired')->with(['entity']);

        $this->blockType->setDefaultOptions($resolver);
    }

    /**
     * @param string $className
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockWithoutConstructor($className)
    {
        return $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
