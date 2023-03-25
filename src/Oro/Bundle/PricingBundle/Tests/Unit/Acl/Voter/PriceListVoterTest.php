<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Acl\Voter\PriceListVoter;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListReferenceChecker;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PriceListVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var PriceListReferenceChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListReferenceChecker;

    /** @var PriceListVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->priceListReferenceChecker = $this->createMock(PriceListReferenceChecker::class);

        $container = TestContainerBuilder::create()
            ->add('oro_pricing.price_list_reference_checker', $this->priceListReferenceChecker)
            ->getContainer($this);

        $this->voter = new PriceListVoter($this->doctrineHelper, $container);
    }

    private function getPriceList(): PriceList
    {
        return new PriceList();
    }

    /**
     * @dataProvider attributesDataProvider
     */
    public function testVote(object $object, bool $isReferential, int $expected)
    {
        $this->voter->setClassName(get_class($object));

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $this->priceListReferenceChecker->expects($this->once())
            ->method('isReferential')
            ->willReturn($isReferential);

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $object, ['DELETE'])
        );
    }

    public function attributesDataProvider(): array
    {
        return [
            [$this->getPriceList(), false, VoterInterface::ACCESS_ABSTAIN],
            [$this->getPriceList(), true, VoterInterface::ACCESS_DENIED]
        ];
    }
}
