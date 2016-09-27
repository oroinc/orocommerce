<?php
namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use Oro\Bundle\PricingBundle\Model\PriceListIsReferentialChecker;
use Oro\Bundle\PricingBundle\Model\PriceListIsReferentialCheckerInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PriceListIsReferentialCheckerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var PriceListIsReferentialCheckerInterface
     */
    protected $isReferentialChecker;

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
            ->method('countReferencesForRelation')
            ->willReturn([
                ['relationId' => 1, 'relationCount' => 3],
                ['relationId' => 2, 'relationCount' => 1]
            ]);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->entityManager);

        $this->isReferentialChecker = new PriceListIsReferentialChecker($this->registry);
    }

    public function testReferential()
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $this->assertTrue($this->isReferentialChecker->isReferential($priceList));
    }

    public function testNotReferential()
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 8]);
        $this->assertFalse($this->isReferentialChecker->isReferential($priceList));
    }
}
