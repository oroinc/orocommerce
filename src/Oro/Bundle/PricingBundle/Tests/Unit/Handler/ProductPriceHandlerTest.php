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
    const FORM_DATA = ['field' => 'value'];

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDispatcher;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var FormInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $form;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ProductPriceHandler
     */
    private $handler;

    /**
     * @var PriceManager|\PHPUnit\Framework\MockObject\MockObject
     */
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
        $this->priceManager->expects($this->once())->method('flush');
        $em->expects($this->once())->method('commit');

        $this->assertProcessEventsTriggered($this->form, $entity);
        $this->assertProcessAfterEventsTriggered($this->form, $entity);
        $this->eventDispatcher->expects($this->exactly(4))
            ->method('dispatch');

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
     * @param $entity
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function formHandlerMock($entity)
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
            ->will($this->returnValue(true));
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($entity)
            ->will($this->returnValue($em));
        $em->expects($this->once())
            ->method('beginTransaction');
        $this->priceManager->expects($this->once())
            ->method('persist')
            ->with($entity);
        return $em;
    }

    /**
     * @param FormInterface $form
     * @param object $entity
     */
    protected function assertProcessEventsTriggered(FormInterface $form, $entity)
    {
        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(new FormProcessEvent($form, $entity), Events::BEFORE_FORM_DATA_SET);

        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(new FormProcessEvent($form, $entity), Events::BEFORE_FORM_SUBMIT);
    }

    /**
     * @param FormInterface $form
     * @param object $entity
     */
    protected function assertProcessAfterEventsTriggered(FormInterface $form, $entity)
    {
        $this->eventDispatcher->expects($this->at(2))
            ->method('dispatch')
            ->with(new AfterFormProcessEvent($form, $entity), Events::BEFORE_FLUSH);

        $this->eventDispatcher->expects($this->at(3))
            ->method('dispatch')
            ->with(new AfterFormProcessEvent($form, $entity), Events::AFTER_FLUSH);
    }
}
