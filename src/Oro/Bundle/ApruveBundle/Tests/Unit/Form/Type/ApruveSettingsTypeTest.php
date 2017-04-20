<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\ApruveBundle\Form\Type\ApruveSettingsType;
use Oro\Bundle\ApruveBundle\Form\Type\WebhookTokenType;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\SecurityBundle\Form\DataTransformer\Factory\CryptedDataTransformerFactoryInterface;
use Oro\Bundle\SecurityBundle\Generator\RandomTokenGeneratorInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class ApruveSettingsTypeTest extends FormIntegrationTestCase
{
    const ENCRYPTED_API_KEY = 'encryptedApiKeySample';
    const DECRYPTED_API_KEY = 'apiKeySample';

    const ENCRYPTED_MERCHANT_ID = 'encryptedMerchantIdSample';
    const DECRYPTED_MERCHANT_ID = 'merchantIdSample';

    const LABEL = 'Apruve';
    const SHORT_LABEL = 'Apruve (short)';
    const TEST_MODE = true;
    const WEBHOOK_TOKEN = 'webhookTokenSample';

    const DATA_CLASS = ApruveSettings::class;

    /**
     * @var DataTransformerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataTransformer;

    /**
     * @var CryptedDataTransformerFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cryptedDataTransformerFactory;

    /**
     * @var RandomTokenGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
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
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->dataTransformer = $this->createMock(DataTransformerInterface::class);
        $this->cryptedDataTransformerFactory = $this->createMock(CryptedDataTransformerFactoryInterface::class);
        $this->cryptedDataTransformerFactory
            ->method('create')
            ->willReturn($this->dataTransformer);

        $this->transport = $this->createMock(TransportInterface::class);
        $this->transport->expects(static::any())
            ->method('getSettingsEntityFQCN')
            ->willReturn(static::DATA_CLASS);

        $this->tokenGenerator = $this->createMock(RandomTokenGeneratorInterface::class);
        $this->tokenGenerator
            ->method('generateToken')
            ->willReturn('webhookTokenSample');

        $this->formType = new ApruveSettingsType($this->transport, $this->cryptedDataTransformerFactory);

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
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitDataProvider
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
        $this->dataTransformer
            ->method('reverseTransform')
            ->willReturnMap([
                [null, null],
                [self::DECRYPTED_MERCHANT_ID, self::ENCRYPTED_MERCHANT_ID],
                [self::DECRYPTED_API_KEY, self::ENCRYPTED_API_KEY],
            ]);

        $form = $this->factory->create($this->formType, $defaultData, []);

        static::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        static::assertEquals($isValid, $form->isValid());
        static::assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $label = (new LocalizedFallbackValue())->setString(self::LABEL);
        $shortLabel = (new LocalizedFallbackValue())->setString(self::SHORT_LABEL);

        return [
            'empty form' => [
                'defaultData' => new ApruveSettings(),
                'submittedData' => [],
                'isValid' => true,
                'expectedData' => (new ApruveSettings())
                    ->setApruveWebhookToken(self::WEBHOOK_TOKEN)
            ],
            'not empty form' => [
                'defaultData' => new ApruveSettings(),
                'submittedData' => [
                    'labels' => [['string' => self::LABEL]],
                    'shortLabels' => [['string' => self::SHORT_LABEL]],
                    'apruveTestMode' => self::TEST_MODE,
                    'apruveMerchantId' => self::DECRYPTED_MERCHANT_ID,
                    'apruveApiKey' => self::DECRYPTED_API_KEY,
                    'apruveWebhookToken' => self::WEBHOOK_TOKEN,
                ],
                'isValid' => true,
                'expectedData' => (new ApruveSettings())
                    ->addLabel($label)
                    ->addShortLabel($shortLabel)
                    ->setApruveTestMode(self::TEST_MODE)
                    ->setApruveMerchantId(self::ENCRYPTED_MERCHANT_ID)
                    ->setApruveApiKey(self::ENCRYPTED_API_KEY)
                    ->setApruveWebhookToken(self::WEBHOOK_TOKEN)
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
