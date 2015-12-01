<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Configuration;

use Doctrine\Common\Collections\ArrayCollection;

use Psr\Log\LoggerInterface;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\ActionBundle\Configuration\ActionDefinitionConfigurationValidator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ActionDefinitionConfigurationValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var \Twig_Loader_Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $twigLoader;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var ActionDefinitionConfigurationValidator
     */
    protected $validator;

    public function setUp()
    {
        $this->router = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $this->twigLoader = $this->getMock('Twig_Loader_Filesystem');

        $this->logger = $this->getMock('Symfony\Component\HttpKernel\Log\LoggerInterface');

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->createValidator();
    }

    /**
     * @param bool $debug
     */
    protected function createValidator($debug = false)
    {
        $this->validator = new ActionDefinitionConfigurationValidator(
            $this->router,
            $this->twigLoader,
            $this->doctrineHelper,
            $this->logger,
            $debug
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider validateProvider
     */
    public function testValidate(array $inputData, array $expectedData)
    {
        $this->createValidator($inputData['debug']);

        /* @var $collection RouteCollection|\PHPUnit_Framework_MockObject_MockObject */
        $collection = $this->getMock('Symfony\Component\Routing\RouteCollection');

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($class) {
                return $class;
            });

        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->willReturn(true);

        $this->router->expects($this->any())
            ->method('getRouteCollection')
            ->willReturn($collection);

        $collection->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($inputData['routes']));

        $this->twigLoader->expects($this->any())
            ->method('exists')
            ->will($this->returnValueMap($inputData['templates']));

        $this->logger->expects($expectedData['expectsLog'])
            ->method('warning')
            ->with($expectedData['logMessage']);

        $errors = new ArrayCollection();

        $this->validator->validate($inputData['config'], $errors);

        $this->assertEquals($expectedData['errors'], $errors->toArray());
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Unable to find template "unknown_template"
     */
    public function testValidateWithTemplateException()
    {
        $this->twigLoader->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $config = [
            'exception_action' => [
                'routes' => [],
                'entities' => [],
                'frontend_options' => [
                    'template' => 'unknown_template',
                ],
            ],
        ];

        $this->validator->validate($config);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Unable to find template "unknown_template"
     */
    public function testValidateWithDialogTemplateException()
    {
        $this->twigLoader->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $config = [
            'exception_action' => [
                'routes' => [],
                'entities' => [],
                'frontend_options' => [
                    'dialog_template' => 'unknown_template',
                ],
            ],
        ];

        $this->validator->validate($config);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function validateProvider()
    {
        $routes = [
            ['route1', new \stdClass()],
        ];

        $templates = [
            ['Template1', true],
        ];

        $config = [
            'routes' => [],
            'entities' => [],
            'frontend_options' => [],
        ];

        return [
            'unknown route and unknown entity NO DEBUG' => [
                'input' => [
                    'debug' => false,
                    'routes' => $routes,
                    'templates' => $templates,
                    'config' => [
                        'unknown_route_and_entity_action1' => array_merge($config, [
                            'routes' => ['unknown_route'],
                            'entities' => ['unknown_entity'],
                        ]),
                    ],
                ],
                'expected' => [
                    'expectsLog' => $this->never(),
                    'logMessage' => null,
                    'errors' => [
                        'unknown_route_and_entity_action1.routes.0: Route "unknown_route" not found.',
                        'unknown_route_and_entity_action1.entities.0: Entity "unknown_entity" not found.'
                    ],
                ],
            ],
            'unknown route' => [
                'input' => [
                    'debug' => true,
                    'routes' => $routes,
                    'templates' => $templates,
                    'config' => [
                        'unknown_route_action2' => array_merge($config, [
                            'routes' => ['unknown_route'],
                        ]),
                    ],
                ],
                'expected' => [
                    'expectsLog' => $this->once(),
                    'logMessage' => 'InvalidConfiguration: ' .
                        'unknown_route_action2.routes.0: Route "unknown_route" not found.',
                    'errors' => [
                        'unknown_route_action2.routes.0: Route "unknown_route" not found.',
                    ],
                ],
            ],
            'unknown entity short syntax' => [
                'input' => [
                    'debug' => true,
                    'routes' => $routes,
                    'templates' => $templates,
                    'config' => [
                        'unknown_entity_short_syntax_action' => array_merge($config, [
                            'entities' => ['UnknownBundle:UnknownEntity'],
                        ]),
                    ],
                ],
                'expected' => [
                    'expectsLog' => $this->once(),
                    'logMessage' => 'InvalidConfiguration: ' .
                        'unknown_entity_short_syntax_action.entities.0: ' .
                            'Entity "UnknownBundle:UnknownEntity" not found.',
                    'errors' => [
                        'unknown_entity_short_syntax_action.entities.0: ' .
                            'Entity "UnknownBundle:UnknownEntity" not found.',
                    ],
                ],
            ],
            'unknown entity' => [
                'input' => [
                    'debug' => true,
                    'routes' => $routes,
                    'templates' => $templates,
                    'config' => [
                        'unknown_entity_action' => array_merge($config, [
                            'entities' => ['TestEntity'],
                        ]),
                    ],
                ],
                'expected' => [
                    'expectsLog' => $this->once(),
                    'logMessage' => 'InvalidConfiguration: ' .
                        'unknown_entity_action.entities.0: ' .
                            'Entity "TestEntity" not found.',
                    'errors' => [
                        'unknown_entity_action.entities.0: ' .
                            'Entity "TestEntity" not found.',
                    ],
                ],
            ],
            'valid config' => [
                'input' => [
                    'debug' => true,
                    'routes' => $routes,
                    'templates' => $templates,
                    'config' => [
                        'valid_config_action' => array_merge($config, [
                            'entities' => ['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'],
                            'routes' => ['route1'],
                            'frontend_options' => [
                                'template' => 'Template1',
                                'dialog_template' => 'Template1',
                            ],
                        ]),
                    ],
                ],
                'expected' => [
                    'expectsLog' => $this->never(),
                    'logMessage' => null,
                    'errors' => [],
                ],
            ],
        ];
    }
}
