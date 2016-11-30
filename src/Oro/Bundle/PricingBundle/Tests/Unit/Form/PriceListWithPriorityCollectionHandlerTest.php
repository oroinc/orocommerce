<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\PricingBundle\Entity\PriceListToAccount;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Form\PriceListWithPriorityCollectionHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListWithPriorityCollectionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceListWithPriorityCollectionHandler
     */
    protected $handler;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject $doctrineHelper
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $accessor = new PropertyAccessor();

        $this->handler = new PriceListWithPriorityCollectionHandler($this->doctrineHelper, $accessor);
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

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

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

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

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

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();

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
        $account = new Account();
        $newRelation = new PriceListToAccount();
        $notChangedRelation = new PriceListToAccount();

        $existing = [
            $notChangedRelation,
        ];
        $submitted = [
            $newRelation,
            $notChangedRelation
        ];

        $meta = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $meta->expects($this->once())
            ->method('getAssociationsByTargetClass')
            ->with(get_class($account))
            ->willReturn([['fieldName'=>'account']]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();

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

        $hasChanges = $this->handler->handleChanges($submitted, $existing, $account, $website);
        $this->assertTrue($hasChanges);
        $this->assertSame($account, $newRelation->getAccount());
    }
}
