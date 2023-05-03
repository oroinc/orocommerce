<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Form\PriceListWithPriorityCollectionHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListWithPriorityCollectionHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var PriceListWithPriorityCollectionHandler */
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->handler = new PriceListWithPriorityCollectionHandler(
            $this->doctrineHelper,
            PropertyAccess::createPropertyAccessor()
        );
    }

    public function testNoChanges()
    {
        $website = new Website();
        $notChangedRelation = new PriceListToWebsite();

        $existing = [
            $notChangedRelation,
        ];
        $submitted = [
            $notChangedRelation
        ];

        $em = $this->createMock(EntityManagerInterface::class);

        $uow = $this->createMock(UnitOfWork::class);

        $uow->expects($this->once())
            ->method('computeChangesets');

        $uow->expects($this->once())
            ->method('isScheduledForUpdate')
            ->with($notChangedRelation)
            ->willReturn(false);

        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $hasChanges = $this->handler->handleChanges($submitted, $existing, $website, $website);
        $this->assertFalse($hasChanges);
    }

    public function testScheduledUpdateChanges()
    {
        $website = new Website();
        $notChangedRelation = new PriceListToWebsite();

        $existing = [
            $notChangedRelation,
        ];
        $submitted = [
            $notChangedRelation
        ];

        $em = $this->createMock(EntityManagerInterface::class);

        $uow = $this->createMock(UnitOfWork::class);

        $uow->expects($this->once())
            ->method('computeChangesets');

        $uow->expects($this->once())
            ->method('isScheduledForUpdate')
            ->with($notChangedRelation)
            ->willReturn(true);

        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $hasChanges = $this->handler->handleChanges($submitted, $existing, $website, $website);
        $this->assertTrue($hasChanges);
    }

    public function testRelationsDeleted()
    {
        $website = new Website();
        $deletedRelation = new PriceListToWebsite();
        $notChangedRelation = new PriceListToWebsite();

        $existing = [
            $deletedRelation,
            $notChangedRelation,
        ];
        $submitted = [
            $notChangedRelation
        ];

        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $em->expects($this->once())
            ->method('remove')
            ->with($deletedRelation);

        $hasChanges = $this->handler->handleChanges($submitted, $existing, $website, $website);
        $this->assertTrue($hasChanges);
    }

    public function testNewRelationsAdded()
    {
        $website = new Website();
        $customer = new Customer();
        $newRelation = new PriceListToCustomer();
        $notChangedRelation = new PriceListToCustomer();

        $existing = [
            $notChangedRelation,
        ];
        $submitted = [
            $newRelation,
            $notChangedRelation
        ];

        $meta = $this->createMock(ClassMetadata::class);

        $meta->expects($this->once())
            ->method('getAssociationsByTargetClass')
            ->with(get_class($customer))
            ->willReturn([['fieldName'=>'customer']]);

        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($newRelation))
            ->willReturn($meta);

        $em->expects($this->once())
            ->method('persist')
            ->with($newRelation);

        $hasChanges = $this->handler->handleChanges($submitted, $existing, $customer, $website);
        $this->assertTrue($hasChanges);
        $this->assertSame($customer, $newRelation->getCustomer());
    }
}
