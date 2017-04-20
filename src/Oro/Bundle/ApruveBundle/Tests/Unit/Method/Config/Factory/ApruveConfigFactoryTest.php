<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Method\Config\Factory;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfig;
use Oro\Bundle\ApruveBundle\Method\Config\Factory\ApruveConfigFactory;
use Oro\Bundle\ApruveBundle\Method\Config\Factory\ApruveConfigFactoryInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Log\LoggerInterface;

class ApruveConfigFactoryTest extends \PHPUnit_Framework_TestCase
{
    const CHANNEL_NAME = 'apruve';
    const LABEL = 'Apruve';
    const SHORT_LABEL = 'Apruve (short)';
    const PAYMENT_METHOD_ID = 'apruve_1';
    const API_KEY = '213a9079914f3b5163c6190f31444528';
    const MERCHANT_ID = '7b97ea0172e18cbd4d3bf21e2b525b2d';
    const API_KEY_DECRYPTED = 'apiKeyDecrypted';
    const MERCHANT_ID_DECRYPTED = 'merchantIdDecrypted';
    const TEST_MODE = false;

    /**
     * @var Channel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $channel;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $crypter;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var ApruveConfigFactoryInterface
     */
    private $factory;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localizationHelper;

    /**
     * @var IntegrationIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $integrationIdentifierGenerator;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->integrationIdentifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);
        $this->channel = $this->createChannelMock();

        $this->factory = new ApruveConfigFactory(
            $this->localizationHelper,
            $this->integrationIdentifierGenerator,
            $this->crypter,
            $this->logger
        );
    }

    public function testCreate()
    {
        $this->crypter
            ->method('decryptData')
            ->willReturnMap([
                [self::API_KEY, self::API_KEY_DECRYPTED],
                [self::MERCHANT_ID, self::MERCHANT_ID_DECRYPTED],
            ]);

        $apruveSettings = $this->createApruveSettingsMock();

        $expectedSettings = new ApruveConfig(
            [
                ApruveConfig::ADMIN_LABEL_KEY => self::CHANNEL_NAME,
                ApruveConfig::LABEL_KEY => self::LABEL,
                ApruveConfig::SHORT_LABEL_KEY => self::SHORT_LABEL,
                ApruveConfig::PAYMENT_METHOD_IDENTIFIER_KEY => self::PAYMENT_METHOD_ID,
                ApruveConfig::API_KEY_KEY => self::API_KEY_DECRYPTED,
                ApruveConfig::MERCHANT_ID_KEY => self::MERCHANT_ID_DECRYPTED,
                ApruveConfig::TEST_MODE_KEY => self::TEST_MODE,
            ]
        );

        $actualSettings = $this->factory->create($apruveSettings);

        static::assertEquals($expectedSettings, $actualSettings);
    }

    public function testCreateWithDecryptionFailure()
    {
        $this->crypter
            ->method('decryptData')
            ->willThrowException(new \Exception());

        $apruveSettings = $this->createApruveSettingsMock();

        $this->logger
            ->method('error');

        $expectedSettings = new ApruveConfig(
            [
                ApruveConfig::ADMIN_LABEL_KEY => self::CHANNEL_NAME,
                ApruveConfig::LABEL_KEY => self::LABEL,
                ApruveConfig::SHORT_LABEL_KEY => self::SHORT_LABEL,
                ApruveConfig::PAYMENT_METHOD_IDENTIFIER_KEY => self::PAYMENT_METHOD_ID,
                ApruveConfig::API_KEY_KEY => '',
                ApruveConfig::MERCHANT_ID_KEY => '',
                ApruveConfig::TEST_MODE_KEY => self::TEST_MODE,
            ]
        );

        $actualSettings = $this->factory->create($apruveSettings);

        static::assertEquals($expectedSettings, $actualSettings);
    }

    /**
     * @return ApruveSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createApruveSettingsMock()
    {
        $labelsCollection = $this->createLabelsCollectionMock();
        $shortLabelsCollection = $this->createLabelsCollectionMock();

        $this->channel
            ->expects(static::once())
            ->method('getName')
            ->willReturn(self::CHANNEL_NAME);

        $this->integrationIdentifierGenerator
            ->expects(static::once())
            ->method('generateIdentifier')
            ->with($this->channel)
            ->willReturn(self::PAYMENT_METHOD_ID);

        $this->localizationHelper
            ->expects(static::at(0))
            ->method('getLocalizedValue')
            ->with($labelsCollection)
            ->willReturn(self::LABEL);

        $this->localizationHelper
            ->expects(static::at(1))
            ->method('getLocalizedValue')
            ->with($shortLabelsCollection)
            ->willReturn(self::SHORT_LABEL);

        $apruveSettings = $this->createMock(ApruveSettings::class);

        $apruveSettings
            ->expects(static::once())
            ->method('getChannel')
            ->willReturn($this->channel);

        $apruveSettings
            ->expects(static::once())
            ->method('getLabels')
            ->willReturn($labelsCollection);

        $apruveSettings
            ->expects(static::once())
            ->method('getShortLabels')
            ->willReturn($shortLabelsCollection);

        $apruveSettings
            ->expects(static::once())
            ->method('getApruveApiKey')
            ->willReturn(self::API_KEY);

        $apruveSettings
            ->expects(static::once())
            ->method('getApruveMerchantId')
            ->willReturn(self::MERCHANT_ID);

        $apruveSettings
            ->expects(static::once())
            ->method('getApruveTestMode')
            ->willReturn(self::TEST_MODE);

        return $apruveSettings;
    }

    /**
     * @return Channel|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createChannelMock()
    {
        return $this->createMock(Channel::class);
    }

    /**
     * @return Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createLabelsCollectionMock()
    {
        return $this->createMock(Collection::class);
    }
}
