<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Functional\DependencyInjection\Loader;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

use OroB2B\Bundle\FrontendBundle\DependencyInjection\Loader\PrivateYamlFileLoader;

class PrivateYamlFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $fileName
     * @param array $expectedServices
     * @dataProvider loadDataProvider
     */
    public function testLoad($fileName, array $expectedServices)
    {
        $actualServices = [];

        /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder $container */
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->expects($this->any())
            ->method('setDefinition')
            ->willReturnCallback(
                function ($id, Definition $definition) use (&$actualServices) {
                    $actualServices[$id] = $definition->isPublic();
                }
            );

        $loader = new PrivateYamlFileLoader($container, new FileLocator(__DIR__ . '/services'));
        $loader->load($fileName);

        $this->assertEquals($expectedServices, $actualServices);
    }

    /**
     * @return array
     */
    public function loadDataProvider()
    {
        return [
            'no services' => [
                'fileName' => 'no_services.yml',
                'expectedServices' => [],
            ],
            'default publicity' => [
                'fileName' => 'default_publicity.yml',
                'expectedServices' => [
                    'default_publicity_service_1' => false,
                    'default_publicity_service_2' => false,
                ],
            ],
            'custom publicity' => [
                'fileName' => 'custom_publicity.yml',
                'expectedServices' => [
                    'public_service' => true,
                    'private_service' => false,
                ],
            ],
        ];
    }
}
