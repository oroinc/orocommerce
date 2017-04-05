<?php
namespace Oro\Bundle\PricingBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\PricingBundle\Handler\ProductPriceHandler;

class ProductPriceHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var FormInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $form;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var ProductPriceHandler
     */
    private $handler;

    /**
     * @var PriceManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceManager;

    protected function setUp()
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->request = $this->createMock(Request::class);
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

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Test flush exception
     */
    public function testHandleUpdateWorksWhenFormFlushFailed()
    {
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
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function formHandlerMock($entity)
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
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
            ->with(Events::BEFORE_FORM_DATA_SET, new FormProcessEvent($form, $entity));

        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(Events::BEFORE_FORM_SUBMIT, new FormProcessEvent($form, $entity));
    }

    /**
     * @param FormInterface $form
     * @param object $entity
     */
    protected function assertProcessAfterEventsTriggered(FormInterface $form, $entity)
    {
        $this->eventDispatcher->expects($this->at(2))
            ->method('dispatch')
            ->with(Events::BEFORE_FLUSH, new AfterFormProcessEvent($form, $entity));

        $this->eventDispatcher->expects($this->at(3))
            ->method('dispatch')
            ->with(Events::AFTER_FLUSH, new AfterFormProcessEvent($form, $entity));
    }
}
