<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

abstract class AbstractFlatPricingRelationFormListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

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
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->triggerHandler = $this->createMock(PriceListRelationTriggerHandler::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
    }

    abstract protected function assertGetPriceListRelation(
        Website $website,
        $targetEntity,
        ?BasePriceListRelation $relation
    );

    /**
     * @param object $targetEntity
     * @param Website $website
     * @param FormEvent|\PHPUnit\Framework\MockObject\MockObject $formEvent
     * @param PriceList $priceList
     */
    protected function assertPostSetDataFormCalls(
        $targetEntity,
        Website $website,
        FormEvent $formEvent,
        PriceList $priceList
    ) {
        $priceListForm = $this->assertPriceListFormCalls($website, $targetEntity, $formEvent);
        $priceListForm->expects($this->once())
            ->method('setData')
            ->with($priceList);
    }

    /**
     * @param Website $website
     * @param object $targetEntity
     * @param AfterFormProcessEvent|\PHPUnit\Framework\MockObject\MockObject $formEvent
     * @param PriceList|null $submittedPriceList
     */
    protected function assertPostSubmitFormCalls(
        Website $website,
        $targetEntity,
        AfterFormProcessEvent $formEvent,
        ?PriceList $submittedPriceList
    ): void {
        $priceListForm = $this->assertPriceListFormCalls($website, $targetEntity, $formEvent);
        $priceListForm->expects($this->once())
            ->method('getData')
            ->willReturn($submittedPriceList);
    }

    /**
     * @param Website $website
     * @param object $targetEntity
     * @param \PHPUnit\Framework\MockObject\MockObject $formEvent
     */
    protected function assertPriceListFormCalls(
        Website $website,
        object $targetEntity,
        $formEvent
    ) {
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with(WebsiteScopedDataType::WEBSITE_OPTION)
            ->willReturn($website);
        $priceListsByWebsiteForm = $this->createMock(FormInterface::class);
        $priceListsByWebsiteForm->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $priceListForm = $this->createMock(FormInterface::class);
        $priceListsByWebsiteForm->expects($this->once())
            ->method('get')
            ->with('priceList')
            ->willReturn($priceListForm);

        $priceListsByWebsitesForm = $this->createMock(FormInterface::class);
        $priceListsByWebsitesForm->expects($this->once())
            ->method('all')
            ->willReturn([$priceListsByWebsiteForm]);

        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->expects($this->once())
            ->method('getData')
            ->willReturn($targetEntity);
        $parentForm->expects($this->once())
            ->method('get')
            ->with('priceListsByWebsites')
            ->willReturn($priceListsByWebsitesForm);

        $formEvent->expects($this->any())
            ->method('getForm')
            ->willReturn($parentForm);

        return $priceListForm;
    }
}
