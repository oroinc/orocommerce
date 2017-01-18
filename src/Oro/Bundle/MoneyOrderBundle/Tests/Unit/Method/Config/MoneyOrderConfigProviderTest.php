<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\Config;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigProvider;
use Oro\Bundle\MoneyOrderBundle\Method\Factory\MoneyOrderConfigFactoryInterface;

class MoneyOrderConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @internal */
    const CONFIG_ID1 = 5;

    /** @internal */
    const CONFIG_ID2 = 9;

    /** @var MoneyOrderConfigProvider */
    private $provider;

    /** @var MoneyOrderConfig[] */
    private $configs;

    protected function setUp()
    {
        $this->configs = [
            $this->createConfigWithIdentifier(self::CONFIG_ID1),
            $this->createConfigWithIdentifier(self::CONFIG_ID2),
        ];

        $channelRepository = $this->createMock(ChannelRepository::class);
        $channelRepository->expects(static::any())
            ->method('findBy')
            ->willReturn([
                new Channel(),
                new Channel(),
            ]);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(static::any())
            ->method('getEntityRepository')
            ->willReturn($channelRepository);

        $configFactory = $this->createMock(MoneyOrderConfigFactoryInterface::class);
        $configFactory->expects(static::at(0))
            ->method('create')
            ->willReturn($this->configs[0]);

        $configFactory->expects(static::at(1))
            ->method('create')
            ->willReturn($this->configs[1]);

        $this->provider = new MoneyOrderConfigProvider($doctrineHelper, $configFactory);
    }

    public function testGetPaymentConfigsReturnsCorrectObjects()
    {
        $expected = [
            self::CONFIG_ID1 => $this->configs[0],
            self::CONFIG_ID2 => $this->configs[1],
        ];

        static::assertEquals($expected, $this->provider->getPaymentConfigs());
    }

    public function testHasPaymentConfigForRightIdentifier()
    {
        static::assertTrue($this->provider->hasPaymentConfig(self::CONFIG_ID1));
    }

    public function testHasPaymentConfigForWrongIdentifier()
    {
        static::assertFalse($this->provider->hasPaymentConfig('wrong'));
    }

    public function testGetPaymentConfigReturnsCorrectObject()
    {
        static::assertEquals(
            $this->configs[1],
            $this->provider->getPaymentConfig(self::CONFIG_ID2)
        );
    }

    public function testGetPaymentConfigForWrongIdentifier()
    {
        static::assertNull($this->provider->getPaymentConfig('wrong'));
    }

    /**
     * @param int $identifier
     *
     * @return MoneyOrderConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createConfigWithIdentifier($identifier)
    {
        $config = $this->createMock(MoneyOrderConfig::class);
        $config->expects(static::any())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($identifier);

        return $config;
    }
}
