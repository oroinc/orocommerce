<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Acl\Voter\PriceListVoter;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListReferenceChecker;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PriceListVoterTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private PriceListReferenceChecker&MockObject $priceListReferenceChecker;
    private PriceListVoter $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->priceListReferenceChecker = $this->createMock(PriceListReferenceChecker::class);

        $container = TestContainerBuilder::create()
            ->add(PriceListReferenceChecker::class, $this->priceListReferenceChecker)
            ->getContainer($this);

        $this->voter = new PriceListVoter($this->doctrineHelper, $container);
        $this->voter->setClassName(PriceList::class);
    }

    /**
     * @dataProvider attributesDataProvider
     */
    public function testVote(bool $isReferential, int $expected): void
    {
        $object = new PriceList();

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $this->priceListReferenceChecker->expects(self::once())
            ->method('isReferential')
            ->with(self::identicalTo($object))
            ->willReturn($isReferential);

        $token = $this->createMock(TokenInterface::class);
        self::assertEquals(
            $expected,
            $this->voter->vote($token, $object, ['DELETE'])
        );
    }

    public static function attributesDataProvider(): array
    {
        return [
            [false, VoterInterface::ACCESS_ABSTAIN],
            [true, VoterInterface::ACCESS_DENIED]
        ];
    }
}
