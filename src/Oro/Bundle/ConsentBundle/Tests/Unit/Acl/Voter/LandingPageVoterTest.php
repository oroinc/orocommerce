<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Acl\Voter\LandingPageVoter;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LandingPageVoterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LandingPageVoter
     */
    protected $voter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->voter = new LandingPageVoter($this->doctrineHelper);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->voter);
        unset($this->doctrineHelper);
    }

    /**
     * @dataProvider attributesDataProvider
     *
     * @param string $attribute
     * @param $hasConsents
     * @param $expected
     */
    public function testVote($attribute, $hasConsents, $expected)
    {
        $object = $this->createMock(Page::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($object)
            ->will($this->returnValue(Page::class));

        $this->voter->setClassName(Page::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->will($this->returnValue(1));

        $this->assertHasConsents($hasConsents);

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $object, [$attribute])
        );
    }

    /**
     * @return array
     */
    public function attributesDataProvider()
    {
        return [
            ['EDIT', true, LandingPageVoter::ACCESS_DENIED],
            ['EDIT', false, LandingPageVoter::ACCESS_ABSTAIN],
            ['DELETE', true, LandingPageVoter::ACCESS_DENIED],
            ['DELETE', false, LandingPageVoter::ACCESS_ABSTAIN]
        ];
    }

    /**
     * @param $assertHasConsents
     */
    protected function assertHasConsents($assertHasConsents)
    {
        $landingPage = $this->createMock(Page::class);
        $repository = $this->createMock(ConsentAcceptanceRepository::class);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->with(Page::class, 1)
            ->willReturn($landingPage);

        $repository
            ->expects($this->once())
            ->method('hasLandingPageAcceptedConsents')
            ->will($this->returnValue($assertHasConsents));

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->will($this->returnValue($repository));
    }
}
