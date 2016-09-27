<?php
namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use Oro\Bundle\PricingBundle\Model\PriceListReferenceChecker;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PriceListReferenceCheckerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var PriceListReferenceChecker
     */
    protected $priceListReferenceChecker;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder(RegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityRepository = $this->getMockBuilder(PriceRuleLexemeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRepository->expects($this->once())
            ->method('getRelationIds')
            ->willReturn([1, 2]);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->entityManager);

        $this->priceListReferenceChecker = new PriceListReferenceChecker($this->registry);
    }

    public function testReferential()
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $this->assertTrue($this->priceListReferenceChecker->isReferential($priceList));
    }

    public function testNotReferential()
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 8]);
        $this->assertFalse($this->priceListReferenceChecker->isReferential($priceList));
    }
}
