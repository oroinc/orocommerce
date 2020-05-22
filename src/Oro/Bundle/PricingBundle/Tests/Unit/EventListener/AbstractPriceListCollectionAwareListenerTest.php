<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\PricingBundle\EventListener\AbstractPriceListCollectionAwareListener;
use Oro\Bundle\PricingBundle\Form\PriceListWithPriorityCollectionHandler;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

abstract class AbstractPriceListCollectionAwareListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var PriceListWithPriorityCollectionHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $collectionHandler;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var PriceListRelationTriggerHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $triggerHandler;

    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $featureChecker;

    /**
     * @return string
     */
    abstract protected function getRelationClass();

    /**
     * @return string
     */
    abstract protected function getRelationRepositoryClass();

    /**
     * @return string
     */
    abstract protected function getFallbackClass();

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->collectionHandler = $this->createMock(PriceListWithPriorityCollectionHandler::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->triggerHandler = $this->createMock(PriceListRelationTriggerHandler::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
    }

    /**
     * @param object $targetEntity
     * @param Website $website
     * @param int $numberOfCalls
     * @param FormEvent|\PHPUnit\Framework\MockObject\MockObject $formEvent
     * @return \PHPUnit\Framework\MockObject\MockObject|FormEvent
     */
    protected function assertPostSetDataFormCalls($targetEntity, Website $website, $numberOfCalls, FormEvent $formEvent)
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($targetEntity);
        $form->expects($this->exactly($numberOfCalls))
            ->method('get')
            ->willReturnSelf();
        $form->expects($this->once())
            ->method('all')
            ->willReturn([$form]);
        $form->expects($this->exactly($numberOfCalls - 1))
            ->method('setData')
            ->willReturnSelf();

        $formEvent->expects($this->exactly(2))
            ->method('getForm')
            ->willReturn($form);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with(WebsiteScopedDataType::WEBSITE_OPTION)
            ->willReturn($website);
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        return $formEvent;
    }

    /**
     * @param object $targetEntity
     * @param Website $website
     * @param int $fallbackData
     * @param array $submitted
     * @param AfterFormProcessEvent|\PHPUnit\Framework\MockObject\MockObject $formEvent
     */
    protected function assertPostSubmitFormCalls(
        $targetEntity,
        Website $website,
        int $fallbackData,
        array $submitted,
        AfterFormProcessEvent $formEvent
    ): void {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $priceListByWebsiteForm */
        $fallbackForm = $this->createMock(FormInterface::class);
        $fallbackForm->expects($this->once())
            ->method('getData')
            ->willReturn($fallbackData);
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $priceListByWebsiteForm */
        $priceListByWebsiteForm = $this->createMock(FormInterface::class);
        $priceListByWebsiteForm->expects($this->once())
            ->method('getData')
            ->willReturn([PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD => $submitted]);
        $priceListByWebsiteForm->expects($this->once())
            ->method('get')
            ->with(PriceListsSettingsType::FALLBACK_FIELD)
            ->willReturn($fallbackForm);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($targetEntity);
        $form->expects($this->once())
            ->method('get')
            ->with(AbstractPriceListCollectionAwareListener::PRICE_LISTS_COLLECTION_FORM_FIELD_NAME)
            ->willReturnSelf();
        $form->expects($this->once())
            ->method('all')
            ->willReturn([$priceListByWebsiteForm]);

        $formEvent->expects($this->exactly(2))
            ->method('getForm')
            ->willReturn($form);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with(WebsiteScopedDataType::WEBSITE_OPTION)
            ->willReturn($website);
        $priceListByWebsiteForm->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);
    }

    /**
     * @param object $targetEntity
     * @param Website $website
     * @param array $priceLists
     * @param array $priceListsFallback
     * @param int $numberOfRepoCalls
     */
    protected function assertRepositoryCalls(
        $targetEntity,
        Website $website,
        array $priceLists,
        array $priceListsFallback,
        int $numberOfRepoCalls
    ): void {
        $relationRepository = $this->createMock($this->getRelationRepositoryClass());
        $fallbackRepository = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturnMap([
                [$this->getRelationClass(), $relationRepository],
                [$this->getFallbackClass(), $fallbackRepository],
            ]);

        $relationRepository->expects($this->exactly($numberOfRepoCalls))
            ->method('getPriceLists')
            ->with($targetEntity, $website, PriceListCollectionType::DEFAULT_ORDER)
            ->willReturn($priceLists);

        $fallbackRepository->expects($this->any())
            ->method('findBy')
            ->willReturn($priceListsFallback);
    }
}
