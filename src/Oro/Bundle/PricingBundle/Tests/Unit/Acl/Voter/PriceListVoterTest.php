<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Acl\Voter\PriceListVoter;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListReferenceChecker;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PriceListVoterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PriceListVoter
     */
    protected $voter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var PriceListReferenceChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $priceListReferenceChecker;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceListReferenceChecker = $this->getMockBuilder(PriceListReferenceChecker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->voter = new PriceListVoter($this->doctrineHelper, $this->priceListReferenceChecker);
    }

    protected function tearDown(): void
    {
        unset($this->voter, $this->doctrineHelper);
    }

    /**
     * @param object $object
     * @param int $expected
     *
     * @dataProvider attributesDataProvider
     */
    public function testVote($object, $expected)
    {
        $this->voter->setClassName(get_class($object));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->will($this->returnValue(1));

        /** @var \PHPUnit\Framework\MockObject\MockObject|TokenInterface $token */
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $object, ['DELETE'])
        );
    }

    /**
     * @return array
     */
    public function attributesDataProvider()
    {
        return [
            [$this->getPriceList(false), 0],
            [$this->getPriceList(true), -1]
        ];
    }

    /**
     * @param bool $isDefault
     * @return PriceList
     */
    protected function getPriceList($isDefault)
    {
        $priceList = new PriceList();
        $priceList->setDefault($isDefault);

        return $priceList;
    }
}
