<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShippingBundle\Entity\MultiShippingSettings;
use Oro\Bundle\ShippingBundle\Integration\MultiShippingChannelType;
use Oro\Bundle\ShippingBundle\Manager\MultiShippingIntegrationManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\AbstractUserStub;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MultiShippingIntegrationManagerTest extends TestCase
{
    use EntityTrait;

    private ManagerRegistry|MockObject $doctrine;
    private TokenAccessorInterface|MockObject $tokenAccessor;
    private AuthorizationCheckerInterface|MockObject $authorizationChecker;
    private MultiShippingIntegrationManager $manager;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->manager = new MultiShippingIntegrationManager(
            $this->doctrine,
            $this->tokenAccessor,
            $this->authorizationChecker
        );
    }

    public function testCreateIntegrationWithExistingIntegration()
    {
        $organization = $this->getEntity(Organization::class, ['id' => 1]);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $repository = $this->createMock(ObjectRepository::class);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $channel = $this->getEntity(Channel::class, ['id' => 1]);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'type' => MultiShippingChannelType::TYPE,
                'organization' => $organization,
            ])
            ->willReturn($channel);

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->tokenAccessor->expects($this->never())
            ->method('getUser');

        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $result = $this->manager->createIntegration();

        $this->assertSame($channel, $result);
    }

    public function testCreateIntegration()
    {
        $organization = $this->getEntity(Organization::class, ['id' => 1]);

        $this->tokenAccessor->expects($this->exactly(2))
            ->method('getOrganization')
            ->willReturnOnConsecutiveCalls($organization, $organization);

        $repository = $this->createMock(ObjectRepository::class);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'type' => MultiShippingChannelType::TYPE,
                'organization' => $organization,
            ])
            ->willReturn(null);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_integration_create')
            ->willReturn(true);

        $manager = $this->createMock(ObjectManager::class);

        $user = $this->getEntity(User::class, ['id' => 1]);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $manager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Channel::class));

        $manager->expects($this->once())
            ->method('flush');

        $result = $this->manager->createIntegration();

        $this->assertInstanceOf(Channel::class, $result);
        $this->assertEquals(MultiShippingChannelType::TYPE, $result->getType());
        $this->assertEquals('Multi Shipping', $result->getName());
        $this->assertTrue($result->isEnabled());
        $this->assertEquals($organization, $result->getOrganization());
        $this->assertInstanceOf(MultiShippingSettings::class, $result->getTransport());
    }

    public function testCreateIntegrationWhenUserUnauthorized()
    {
        $organization = $this->getEntity(Organization::class, ['id' => 1]);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $repository = $this->createMock(ObjectRepository::class);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'type' => MultiShippingChannelType::TYPE,
                'organization' => $organization,
            ])
            ->willReturn(null);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_integration_create')
            ->willReturn(false);

        $this->tokenAccessor->expects($this->never())
            ->method('getUser');

        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied.');

        $this->manager->createIntegration();
    }

    public function testCreateIntegrationWithUnsupportedUserClass()
    {
        $organization = $this->getEntity(Organization::class, ['id' => 1]);

        $this->tokenAccessor->expects($this->exactly(2))
            ->method('getOrganization')
            ->willReturnOnConsecutiveCalls($organization, $organization);

        $repository = $this->createMock(ObjectRepository::class);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'type' => MultiShippingChannelType::TYPE,
                'organization' => $organization,
            ])
            ->willReturn(null);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_integration_create')
            ->willReturn(true);

        $manager = $this->createMock(ObjectManager::class);

        $user = $this->getEntity(AbstractUserStub::class, ['id' => 1]);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $manager->expects($this->never())
            ->method('persist');

        $manager->expects($this->never())
            ->method('flush');

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            'User must be an instance of "Oro\Bundle\UserBundle\Entity\User", ' .
            '"Oro\Bundle\UserBundle\Tests\Unit\Stub\AbstractUserStub" is given.'
        );

        $this->manager->createIntegration();
    }

    /**
     * @param Channel|null $channel
     * @param bool $expected
     * @dataProvider getDataForTestIntegrationExists
     */
    public function testIntegrationExists(?Channel $channel, bool $expected)
    {
        $organization = $this->getEntity(Organization::class, ['id' => 1]);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $repository = $this->createMock(ObjectRepository::class);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findOneBy')
            ->with([
                'type' => MultiShippingChannelType::TYPE,
                'organization' => $organization,
            ])
            ->willReturn($channel);

        $result = $this->manager->integrationExists();

        $this->assertEquals($expected, $result);
    }

    public function getDataForTestIntegrationExists(): array
    {
        return [
            'Integration exists' => [
                'channel' => $this->getEntity(Channel::class, ['id' => 1]),
                'expected' => true
            ],
            'Integration does not exists' => [
                'channel' => null,
                'expected' => false
            ]
        ];
    }
}
