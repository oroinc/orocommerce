<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\EventListener\CustomerFlatPricingRelationFormListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class CustomerFlatPricingRelationFormListenerTest extends AbstractFlatPricingRelationFormListenerTest
{
    private CustomerFlatPricingRelationFormListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new CustomerFlatPricingRelationFormListener(
            $this->doctrineHelper,
            $this->triggerHandler
        );

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');
    }

    /**
     * {@inheritDoc}
     */
    protected function assertGetPriceListRelation(Website $website, $targetEntity, ?BasePriceListRelation $relation)
    {
        $repo = $this->createMock(PriceListToCustomerRepository::class);
        $repo->expects($this->once())
            ->method('getFirstRelation')
            ->with($website, $targetEntity)
            ->willReturn($relation);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(PriceListToCustomer::class)
            ->willReturn($repo);
    }

    public function testOnPostSetData()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $targetEntity = $this->getEntity(Customer::class, ['id' => 1]);
        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(true);

        $relation = new PriceListToCustomer();
        $relation->setPriceList($priceList);
        $relation->setWebsite($website);
        $relation->setCustomer($targetEntity);
        $this->assertGetPriceListRelation($website, $targetEntity, $relation);

        $formEvent = $this->createMock(FormEvent::class);
        $this->assertPostSetDataFormCalls($targetEntity, $website, $formEvent, $priceList);

        $this->listener->onPostSetData($formEvent);
    }

    /**
     * @dataProvider wrongEntityDataProvider
     */
    public function testOnPostSetDataTargetEntityEmpty($entity)
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(true);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($entity);

        $formEvent = $this->createMock(FormEvent::class);
        $formEvent->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->listener->onPostSetData($formEvent);
    }

    public function wrongEntityDataProvider(): array
    {
        return [
            'none' => [null],
            'empty' => [new Customer()]
        ];
    }

    public function testOnPostSetDataFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(false);

        $formEvent = $this->createMock(FormEvent::class);
        $formEvent->expects($this->never())
            ->method('getForm');

        $this->listener->onPostSetData($formEvent);
    }

    public function testOnPostSubmitNoRelationNoPriceList()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $targetEntity = $this->getEntity(Customer::class, ['id' => 1]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(true);

        $this->assertGetPriceListRelation($website, $targetEntity, null);

        $formEvent = $this->createMock(AfterFormProcessEvent::class);
        $this->assertPostSubmitFormCalls($website, $targetEntity, $formEvent, null);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');
        $this->triggerHandler->expects($this->never())
            ->method($this->anything());

        $this->listener->onPostSubmit($formEvent);
    }

    public function testOnPostSubmitNoRelationNewPriceList()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $targetEntity = $this->getEntity(Customer::class, ['id' => 1]);
        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(true);

        $formEvent = $this->createMock(AfterFormProcessEvent::class);
        $this->assertPostSubmitFormCalls($website, $targetEntity, $formEvent, $priceList);

        $relation = new PriceListToCustomer();
        $relation->setPriceList($priceList);
        $relation->setWebsite($website);
        $relation->setCustomer($targetEntity);
        $this->assertGetPriceListRelation($website, $targetEntity, null);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($relation);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->triggerHandler->expects($this->once())
            ->method('handleCustomerChange')
            ->with($targetEntity, $website);

        $this->listener->onPostSubmit($formEvent);
    }

    public function testOnPostSubmitHasRelationNoPriceListChanges()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $targetEntity = $this->getEntity(Customer::class, ['id' => 1]);
        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(true);

        $formEvent = $this->createMock(AfterFormProcessEvent::class);
        $this->assertPostSubmitFormCalls($website, $targetEntity, $formEvent, $priceList);

        $relation = new PriceListToCustomer();
        $relation->setPriceList($priceList);
        $relation->setWebsite($website);
        $relation->setCustomer($targetEntity);
        $this->assertGetPriceListRelation($website, $targetEntity, $relation);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($relation);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->triggerHandler->expects($this->never())
            ->method('handleCustomerChange');

        $this->listener->onPostSubmit($formEvent);
    }

    public function testOnPostSubmitHasRelationNoPriceList()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $targetEntity = $this->getEntity(Customer::class, ['id' => 1]);
        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(true);

        $formEvent = $this->createMock(AfterFormProcessEvent::class);
        $this->assertPostSubmitFormCalls($website, $targetEntity, $formEvent, null);

        $relation = new PriceListToCustomer();
        $relation->setPriceList($priceList);
        $relation->setWebsite($website);
        $relation->setCustomer($targetEntity);
        $this->assertGetPriceListRelation($website, $targetEntity, $relation);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('remove')
            ->with($relation);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->triggerHandler->expects($this->once())
            ->method('handleCustomerChange')
            ->with($targetEntity, $website);

        $this->listener->onPostSubmit($formEvent);
    }

    public function testOnPostSubmitHasRelationNewPriceListSet()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $targetEntity = $this->getEntity(Customer::class, ['id' => 1]);
        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(true);

        $formEvent = $this->createMock(AfterFormProcessEvent::class);
        $this->assertPostSubmitFormCalls($website, $targetEntity, $formEvent, $priceList2);

        $relation = new PriceListToCustomer();
        $relation->setPriceList($priceList1);
        $relation->setWebsite($website);
        $relation->setCustomer($targetEntity);
        $this->assertGetPriceListRelation($website, $targetEntity, $relation);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($relation);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->triggerHandler->expects($this->once())
            ->method('handleCustomerChange')
            ->with($targetEntity, $website);

        $this->listener->onPostSubmit($formEvent);
    }

    public function testOnPostSubmitFeatureDisabled()
    {
        $formEvent = $this->createMock(AfterFormProcessEvent::class);
        $formEvent->expects($this->never())
            ->method('getForm');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(false);

        $this->listener->onPostSubmit($formEvent);
    }
}
