<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Acl\Voter\WebCatalogVoter;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class WebCatalogVoterTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private WebCatalogUsageProviderInterface&MockObject $webCatalogUsageProvider;
    private WebCatalogVoter $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->webCatalogUsageProvider = $this->createMock(WebCatalogUsageProviderInterface::class);

        $container = TestContainerBuilder::create()
            ->add(WebCatalogUsageProviderInterface::class, $this->webCatalogUsageProvider)
            ->getContainer($this);

        $this->voter = new WebCatalogVoter($this->doctrineHelper, $container);
        $this->voter->setClassName(WebCatalog::class);
    }

    public function testVoteAbstain(): void
    {
        $object = new WebCatalog();
        ReflectionUtil::setId($object, 1);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $this->webCatalogUsageProvider->expects(self::once())
            ->method('isInUse')
            ->with($object)
            ->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, ['DELETE'])
        );
    }

    public function testVoteDeny(): void
    {
        $object = new WebCatalog();
        ReflectionUtil::setId($object, 1);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $this->webCatalogUsageProvider->expects(self::once())
            ->method('isInUse')
            ->with($object)
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $object, ['DELETE'])
        );
    }
}
