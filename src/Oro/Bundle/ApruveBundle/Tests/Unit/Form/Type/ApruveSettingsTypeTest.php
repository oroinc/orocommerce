<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\ApruveBundle\Form\Type\ApruveSettingsType;
use Oro\Bundle\ApruveBundle\Form\Type\WebhookTokenType;
use Oro\Bundle\ApruveBundle\TokenGenerator\TokenGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;

class ApruveSettingsTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = ApruveSettings::class;

    /**
     * @var TokenGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenGenerator;

    /**
     * @var TransportInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transport;

    /**
     * @var ApruveSettingsType
     */
    private $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->transport->expects(static::any())
            ->method('getSettingsEntityFQCN')
            ->willReturn(static::DATA_CLASS);

        $this->tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $this->tokenGenerator
            ->method('generateToken')
            ->willReturn('webhookTokenSample');

        $this->formType = new ApruveSettingsType($this->transport);

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionTypeStub(),
                    WebhookTokenType::class => new WebhookTokenType($this->tokenGenerator),
                ],
                []
            )
        ];
    }

    public function testConstructor()
    {
        $formType = new ApruveSettingsType($this->transport);

        $reflection = new \ReflectionProperty(ApruveSettingsType::class, 'transport');
        $reflection->setAccessible(true);
        $transport = $reflection->getValue($formType);

        static::assertEquals($this->transport, $transport);
    }

    /**
     * @dataProvider submitProvider
     *
     * @param ApruveSettings $defaultData
     * @param array $submittedData
     * @param bool $isValid
     * @param ApruveSettings $expectedData
     */
    public function testSubmit(
        ApruveSettings $defaultData,
        array $submittedData,
        $isValid,
        ApruveSettings $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, []);

        static::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        static::assertEquals($isValid, $form->isValid());
        static::assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $label = (new LocalizedFallbackValue())->setString('Apruve');
        $shortLabel = (new LocalizedFallbackValue())->setString('Apruve (short)');

        return [
            'empty form' => [
                'defaultData' => new ApruveSettings(),
                'submittedData' => [],
                'isValid' => true,
                'expectedData' => (new ApruveSettings())
                    ->setWebhookToken('webhookTokenSample')
            ],
            'not empty form' => [
                'defaultData' => new ApruveSettings(),
                'submittedData' => [
                    'labels' => [['string' => 'Apruve']],
                    'shortLabels' => [['string' => 'Apruve (short)']],
                    'testMode' => true,
                    'merchantId' => 'merchantIdSample',
                    'apiKey' => 'apiKeySample',
                    'webhookToken' => 'webhookTokenSample',
                ],
                'isValid' => true,
                'expectedData' => (new ApruveSettings())
                    ->addLabel($label)
                    ->addShortLabel($shortLabel)
                    ->setTestMode(true)
                    ->setMerchantId('merchantIdSample')
                    ->setApiKey('apiKeySample')
                    ->setWebhookToken('webhookTokenSample')
            ]
        ];
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with([
                'data_class' => $this->transport->getSettingsEntityFQCN(),
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        static::assertEquals(ApruveSettingsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }
}
