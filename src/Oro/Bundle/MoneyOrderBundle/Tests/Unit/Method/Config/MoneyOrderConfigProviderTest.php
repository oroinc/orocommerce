<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\Config;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigProvider;

class MoneyOrderConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @internal */
    const CHANNEL_ID1 = 1;

    /** @internal */
    const CHANNEL_ID2 = 2;

    /** @var MoneyOrderConfigProvider */
    private $provider;

    /** @var Channel[] */
    private $channels;

    protected function setUp()
    {
        $this->channels = [
            $this->createChannelWithId(self::CHANNEL_ID1),
            $this->createChannelWithId(self::CHANNEL_ID2),
        ];

        $channelRepository = $this->createMock(ChannelRepository::class);
        $channelRepository->expects(static::any())
            ->method('findBy')
            ->willReturn($this->channels);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(static::any())
            ->method('getEntityRepository')
            ->willReturn($channelRepository);

        $this->provider = new MoneyOrderConfigProvider($doctrineHelper);
    }

    public function testGetPaymentConfigsReturnsCorrectObjects()
    {
        $config1 = new MoneyOrderConfig($this->channels[0]);
        $config2 = new MoneyOrderConfig($this->channels[1]);

        $expected = [
            $config1->getPaymentMethodIdentifier() => $config1,
            $config2->getPaymentMethodIdentifier() => $config2,
        ];

        static::assertEquals($expected, $this->provider->getPaymentConfigs());
    }

    public function testHasPaymentConfigForRightIdentifier()
    {
        $config1 = new MoneyOrderConfig($this->channels[0]);

        static::assertTrue($this->provider->hasPaymentConfig($config1->getPaymentMethodIdentifier()));
    }

    public function testHasPaymentConfigForWrongIdentifier()
    {
        static::assertFalse($this->provider->hasPaymentConfig('wrong'));
    }

    public function testGetPaymentConfigReturnsCorrectObject()
    {
        $config1 = new MoneyOrderConfig($this->channels[0]);

        static::assertEquals(
            $config1,
            $this->provider->getPaymentConfig($config1->getPaymentMethodIdentifier())
        );
    }

    public function testGetPaymentConfigForWrongIdentifier()
    {
        static::assertNull($this->provider->getPaymentConfig('wrong'));
    }

    /**
     * @param int $id
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Channel
     */
    private function createChannelWithId($id)
    {
        $channel = $this->createMock(Channel::class);
        $channel->expects(static::any())
            ->method('getId')
            ->willReturn($id);

        return $channel;
    }
}
