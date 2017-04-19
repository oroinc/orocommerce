<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\ApruveBundle\Form\Type\WebhookTokenType;
use Oro\Bundle\SecurityBundle\Generator\RandomTokenGeneratorInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebhookTokenTypeTest extends FormIntegrationTestCase
{
    /**
     * @var RandomTokenGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenGenerator;

    /**
     * @var WebhookTokenType
     */
    private $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->tokenGenerator = $this->createMock(RandomTokenGeneratorInterface::class);
        $this->tokenGenerator
            ->method('generateToken')
            ->with(256)
            ->willReturn('webhookTokenSample');

        $this->formType = new WebhookTokenType($this->tokenGenerator);

        parent::setUp();
    }

    public function testConstructor()
    {
        $formType = new WebhookTokenType($this->tokenGenerator);

        $reflection = new \ReflectionProperty(WebhookTokenType::class, 'generator');
        $reflection->setAccessible(true);
        $generator = $reflection->getValue($formType);

        static::assertEquals($this->tokenGenerator, $generator);
    }

    /**
     * @dataProvider submitProvider
     *
     * @param ApruveSettings $defaultData
     * @param array $submittedData
     * @param ApruveSettings $expectedData
     */
    public function testSubmit($defaultData, $submittedData, $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $actualData = $form->getData();
        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'submit with empty data' => [
                'defaultData' => null,
                'submittedData' => '',
                'expectedData' => 'webhookTokenSample',
            ],
            'submit with existing data' => [
                'defaultData' => 'existingWebhookTokenSample',
                'submittedData' => 'existingWebhookTokenSample',
                'expectedData' => 'existingWebhookTokenSample',
            ],
        ];
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $defaultOptions = $resolver->getDefinedOptions();
        $this->assertContains('empty_data', $defaultOptions);
    }

    public function testGetBlockPrefix()
    {
        static::assertEquals(WebhookTokenType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        static::assertEquals(HiddenType::class, $this->formType->getParent());
    }
}
