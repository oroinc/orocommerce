<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Event\CustomerEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\EventListener\CustomerListener;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class CustomerListenerTest extends AbstractPriceListCollectionAwareListenerTest
{
    /**
     * @var CustomerListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new CustomerListener(
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
        return PriceListToCustomer::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFallbackClass()
    {
        return PriceListCustomerFallback::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRelationRepositoryClass()
    {
        return PriceListToCustomerRepository::class;
    }

    /**
     * @dataProvider getPostSetData
     * @param Customer $targetEntity
     * @param Website $website
     * @param PriceListFallback $priceListFallback
     * @param int $numberOfCalls
     */
    public function testOnPostSetData($targetEntity, $website, $priceListFallback, $numberOfCalls)
    {
        /** @var BasePriceList $priceList */
        $priceList = $this->getEntity(BasePriceList::class, ['id' => 2]);
        $priceLists = [$priceList];
        $priceListsFallback = [$priceListFallback];

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(true);

        /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject $formEvent */
        $formEvent = $this->createMock(FormEvent::class);
        $this->assertPostSetDataFormCalls($targetEntity, $website, $numberOfCalls, $formEvent);
        $this->assertRepositoryCalls($targetEntity, $website, $priceLists, $priceListsFallback, 1);

        $this->listener->onPostSetData($formEvent);
    }

    /**
     * @return array
     */
    public function getPostSetData()
    {
        /** @var Website $website1 */
        $website1 = $this->getEntity(Website::class, ['id' => 1]);
        /** @var Website $website2 */
        $website2 = $this->getEntity(Website::class, ['id' => 2]);
        /** @var PriceListFallback $priceListFallback1 */
        $priceListFallback1 = $this->getEntity(PriceListFallback::class, ['id' => 3]);
        $priceListFallback1->setWebsite($website1);
        /** @var PriceListFallback $priceListFallback2 */
        $priceListFallback2 = $this->getEntity(PriceListFallback::class, ['id' => 4]);
        $priceListFallback2->setWebsite($website2);

        return [
            'ok' => [
                'Customer entity' => $this->getEntity(Customer::class, ['id' => 1]),
                'Website entity returned by self::formConfig' => $website1,
                'fallback price lists' => $priceListFallback1,
                'number of calls to form::get()' => 3
            ],
            'different websites' => [
                'Customer entity' => $this->getEntity(Customer::class, ['id' => 1]),
                'Website entity returned by self::formConfig' => $website1,
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

    /**
     * @return array
     */
    public function wrongEntityDataProvider()
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

        /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject $formEvent */
        $formEvent = $this->createMock(FormEvent::class);
        $formEvent->expects($this->never())
            ->method('getForm');

        $this->listener->onPostSetData($formEvent);
    }

    /**
     * @dataProvider getOnPostSubmitData
     *
     * @param Customer $targetEntity
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

        /** @var AfterFormProcessEvent|\PHPUnit\Framework\MockObject\MockObject $formEvent */
        $formEvent = $this->createMock(AfterFormProcessEvent::class);
        $this->assertPostSubmitFormCalls($targetEntity, $website, $fallbackData, $submitted, $formEvent);
        $this->assertRepositoryCalls($targetEntity, $website, $priceLists, $priceListsFallback, $numberOfRepoCalls);

        $this->collectionHandler->expects($this->once())
            ->method('handleChanges')
            ->with($submitted, $priceLists, $targetEntity, $website)
            ->willReturn(true);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $priceListByWebsiteForm */
        $entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->exactly($numberOfEmCalls))
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->exactly($numberOfEmCalls))
            ->method('persist');

        $this->listener->onPostSubmit($formEvent);
    }

    /**
     * @return array
     */
    public function getOnPostSubmitData()
    {
        /** @var BasePriceList $priceList */
        $priceList = $this->getEntity(BasePriceList::class, ['id' => 3]);
        /** @var Website $website1 */
        $website1 = $this->getEntity(Website::class, ['id' => 1]);
        /** @var Website $website2 */
        $website2 = $this->getEntity(Website::class, ['id' => 2]);
        /** @var PriceListFallback $priceListFallback1 */
        $priceListFallback1 = $this->getEntity(PriceListFallback::class, ['id' => 3]);
        $priceListFallback1->setWebsite($website1);
        /** @var PriceListFallback $priceListFallback2 */
        $priceListFallback2 = $this->getEntity(PriceListFallback::class, ['id' => 4]);
        $priceListFallback2->setWebsite($website2);

        return [
            'empty pricelist' => [
                'Customer entity' => $this->getEntity(Customer::class, ['id' => 1]),
                'list returned by self::relationRepository' => [$priceList],
                'Website entity returned by self::formConfig' => $website1,
                'fallback price lists' => [],
                'fallback data' => PriceListCustomerFallback::ACCOUNT_GROUP,
                'number of calls to entity manager' => 0,
                'number of calls to repository' => 1,
            ],
            'different default fallback and fallback data' => [
                'Customer entity' => $this->getEntity(Customer::class, ['id' => 1]),
                'list returned by self::relationRepository' => [$priceList],
                'Website entity returned by self::formConfig' => $website1,
                'fallback price lists' => [$priceListFallback2],
                'fallback data' => 1,
                'number of calls to entity manager' => 1,
                'number of calls to repository' => 1,
            ],
            'empty target entity' => [
                'Customer entity' => new Customer(),
                'list returned by self::relationRepository' => [],
                'Website entity returned by self::formConfig' => $website1,
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

    public function testOnCustomerGroupChange()
    {
        /** @var CustomerEvent|\PHPUnit\Framework\MockObject\MockObject $customerEvent */
        $customerEvent = $this->createMock(CustomerEvent::class);
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $website = new Website();

        $customerEvent->expects($this->once())
            ->method('getCustomer')
            ->willReturn($customer);

        $websiteRepository = $this->createMock(WebsiteRepository::class);
        $websiteRepository->expects($this->once())
            ->method('getAllWebsites')
            ->willReturn([$website]);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Website::class)
            ->willReturn($websiteRepository);

        $this->triggerHandler->expects($this->once())
            ->method('handleCustomerChange')
            ->with($customer, $website);

        $this->listener->onCustomerGroupChange($customerEvent);
    }
}
