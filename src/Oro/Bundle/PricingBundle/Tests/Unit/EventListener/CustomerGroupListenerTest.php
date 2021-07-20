<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Event\CustomerGroupEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\EventListener\CustomerGroupListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class CustomerGroupListenerTest extends AbstractPriceListCollectionAwareListenerTest
{
    /**
     * @var CustomerGroupListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new CustomerGroupListener(
            $this->collectionHandler,
            $this->doctrineHelper,
            $this->triggerHandler
        );

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');
    }

    /**
     * {@inheritdoc}
     */
    protected function getRelationClass()
    {
        return PriceListToCustomerGroup::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFallbackClass()
    {
        return PriceListCustomerGroupFallback::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRelationRepositoryClass()
    {
        return PriceListToCustomerGroupRepository::class;
    }

    /**
     * @dataProvider getPostSetData
     * @param CustomerGroup $targetEntity
     * @param Website $website
     * @param PriceListFallback $priceListFallback
     * @param int $numberOfCalls
     */
    public function testOnPostSetData(
        CustomerGroup $targetEntity,
        Website $website,
        PriceListFallback $priceListFallback,
        $numberOfCalls
    ) {
        /** @var BasePriceList $priceList */
        $priceList = $this->getEntity(BasePriceList::class, ['id' => 2]);
        $priceLists = [$priceList];
        $priceListsFallback = [$priceListFallback];

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(true);

        $this->assertRepositoryCalls($targetEntity, $website, $priceLists, $priceListsFallback, 1);
        /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject $formEvent */
        $formEvent = $this->createMock(FormEvent::class);
        $this->assertPostSetDataFormCalls($targetEntity, $website, $numberOfCalls, $formEvent);

        $this->listener->onPostSetData($formEvent);
    }

    /**
     * @return array
     */
    public function getPostSetData()
    {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 1]);
        /** @var PriceListFallback $priceListFallback1 */
        $priceListFallback1 = $this->getEntity(PriceListFallback::class, ['id' => 3]);
        $priceListFallback1->setWebsite($website);
        /** @var PriceListFallback $priceListFallback2 */
        $priceListFallback2 = $this->getEntity(PriceListFallback::class, ['id' => 4]);
        $priceListFallback2->setWebsite($this->getEntity(Website::class, ['id' => 2]));

        return [
            'ok' => [
                'CustomerGroup entity' => $this->getEntity(CustomerGroup::class, ['id' => 1]),
                'Website entity returned by self::formConfig' => $website,
                'fallback price lists' => $priceListFallback1,
                'number of calls to form::get()' => 3
            ],
            'different websites' => [
                'CustomerGroup entity' => $this->getEntity(CustomerGroup::class, ['id' => 1]),
                'Website entity returned by self::formConfig' => $website,
                'fallback price lists' => $priceListFallback2,
                'number of calls to form::get()' => 2
            ],
        ];
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

        /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject $formEvent */
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
            'empty' => [new CustomerGroup()]
        ];
    }

    public function testOnPostSetDataFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(false);

        /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject $formEvent */
        $formEvent = $this->createMock(FormEvent::class);
        $formEvent->expects($this->never())
            ->method('getForm');

        $this->listener->onPostSetData($formEvent);
    }

    /**
     * @dataProvider getOnPostSubmitData
     *
     * @param CustomerGroup $targetEntity
     * @param BasePriceList[] $priceLists
     * @param Website $website
     * @param PriceListFallback[] $priceListsFallback
     * @param int $fallbackData
     * @param int $numberOfEmCalls
     * @param int $numberOfRepoCalls
     */
    public function testOnPostSubmit(
        $targetEntity,
        $priceLists,
        $website,
        $priceListsFallback,
        $fallbackData,
        $numberOfEmCalls,
        $numberOfRepoCalls
    ) {
        $submitted = [];

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(true);

        $this->assertRepositoryCalls($targetEntity, $website, $priceLists, $priceListsFallback, $numberOfRepoCalls);
        /** @var AfterFormProcessEvent|\PHPUnit\Framework\MockObject\MockObject $formEvent */
        $formEvent = $this->createMock(AfterFormProcessEvent::class);
        $this->assertPostSubmitFormCalls($targetEntity, $website, $fallbackData, $submitted, $formEvent);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $priceListByWebsiteForm */
        $entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->exactly($numberOfEmCalls))
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->exactly($numberOfEmCalls))
            ->method('persist');

        $this->collectionHandler->expects($this->once())
            ->method('handleChanges')
            ->with($submitted, $priceLists, $targetEntity, $website)
            ->willReturn(true);

        $this->listener->onPostSubmit($formEvent);
    }

    /**
     * @return array
     */
    public function getOnPostSubmitData()
    {
        /** @var BasePriceList $priceList */
        $priceList = $this->getEntity(BasePriceList::class, ['id' => 3]);
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 1]);
        /** @var PriceListFallback $priceListFallback1 */
        $priceListFallback1 = $this->getEntity(PriceListFallback::class, ['id' => 3]);
        $priceListFallback1->setWebsite($website);
        /** @var PriceListFallback $priceListFallback2 */
        $priceListFallback2 = $this->getEntity(PriceListFallback::class, ['id' => 4]);
        $priceListFallback2->setWebsite($this->getEntity(Website::class, ['id' => 2]));

        return [
            'empty pricelist' => [
                'CustomerGroup entity' => $this->getEntity(CustomerGroup::class, ['id' => 1]),
                'list returned by self::relationRepository' => [$priceList],
                'Website entity returned by self::formConfig' => $website,
                'fallback price lists' => [],
                'fallback data' => PriceListCustomerFallback::ACCOUNT_GROUP,
                'number of calls to entity manager' => 0,
                'number of calls to repository' => 1,
            ],
            'different default fallback and fallback data' => [
                'CustomerGroup entity' => $this->getEntity(CustomerGroup::class, ['id' => 1]),
                'list returned by self::relationRepository' => [$priceList],
                'Website entity returned by self::formConfig' => $website,
                'fallback price lists' => [$priceListFallback2],
                'fallback data' => 1,
                'number of calls to entity manager' => 1,
                'number of calls to repository' => 1,
            ],
            'empty target entity' => [
                'CustomerGroup entity' => new CustomerGroup(),
                'list returned by self::relationRepository' => [],
                'Website entity returned by self::formConfig' => $website,
                'fallback price lists' => [],
                'fallback data' => 1,
                'number of calls to entity manager' => 1,
                'number of calls to repository' => 0,
            ],
        ];
    }

    public function testOnPostSubmitFeatureDisabled()
    {
        /** @var AfterFormProcessEvent|\PHPUnit\Framework\MockObject\MockObject $formEvent */
        $formEvent = $this->createMock(AfterFormProcessEvent::class);

        $formEvent->expects($this->never())
            ->method('getForm');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(false);

        $this->listener->onPostSubmit($formEvent);
    }

    public function testOnGroupRemove()
    {
        /** @var CustomerGroupEvent|\PHPUnit\Framework\MockObject\MockObject $customerGroupEvent */
        $customerGroupEvent = $this->createMock(CustomerGroupEvent::class);
        $customerGroup = new CustomerGroup();

        $customerGroupEvent->expects($this->once())
            ->method('getData')
            ->willReturn($customerGroup);

        $this->triggerHandler->expects($this->once())
            ->method('handleCustomerGroupRemove')
            ->with($customerGroup);

        $this->listener->onGroupRemove($customerGroupEvent);
    }
}
