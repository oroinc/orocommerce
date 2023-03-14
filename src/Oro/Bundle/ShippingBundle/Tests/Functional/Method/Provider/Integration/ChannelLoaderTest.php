<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Method\Provider\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ChannelLoaderInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;

class ChannelLoaderTest extends WebTestCase
{
    use RolePermissionExtension;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadChannelData::class, LoadUser::class]);
    }

    private function setUpTokenStorage(): void
    {
        $user = $this->getUser();
        $token = new UsernamePasswordOrganizationToken(
            $user,
            'password',
            'main',
            $user->getOrganization(),
            $user->getUserRoles()
        );
        self::getContainer()->get('security.token_storage')->setToken($token);
    }

    private function getUser(): User
    {
        return $this->getReference(LoadUser::USER);
    }

    private function getChannelLoader(): ChannelLoaderInterface
    {
        return self::getContainer()->get('oro_shipping.method.integration_channel_loader');
    }

    private function assertChannels(array $expected, array $actual): void
    {
        $expectedIds = [];
        foreach ($expected as $ref) {
            $expectedIds[] = $this->getReference($ref)->getId();
        }
        sort($expectedIds);

        $actualIds = [];
        foreach ($actual as $channel) {
            $actualIds[] = $channel->getId();
        }
        sort($actualIds);

        self::assertSame($expectedIds, $actualIds);
    }

    public function testLoadChannelsWhenNoSecurityToken(): void
    {
        $this->assertChannels(
            [],
            $this->getChannelLoader()->loadChannels('bar', true)
        );
    }

    public function testLoadChannels(): void
    {
        $this->setUpTokenStorage();

        $this->assertChannels(
            ['oro_integration:bar_integration', 'oro_integration:extended_bar_integration'],
            $this->getChannelLoader()->loadChannels('bar', true)
        );
    }

    public function testLoadChannelsWhenAccessToChannelEntityDenied(): void
    {
        $this->setUpTokenStorage();

        $this->updateRolePermission($this->getUser()->getUserRoles()[0], Channel::class, AccessLevel::NONE_LEVEL);

        $this->assertChannels(
            [],
            $this->getChannelLoader()->loadChannels('bar', true)
        );
    }

    public function testLoadChannelsWhenAccessToChannelEntityDeniedButApplyAclNotRequested(): void
    {
        $this->setUpTokenStorage();

        $this->updateRolePermission($this->getUser()->getUserRoles()[0], Channel::class, AccessLevel::NONE_LEVEL);

        $this->assertChannels(
            ['oro_integration:bar_integration', 'oro_integration:extended_bar_integration'],
            $this->getChannelLoader()->loadChannels('bar', false)
        );
    }
}
