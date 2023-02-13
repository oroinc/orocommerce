<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShippingBundle\Entity\MultiShippingSettings;
use Oro\Bundle\ShippingBundle\Integration\MultiShippingChannelType;
use Oro\Bundle\ShippingBundle\Manager\MultiShippingIntegrationManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\AbstractUserStub;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class MultiShippingIntegrationManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface  */
    private $translator;

    /** @var MultiShippingIntegrationManager */
    private $manager;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with(Channel::class)
            ->willReturn($this->repository);

        $this->manager = new MultiShippingIntegrationManager(
            $this->doctrine,
            $this->tokenAccessor,
            $this->authorizationChecker,
            $this->translator
        );
    }

    private function getChannel(int $id): Channel
    {
        $channel = new Channel();
        ReflectionUtil::setId($channel, $id);

        return $channel;
    }

    private function getOrganization(int $id): Organization
    {
        $organization = new Organization();
        $organization->setId($id);

        return $organization;
    }

    public function testCreateIntegrationWithExistingIntegration()
    {
        $organization = $this->getOrganization(1);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $channel = $this->getChannel(1);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['type' => MultiShippingChannelType::TYPE, 'organization' => $organization])
            ->willReturn($channel);

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->translator->expects($this->never())
            ->method('trans');

        $this->tokenAccessor->expects($this->never())
            ->method('getUser');

        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $this->assertSame($channel, $this->manager->createIntegration());
    }

    public function testCreateIntegration()
    {
        $organization = $this->getOrganization(1);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['type' => MultiShippingChannelType::TYPE, 'organization' => $organization])
            ->willReturn(null);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_integration_create')
            ->willReturn(true);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.shipping.multi_shipping_method.label')
            ->willReturn('Multi Shipping');

        $user = new User();
        $user->setId(1);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $manager = $this->createMock(ObjectManager::class);
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
        $this->assertSame($organization, $result->getOrganization());
        $this->assertInstanceOf(MultiShippingSettings::class, $result->getTransport());
    }

    public function testCreateIntegrationWhenNoPermissions()
    {
        $organization = $this->getOrganization(1);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['type' => MultiShippingChannelType::TYPE, 'organization' => $organization])
            ->willReturn(null);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_integration_create')
            ->willReturn(false);

        $this->translator->expects($this->never())
            ->method('trans');

        $this->tokenAccessor->expects($this->never())
            ->method('getUser');

        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $this->expectException(AccessDeniedException::class);

        $this->manager->createIntegration();
    }

    public function testCreateIntegrationWhenNoOrganization()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);

        $this->repository->expects($this->never())
            ->method('findOneBy');

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->tokenAccessor->expects($this->never())
            ->method('getUser');

        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Organization must exist.');

        $this->manager->createIntegration();
    }

    public function testCreateIntegrationWhenUnsupportedUserType()
    {
        $organization = $this->getOrganization(1);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['type' => MultiShippingChannelType::TYPE, 'organization' => $organization])
            ->willReturn(null);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_integration_create')
            ->willReturn(true);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.shipping.multi_shipping_method.label')
            ->willReturn('Multi Shipping');

        $manager = $this->createMock(ObjectManager::class);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(new AbstractUserStub());

        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $manager->expects($this->never())
            ->method('persist');

        $manager->expects($this->never())
            ->method('flush');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'User must be an instance of "Oro\Bundle\UserBundle\Entity\User", ' .
            '"Oro\Bundle\UserBundle\Tests\Unit\Stub\AbstractUserStub" is given.'
        );

        $this->manager->createIntegration();
    }

    /**
     * @dataProvider getDataForTestIntegrationExists
     */
    public function testIntegrationExists(?Channel $channel, bool $expected)
    {
        $organization = $this->getOrganization(1);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['type' => MultiShippingChannelType::TYPE, 'organization' => $organization])
            ->willReturn($channel);

        $this->assertSame($expected, $this->manager->integrationExists());
    }

    public function getDataForTestIntegrationExists(): array
    {
        return [
            'Integration exists' => [
                'channel' => $this->getChannel(1),
                'expected' => true
            ],
            'Integration does not exists' => [
                'channel' => null,
                'expected' => false
            ]
        ];
    }

    public function testIntegrationExistsWhenNoOrganization()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn(null);

        $this->repository->expects($this->never())
            ->method('findOneBy');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Organization must exist.');

        $this->manager->integrationExists();
    }
}
