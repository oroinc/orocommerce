<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\Method\Config\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AuthorizeNetBundle\Entity\AuthorizeNetSettings;
use Oro\Bundle\AuthorizeNetBundle\Entity\Repository\AuthorizeNetSettingsRepository;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfig;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\Factory\AuthorizeNetConfigFactory;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\Provider\AuthorizeNetConfigProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class AuthorizeNetConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var AuthorizeNetSettings[]
     */
    protected $settings;

    /**
     * @var AuthorizeNetConfigProvider
     */
    protected $authorizeNetConfigProvider;

    protected function setUp()
    {
        $this->type = 'authorize_net';

        $channel1 = $this->getEntity(Channel::class, ['id' => 1, 'type' => $this->type]);
        $channel2 = $this->getEntity(Channel::class, ['id' => 2, 'type' => $this->type]);

        $this->settings[] = $this->getEntity(AuthorizeNetSettings::class, ['id' => 1, 'channel' => $channel1]);
        $this->settings[] = $this->getEntity(AuthorizeNetSettings::class, ['id' => 2, 'channel' => $channel2]);

        $config = $this->createMock(AuthorizeNetConfig::class);
        $config->expects($this->at(0))
            ->method('getPaymentMethodIdentifier')
            ->willReturn('authorize_net_1');
        $config->expects($this->at(1))
            ->method('getPaymentMethodIdentifier')
            ->willReturn('authorize_net_2');

        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $objectRepository = $this->createMock(AuthorizeNetSettingsRepository::class);
        $objectRepository->expects($this->once())
            ->method('getEnabledSettingsByType')
            ->with($this->type)
            ->willReturn($this->settings);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->once())->method('getRepository')->willReturn($objectRepository);

        $this->doctrine->expects($this->once())->method('getManagerForClass')->willReturn($objectManager);

        /** @var AuthorizeNetConfigFactory|\PHPUnit_Framework_MockObject_MockObject $factory */
        $factory = $this->createMock(AuthorizeNetConfigFactory::class);
        $factory->expects($this->exactly(2))
            ->method('createConfig')
            ->willReturn($config);

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $this->authorizeNetConfigProvider = new AuthorizeNetConfigProvider(
            $this->doctrine,
            $logger,
            $factory,
            $this->type
        );
    }

    public function testGetPaymentConfigs()
    {
        $this->assertCount(2, $this->authorizeNetConfigProvider->getPaymentConfigs());
    }

    public function testGetPaymentConfig()
    {
        $identifier = 'authorize_net_1';

        $this->assertInstanceOf(
            AuthorizeNetConfig::class,
            $this->authorizeNetConfigProvider->getPaymentConfig($identifier)
        );
    }

    public function testHasPaymentConfig()
    {
        $identifier = 'authorize_net_2';

        $this->assertTrue($this->authorizeNetConfigProvider->hasPaymentConfig($identifier));
    }
}
