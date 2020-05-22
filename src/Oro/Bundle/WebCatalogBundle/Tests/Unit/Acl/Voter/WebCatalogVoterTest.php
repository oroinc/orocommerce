<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Acl\Voter\WebCatalogVoter;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class WebCatalogVoterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var WebCatalogVoter
     */
    protected $voter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var WebCatalogUsageProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $usageProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->usageProvider = $this->getMockBuilder(WebCatalogUsageProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->voter = new WebCatalogVoter($this->doctrineHelper, $this->usageProvider);
    }

    protected function tearDown(): void
    {
        unset($this->voter, $this->doctrineHelper);
    }

    public function testVoteAbstain()
    {
        $object = $this->getEntity(WebCatalog::class, ['id' => 1]);

        $this->voter->setClassName(WebCatalog::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->will($this->returnValue(1));

        $this->usageProvider->expects($this->once())
            ->method('isInUse')
            ->with($object)
            ->willReturn(false);

        /** @var \PHPUnit\Framework\MockObject\MockObject|TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            WebCatalogVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, ['DELETE'])
        );
    }

    public function testVoteDeny()
    {
        $object = $this->getEntity(WebCatalog::class, ['id' => 1]);

        $this->voter->setClassName(WebCatalog::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->will($this->returnValue(1));

        $this->usageProvider->expects($this->once())
            ->method('isInUse')
            ->with($object)
            ->willReturn(true);

        /** @var \PHPUnit\Framework\MockObject\MockObject|TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            WebCatalogVoter::ACCESS_DENIED,
            $this->voter->vote($token, $object, ['DELETE'])
        );
    }
}
