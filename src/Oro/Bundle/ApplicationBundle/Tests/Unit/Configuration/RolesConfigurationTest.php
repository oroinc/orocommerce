<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Configuration;

use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\ApplicationBundle\Configuration\RolesConfiguration;

class RolesConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RolesConfiguration
     */
    protected $configuration;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->configuration = new RolesConfiguration();
    }

    public function testGetConfigTreeBuilder()
    {
        $this->assertInstanceOf(
            'Symfony\Component\Config\Definition\Builder\TreeBuilder',
            $this->configuration->getConfigTreeBuilder()
        );
    }

    public function testProcessValidConfig()
    {
        $role = 'ROLE_TEST';
        $label = 'Test label';
        $description = 'Test description';

        $configs = [
            [
                $role => [
                    'label' => 'Test label',
                    'description' => 'Test description',
                ]
            ]
        ];

        $config = $this->process($configs);

        $this->assertArrayHasKey($role, $config);
        $this->assertArrayHasKey('label', $config[$role]);
        $this->assertArrayHasKey('description', $config[$role]);
        $this->assertArrayNotHasKey('skip_this_data', $config[$role]);
        $this->assertEquals($label, $config[$role]['label']);
        $this->assertEquals($description, $config[$role]['description']);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Configuration contains roles with invalid name "TEST_ROLE_TEST".
     *                           Role name should begin with the prefix 'ROLE_'.
     */
    public function testProcessConfigWithInvalidRoleName()
    {
        $configs = [
            [
                'TEST_ROLE_TEST' => [
                    'label' => 'Test label',
                    'description' => 'Test description',
                ]
            ]
        ];

        $this->process($configs);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Unrecognized option "extra_field" under "roles.ROLE_TEST"
     */
    public function testProcessConfigWithExtraField()
    {
        $configs = [
            [
                'ROLE_TEST' => [
                    'label' => 'Test label',
                    'description' => 'Test description',
                    'extra_field' => 'value',
                ]
            ]
        ];

        $this->process($configs);
    }

    /**
     * Processes an array of configurations and returns a compiled version
     *
     * @param array $configs An array of raw configurations
     *
     * @return array A normalized array
     */
    protected function process($configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration($this->configuration, $configs);
    }
}
