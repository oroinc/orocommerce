<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\InstallerBundle\Form\Type\ConfigurationType;
use Oro\Bundle\FrontendBundle\Form\Extension\ConfigurationTypeExtension;
use Oro\Bundle\FrontendBundle\Form\Type\Configuration\WebType;
use Oro\Bundle\FrontendBundle\Tests\Unit\Form\Extension\Stub\ConfigurationTypeStub;

class ConfigurationTypeExtensionTest extends FormIntegrationTestCase
{
    /** @var ConfigurationTypeExtension */
    protected $configurationTypeExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {

        $this->configurationTypeExtension = new ConfigurationTypeExtension();
        parent::setUp();
    }

    /**
     * @param bool  $isValid
     * @param mixed $defaultData
     * @param array $submittedData
     * @param mixed $expectedData
     * @param array $options
     * @dataProvider submitProvider
     */
    public function testSubmit($isValid, $defaultData, $submittedData, $expectedData, array $options = [])
    {
        $form = $this->factory->create(ConfigurationType::NAME, $defaultData, $options);
        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'configuration_type_valid' => [
                'isValid'       => true,
                'defaultData'   => [
                    'web' => ['oro_installer_web_backend_prefix' => null]
                ],
                'submittedData' => [
                    'web' => ['oro_installer_web_backend_prefix' => '/admin']
                ],
                'expectedData'  => [
                    'web' => ['oro_installer_web_backend_prefix' => '/admin']
                ]
            ],
            'configuration_type_invalid_extra_field' => [
                'isValid'       => false,
                'defaultData'   => [
                    'web' => ['oro_installer_web_backend_prefix' => null]
                ],
                'submittedData' => ['extra_field' => null],
                'expectedData'  => [
                    'web' => ['oro_installer_web_backend_prefix' => null]
                ]
            ],
            'configuration_type_invalid_web_type_invalid' => [
                'isValid'       => false,
                'defaultData'   => [
                    'web' => ['oro_installer_web_backend_prefix' => null]
                ],
                'submittedData' => [
                    'web' => ['oro_installer_web_backend_prefix' => null]
                ],
                'expectedData'  => [
                    'web' => ['oro_installer_web_backend_prefix' => null]
                ]
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    WebType::NAME => new WebType(),
                    ConfigurationType::NAME => new ConfigurationTypeStub(),
                ],
                [
                    ConfigurationType::NAME => [$this->configurationTypeExtension],
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * Test getExtendedType
     */
    public function testGetExtendedType()
    {
        $this->assertEquals(ConfigurationType::NAME, $this->configurationTypeExtension->getExtendedType());
    }
}
