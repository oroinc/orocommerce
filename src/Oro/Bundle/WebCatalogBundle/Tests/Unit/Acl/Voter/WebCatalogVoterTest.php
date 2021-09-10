<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Acl\Voter\WebCatalogVoter;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class WebCatalogVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var WebCatalogUsageProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $webCatalogUsageProvider;

    /** @var WebCatalogVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->webCatalogUsageProvider = $this->createMock(WebCatalogUsageProviderInterface::class);

        $container = TestContainerBuilder::create()
            ->add('oro_web_catalog.provider.web_catalog_usage_provider', $this->webCatalogUsageProvider)
            ->getContainer($this);

        $this->voter = new WebCatalogVoter($this->doctrineHelper, $container);
        $this->voter->setClassName(WebCatalog::class);
    }

    public function testVoteAbstain()
    {
        $object = new WebCatalog();
        ReflectionUtil::setId($object, 1);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $this->webCatalogUsageProvider->expects($this->once())
            ->method('isInUse')
            ->with($object)
            ->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, ['DELETE'])
        );
    }

    public function testVoteDeny()
    {
        $object = new WebCatalog();
        ReflectionUtil::setId($object, 1);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $this->webCatalogUsageProvider->expects($this->once())
            ->method('isInUse')
            ->with($object)
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $object, ['DELETE'])
        );
    }
}
