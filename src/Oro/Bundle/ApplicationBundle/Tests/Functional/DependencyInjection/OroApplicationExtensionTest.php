<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Functional\DependencyInjection;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Finder\Finder;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ApplicationBundle\DependencyInjection\OroApplicationExtension;

class OroApplicationExtensionTest extends WebTestCase
{
    /**
     * @var array
     */
    protected $actualDefinitions = [];

    /**
     * @var array
     */
    protected $actualParameters = [];

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    /**
     * @dataProvider kernelApplications
     * @param string $application
     * @param string $parameter
     * @param array $parameterData
     */
    public function testLoad($application, $parameter, array $parameterData)
    {
        $this->loadExtension(new OroApplicationExtensionStub(), $application);

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
                'application' => 'admin',
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
                'application' => 'frontend',
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
     * @param Extension $extension
     * @param string $application
     * @return $this
     */
    protected function loadExtension(Extension $extension, $application)
    {
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(array('setDefinition', 'setParameter', 'getParameter'))
            ->getMock();
        $container->expects($this->any())
            ->method('setDefinition')
            ->will(
                $this->returnCallback(
                    function ($id, Definition $definition) {
                        $this->actualDefinitions[$id] = $definition;
                    }
                )
            );
        $container->expects($this->any())
            ->method('setParameter')
            ->will(
                $this->returnCallback(
                    function ($name, $value) {
                        $this->actualParameters[$name] = $value;
                    }
                )
            );
        $container->expects($this->any())
            ->method('getParameter')
            ->will(
                $this->returnCallback(
                    function ($id) use ($application) {
                        switch ($id) {
                            case 'kernel.root_dir':
                                return __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures';
                                break;
                            case 'kernel.application':
                                return $application;
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

        $extension->load([], $container);

        return $this;
    }

    /**
     * @param array $expectedDefinitions
     */
    protected function assertDefinitionsLoaded(array $expectedDefinitions)
    {
        foreach ($expectedDefinitions as $serviceId) {
            $this->assertArrayHasKey(
                $serviceId,
                $this->actualDefinitions,
                sprintf('Definition for "%s" service has not been loaded.', $serviceId)
            );
            $this->assertNotEmpty(
                $this->actualDefinitions[$serviceId],
                sprintf('Definition for "%s" service is empty.', $serviceId)
            );
        }
    }

    /**
     * @param array $expectedParameters
     */
    protected function assertParametersLoaded(array $expectedParameters)
    {
        foreach ($expectedParameters as $parameterName) {
            $this->assertArrayHasKey(
                $parameterName,
                $this->actualParameters,
                sprintf('Parameter "%s" has not been loaded.', $parameterName)
            );
            $this->assertNotEmpty(
                $this->actualParameters[$parameterName],
                sprintf('Parameter "%s" is empty.', $parameterName)
            );
        }
    }
}
