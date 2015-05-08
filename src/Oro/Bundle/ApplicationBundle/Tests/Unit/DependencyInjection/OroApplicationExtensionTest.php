<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ApplicationBundle\DependencyInjection\OroApplicationExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroApplicationExtensionTest extends ExtensionTestCase
{
    /**
     * @var string
     */
    protected $application;

    /**
     * @dataProvider kernelApplications
     *
     * @param string $application
     * @param string $parameter
     * @param array $parameterData
     */
    public function testLoad($application, $parameter, array $parameterData)
    {
        $this->application = $application;

        $this->loadExtension(new OroApplicationExtension());

        $expectedParameters = [
            'oro_application.twig.application_url_extension.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        if ($parameter) {
            $this->assertParametersLoaded([$parameter]);

            $this->assertEquals($parameterData, $this->actualParameters[$parameter]);
        }

        $expectedDefinitions = [
            'oro_application.twig.application_url_extension',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    /**
     * @return array
     */
    public function kernelApplications()
    {
        return [
            [
                'application' => OroApplicationExtension::APPLICATION_ADMIN,
                'parameter' => OroApplicationExtension::ROLES_CONTAINER_PARAM_ADMIN,
                'parameterData' => [
                    'ROLE_TEST' => [
                        'label' => 'test',
                        'description' => 'test description',
                    ],
                    'ROLE_TEST2' => [
                        'label' => 'test2',
                        'description' => 'test2 description',
                    ],
                ],
            ],
            [
                'application' => OroApplicationExtension::APPLICATION_FRONTEND,
                'parameter' => OroApplicationExtension::ROLES_CONTAINER_PARAM_FRONTEND,
                'parameterData' => [
                    'ROLE_TEST' => [],
                    'ROLE_TEST2' => [],
                ],
            ],
            [
                'application' => 'tracking',
                'parameter' => null,
                'parameterData' => [],
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function buildContainerMock()
    {
        return $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(['setDefinition', 'setParameter', 'getParameter'])
            ->getMock();
    }

    /**
     * {@inheritDoc}
     */
    protected function getContainerMock()
    {
        $container = parent::getContainerMock();
        $container->expects($this->any())
            ->method('getParameter')
            ->will(
                $this->returnCallback(
                    function ($id) {
                        switch ($id) {
                            case 'kernel.root_dir':
                                return __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'src';
                                break;
                            case 'kernel.application':
                                return $this->application;
                                break;
                            case OroApplicationExtension::ROLES_CONTAINER_PARAM_FRONTEND:
                                return [];
                                break;
                            default:
                                return null;
                                break;
                        }
                    }
                )
            );

        return $container;
    }
}
