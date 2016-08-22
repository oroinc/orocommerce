<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FrontendBundle\Form\Type\Configuration\WebType;

class WebTypeTest extends FormIntegrationTestCase
{
    /** @var WebType */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->formType = new WebType();
    }

    /**
     * @param bool $isValid
     * @param mixed $defaultData
     * @param array $submittedData
     * @param mixed $expectedData
     * @param array $options
     * @dataProvider submitProvider
     */
    public function testSubmit($isValid, $defaultData, $submittedData, $expectedData, array $options = [])
    {
        $form = $this->factory->create($this->formType, $defaultData, $options);
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
            'web_valid_backend_prefix' => [
                'isValid'       => true,
                'defaultData'   => ['oro_installer_web_backend_prefix' => '/admin'],
                'submittedData' => [
                    'oro_installer_web_backend_prefix' => '/new_backend'
                ],
                'expectedData'  => ['oro_installer_web_backend_prefix' => '/new_backend']
            ],
            'web_blank_backend_prefix' => [
                'isValid'       => false,
                'defaultData'   => ['oro_installer_web_backend_prefix' => '/admin'],
                'submittedData' => [
                    'oro_installer_web_backend_prefix' => ''
                ],
                'expectedData'  => ['oro_installer_web_backend_prefix' => ''],
            ],
            'web_setting_invalid_backend_prefix' => [
                'isValid'       => false,
                'defaultData'   => ['oro_installer_web_backend_prefix' => '/admin'],
                'submittedData' => [
                    'oro_installer_web_backend_prefix' => 'admin'
                ],
                'expectedData'  => ['oro_installer_web_backend_prefix' => 'admin'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(WebType::NAME, $this->formType->getName());
    }
}
