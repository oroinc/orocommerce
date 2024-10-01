<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Acl\Voter;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CMSBundle\Acl\Voter\LandingPageDeleteVoter;
use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class LandingPageDeleteVoterTest extends TestCase
{
    private EntityRepository|MockObject $repository;
    private TokenInterface|MockObject $token;
    private DoctrineHelper|MockObject $doctrineHelper;
    private LandingPageDeleteVoter $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->voter = new LandingPageDeleteVoter($this->doctrineHelper);
        $this->voter->setClassName(Page::class);
    }

    public function testVoteIsSelectedInSystemConfiguration(): void
    {
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ConfigValue::class)
            ->willReturn($this->repository);

        $this->repository->expects(self::once())
            ->method('findBy')
            ->with([
                'section' => Configuration::ROOT_NAME,
                'name' => Configuration::HOME_PAGE,
                'textValue' => 1
            ])
            ->willReturn([new ConfigValue()]);

        $result = $this->voter->vote(
            $this->token,
            new DomainObjectReference(Page::class, 1, 2),
            [BasicPermission::DELETE]
        );

        self::assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteIsSelectedAsContentVariant(): void
    {
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityRepository')
            ->withConsecutive([ConfigValue::class], [ContentVariant::class])
            ->willReturn($this->repository);

        $this->repository->expects(self::exactly(2))
            ->method('findBy')
            ->withConsecutive(
                [['section' => Configuration::ROOT_NAME, 'name' => Configuration::HOME_PAGE, 'textValue' => 1]],
                [['type' => CmsPageContentVariantType::TYPE, 'cms_page' => 1]]
            )
            ->willReturn([], [new Page()]);

        $result = $this->voter->vote(
            $this->token,
            new DomainObjectReference(Page::class, 1, 2),
            [BasicPermission::DELETE]
        );

        self::assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteNotSelectedInSystemConfigurationAndAsContentVariant(): void
    {
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityRepository')
            ->withConsecutive([ConfigValue::class], [ContentVariant::class])
            ->willReturn($this->repository);

        $this->repository->expects(self::exactly(2))
            ->method('findBy')
            ->withConsecutive(
                [['section' => Configuration::ROOT_NAME, 'name' => Configuration::HOME_PAGE, 'textValue' => 1]],
                [['type' => CmsPageContentVariantType::TYPE, 'cms_page' => 1]]
            )
            ->willReturn([], []);

        $result = $this->voter->vote(
            $this->token,
            new DomainObjectReference(Page::class, 1, 2),
            [BasicPermission::DELETE]
        );

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    /**
     * @dataProvider voteNoSupportsDataProvider
     */
    public function testVoteNoSupports(string|DomainObjectReference $subject, array $attributes): void
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityRepository')
            ->with(ConfigValue::class)
            ->willReturn($this->repository);

        $result = $this->voter->vote($this->token, $subject, $attributes);
        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function voteNoSupportsDataProvider(): array
    {
        return [
            'wrong attribute' => [
                'subject' => new DomainObjectReference(Page::class, 1, 2),
                'attributes' => [BasicPermission::EDIT],
            ],
            'no object' => [
                'subject' => '',
                'attributes' => [BasicPermission::DELETE],
            ],
            'wrong object' => [
                'subject' => new DomainObjectReference(ContentBlock::class, 1, 2),
                'attributes' => [BasicPermission::DELETE],
            ],
        ];
    }
}
