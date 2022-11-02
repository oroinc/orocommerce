<?php
namespace Oro\Bundle\PricingBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Handler\ProductPriceHandler;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ProductPriceHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var Request */
    private $request;

    /** @var ProductPriceHandler */
    private $handler;

    /** @var PriceManager|\PHPUnit\Framework\MockObject\MockObject */
    private $priceManager;

    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->request = new Request();
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->priceManager = $this->createMock(PriceManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->handler = new ProductPriceHandler($this->eventDispatcher, $this->doctrineHelper, $this->priceManager);
    }

    public function testHandleUpdateWorksWithValidForm()
    {
        $entity = new ProductPrice();
        $em = $this->formHandlerMock($entity);
        $this->priceManager->expects($this->once())
            ->method('flush');
        $em->expects($this->once())
            ->method('commit');

        $this->eventDispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [new FormProcessEvent($this->form, $entity), Events::BEFORE_FORM_DATA_SET],
                [new FormProcessEvent($this->form, $entity), Events::BEFORE_FORM_SUBMIT],
                [new AfterFormProcessEvent($this->form, $entity), Events::BEFORE_FLUSH],
                [new AfterFormProcessEvent($this->form, $entity), Events::AFTER_FLUSH]
            );

        $this->assertTrue($this->handler->process($entity, $this->form, $this->request));
    }

    public function testHandleUpdateWorksWhenFormFlushFailed()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test flush exception');

        $entity = new ProductPrice();
        $em = $this->formHandlerMock($entity);
        $this->priceManager->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception('Test flush exception'));
        $em->expects($this->once())
            ->method('rollback');

        $this->handler->process($entity, $this->form, $this->request);
    }

    /**
     * @param ProductPrice $entity
     *
     * @return EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private function formHandlerMock(ProductPrice $entity): EntityManager
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');
        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $em = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($entity)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('beginTransaction');
        $this->priceManager->expects($this->once())
            ->method('persist')
            ->with($entity);

        return $em;
    }
}
