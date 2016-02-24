<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Layout\Block\Type;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\RestrictHelper;
use Oro\Bundle\ActionBundle\Layout\Block\Type\AbstractButtonsType;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Component\Layout\Action;
use Oro\Component\Layout\BlockBuilderInterface;

abstract class AbstractActionButtonsTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractButtonsType
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
        $typeClassName = $this->getTypeClassName();


        $this->blockType = new $typeClassName(
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
     * @param string $className
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockWithoutConstructor($className)
    {
        return $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
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
        $resolver->expects($this->at(0))->method('setDefaults')->with(
            [
                'actions' => $actions,
                'context' => $context,
                'actionData' => $actionData,
                'dialogRoute' => $dialogRoute,
                'executionRoute' => $executionRoute,
                'fromUrl' => $formUrl
            ]
        );
        $resolver->expects($this->at(1))->method('setOptional')->with(['group']);
        $resolver->expects($this->at(2))->method('setRequired')->with(['entity']);
        $this->setResolverExpectations($resolver);
        $this->blockType->setDefaultOptions($resolver);
    }

    /**
     * @param $manipulator
     * @param $builderId
     * @return BlockBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getBlockBuilder($manipulator, $builderId)
    {
        /** @var BlockBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $blockBuilder */
        $blockBuilder = $this->getMock('Oro\Component\Layout\BlockBuilderInterface');
        $blockBuilder->expects($this->once())->method('getLayoutManipulator')->willReturn($manipulator);
        $blockBuilder->expects($this->once())->method('getId')->willReturn($builderId);

        return $blockBuilder;
    }

    /**
     * @param $groupValue
     * @param $executionRoute
     * @param $dialogRoute
     * @param $actionHasForm
     * @param $actionName
     * @return array
     */
    protected function getOptions(
        $groupValue,
        $executionRoute,
        $dialogRoute,
        $actionHasForm,
        $actionName
    ) {
        /** @var Action|\PHPUnit_Framework_MockObject_MockObject $action */
        $action = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Action')
            ->disableOriginalConstructor()
            ->getMock();
        $action->expects($this->once())->method('hasForm')->willReturn($actionHasForm);
        $action->expects($this->once())->method('getDefinition')->willReturn(new ActionDefinition());
        $action->expects($this->once())->method('getName')->willReturn($actionName);
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

            return $options;
        }

        return $options;
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

    /**
     * @return string
     */
    abstract protected function getTypeClassName();

    /**
     * @param OptionsResolverInterface|\PHPUnit_Framework_MockObject_MockObject $resolver
     */
    abstract protected function setResolverExpectations($resolver);
}
