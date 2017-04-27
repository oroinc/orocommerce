<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Client\Factory\Settings\Basic;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClientInterface;
use Oro\Bundle\ApruveBundle\Client\Factory\ApruveRestClientFactoryInterface;
use Oro\Bundle\ApruveBundle\Client\Factory\Settings\Basic\ApruveSettingsRestClientFactory;
use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class ApruveSettingsRestClientFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruveRestClientFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $restClientFactory;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $crypter;

    /**
     * @var ApruveSettingsRestClientFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->restClientFactory = $this->createMock(ApruveRestClientFactoryInterface::class);

        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);

        $this->factory = new ApruveSettingsRestClientFactory($this->restClientFactory, $this->crypter);
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param bool $isTestMode
     */
    public function testCreate($isTestMode)
    {
        $apruveSettings = $this->getApruveSettingsMock();

        $encryptedKey = 'encrypted_api_key';

        $apruveSettings->expects(static::once())
            ->method('getApruveApiKey')
            ->willReturn($encryptedKey);

        $apruveSettings->expects(static::once())
            ->method('getApruveTestMode')
            ->willReturn($isTestMode);

        $apiKey = 'qwerty12345';

        $this->crypter->expects(static::once())
            ->method('decryptData')
            ->with($encryptedKey)
            ->willReturn($apiKey);

        $expectedClient = $this->createMock(ApruveRestClientInterface::class);

        $this->restClientFactory->expects(static::once())
            ->method('create')
            ->with($apiKey, $isTestMode)
            ->willReturn($expectedClient);

        static::assertEquals($expectedClient, $this->factory->create($apruveSettings));
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'test mode' => [
                'isTestMode' => true,
            ],
            'prod mode' => [
                'isTestMode' => false,
            ],
        ];
    }

    /**
     * @return ApruveSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getApruveSettingsMock()
    {
        return $this->createMock(ApruveSettings::class);
    }
}
