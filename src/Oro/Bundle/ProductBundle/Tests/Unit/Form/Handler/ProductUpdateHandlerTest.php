<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\FormBundle\Form\Handler\FormHandler;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\HandlerStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Handler\ProductUpdateHandler;
use Oro\Bundle\ProductBundle\Form\Handler\RelatedItemsHandler;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use Oro\Bundle\UIBundle\Route\Router;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Symfony\Bundle\FrameworkBundle\Routing\Router as SymfonyRouter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductUpdateHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];
    private const PRODUCT_ID = 1;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Request */
    private $request;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Session */
    private $session;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Router */
    private $router;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    private $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface */
    private $form;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManager */
    private $entityManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ActionGroupRegistry */
    private $actionGroupRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RelatedItemsHandler */
    private $relatedItemsHandler;

    /** @var bool */
    private $resultCallbackInvoked;

    /** @var ProductUpdateHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->request = new Request();
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->session = $this->createMock(Session::class);
        $this->router = $this->createMock(Router::class);
        $this->form = $this->createMock(FormInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->actionGroupRegistry = $this->createMock(ActionGroupRegistry::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->relatedItemsHandler = $this->createMock(RelatedItemsHandler::class);

        $this->resultCallbackInvoked = false;

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $symfonyRouter = $this->createMock(SymfonyRouter::class);
        $symfonyRouter->expects($this->any())
            ->method('generate')
            ->willReturn('generated_redirect_url');

        $this->handler = new ProductUpdateHandler(
            $this->requestStack,
            $this->session,
            $this->router,
            $this->doctrineHelper,
            new FormHandler($this->eventDispatcher, $this->doctrineHelper)
        );
        $this->handler->setTranslator($this->translator);
        $this->handler->setActionGroupRegistry($this->actionGroupRegistry);
        $this->handler->setRouter($symfonyRouter);
        $this->handler->setRelatedItemsHandler($this->relatedItemsHandler);
    }

    public function testHandleUpdateFailsWhenFormHandlerIsInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Argument $formHandler should be an object with method "process", stdClass given.'
        );

        $entity = new ProductStub();

        $this->form->expects($this->never())
            ->method($this->anything());

        $this->handler->update($entity, $this->form, 'Saved', new \stdClass());
    }

    public function testHandleUpdateWorksWithBlankDataAndNoHandler()
    {
        $this->request->initialize(['_wid' => 'WID']);

        $entity = new ProductStub();

        $form = $this->createMock(FormInterface::class);

        $expected = $this->getExpectedSaveData($form, $entity);

        $result = $this->handler->handleUpdate(
            $entity,
            $form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testUpdateWorksWithBlankDataAndNoHandler()
    {
        $this->request->initialize(['_wid' => 'WID']);

        $entity = new ProductStub();

        $expected = $this->getExpectedSaveData($this->form, $entity);

        $result = $this->handler->update($entity, $this->form, 'Saved');
        $this->assertEquals($expected, $result);
    }

    public function testHandleUpdateWorksWithInvalidForm()
    {
        $this->request->initialize(['_wid' => 'WID'], self::FORM_DATA);
        $this->request->setMethod('POST');

        $entity = new ProductStub();

        $this->form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(FormProcessEvent::class), Events::BEFORE_FORM_DATA_SET],
                [$this->isInstanceOf(FormProcessEvent::class), Events::BEFORE_FORM_SUBMIT]
            )
            ->willReturnCallback(function (FormProcessEvent $event) use ($entity) {
                $this->assertSame($this->form, $event->getForm());
                $this->assertSame($entity, $event->getData());
            });

        $expected = $this->getExpectedSaveData($this->form, $entity);

        $result = $this->handler->handleUpdate(
            $entity,
            $this->form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testUpdateWithInvalidForm()
    {
        $this->request->initialize(['_wid' => 'WID']);
        $this->request->setMethod('POST');

        $entity = new ProductStub();

        $this->form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $this->form->expects($this->once())
            ->method('submit');
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(FormProcessEvent::class), Events::BEFORE_FORM_DATA_SET],
                [$this->isInstanceOf(FormProcessEvent::class), Events::BEFORE_FORM_SUBMIT]
            )
            ->willReturnCallback(function (FormProcessEvent $event) use ($entity) {
                $this->assertSame($this->form, $event->getForm());
                $this->assertSame($entity, $event->getData());
            });

        $expected = $this->getExpectedSaveData($this->form, $entity);

        $result = $this->handler->update($entity, $this->form, 'Saved');
        $this->assertEquals($expected, $result);
    }

    public function testHandleUpdateWorksWithValidForm()
    {
        $this->request->initialize(['_wid' => 'WID'], self::FORM_DATA);
        $this->request->setMethod('POST');

        $entity = new ProductStub();

        $this->form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $this->getSubForm()],
                ['removeRelated', $this->getSubForm()],
            ]);
        $this->form->expects($this->any())
            ->method('getErrors')
            ->willReturn(new FormErrorIterator($this->form, []));
        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('persist')
            ->with($entity);
        $em->expects($this->once())
            ->method('flush');
        $em->expects($this->once())
            ->method('commit');
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($entity)
            ->willReturn($em);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn(1);

        $this->eventDispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(FormProcessEvent::class), Events::BEFORE_FORM_DATA_SET],
                [$this->isInstanceOf(FormProcessEvent::class), Events::BEFORE_FORM_SUBMIT],
                [$this->isInstanceOf(AfterFormProcessEvent::class), Events::BEFORE_FLUSH],
                [$this->isInstanceOf(AfterFormProcessEvent::class), Events::AFTER_FLUSH]
            )
            ->willReturnCallback(function (FormProcessEvent|AfterFormProcessEvent $event) use ($entity) {
                $this->assertSame($this->form, $event->getForm());
                $this->assertSame($entity, $event->getData());
            });

        $expected = $this->getExpectedSaveData($this->form, $entity);
        $expected['savedId'] = 1;

        $result = $this->handler->handleUpdate(
            $entity,
            $this->form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testUpdateWorksWithValidForm()
    {
        $this->request->initialize(['_wid' => 'WID'], self::FORM_DATA);
        $this->request->setMethod('POST');

        $entity = new ProductStub();

        $this->form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $this->getSubForm()],
                ['removeRelated', $this->getSubForm()],
            ]);
        $this->form->expects($this->any())
            ->method('getErrors')
            ->willReturn(new FormErrorIterator($this->form, []));
        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('persist')
            ->with($entity);
        $em->expects($this->once())
            ->method('flush');
        $em->expects($this->once())
            ->method('commit');
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($entity)
            ->willReturn($em);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn(1);

        $this->eventDispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(FormProcessEvent::class), Events::BEFORE_FORM_DATA_SET],
                [$this->isInstanceOf(FormProcessEvent::class), Events::BEFORE_FORM_SUBMIT],
                [$this->isInstanceOf(AfterFormProcessEvent::class), Events::BEFORE_FLUSH],
                [$this->isInstanceOf(AfterFormProcessEvent::class), Events::AFTER_FLUSH]
            )
            ->willReturnCallback(function (FormProcessEvent|AfterFormProcessEvent $event) use ($entity) {
                $this->assertSame($this->form, $event->getForm());
                $this->assertSame($entity, $event->getData());
            });

        $expected = $this->getExpectedSaveData($this->form, $entity);
        $expected['savedId'] = 1;

        $result = $this->handler->update($entity, $this->form, 'Saved');
        $this->assertEquals($expected, $result);
    }

    public function testHandleUpdateWorksWhenFormFlushFailed()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test flush exception');

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');

        $entity = new ProductStub();

        $this->form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('persist')
            ->with($entity);
        $em->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception('Test flush exception'));
        $em->expects($this->once())
            ->method('rollback');
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($entity)
            ->willReturn($em);

        $this->handler->handleUpdate(
            $entity,
            $this->form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
    }

    public function testUpdateWorksWhenFormFlushFailed()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test flush exception');

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');

        $entity = new ProductStub();

        $this->form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('persist')
            ->with($entity);
        $em->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception('Test flush exception'));
        $em->expects($this->once())
            ->method('rollback');
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($entity)
            ->willReturn($em);

        $this->handler->update($entity, $this->form, 'Saved');
    }

    public function testHandleUpdateBeforeFormDataSetInterrupted()
    {
        $this->request->initialize(['_wid' => 'WID']);

        $entity = new ProductStub();

        $this->form->expects($this->never())
            ->method('submit');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(FormProcessEvent::class), Events::BEFORE_FORM_DATA_SET)
            ->willReturnCallback(function (FormProcessEvent $event) {
                $event->interruptFormProcess();
            });

        $expected = $this->getExpectedSaveData($this->form, $entity);

        $result = $this->handler->handleUpdate(
            $entity,
            $this->form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testHandleUpdateInterruptedBeforeFormSubmit()
    {
        $this->request->initialize(['_wid' => 'WID']);
        $this->request->setMethod('POST');

        $entity = new ProductStub();

        $this->form->expects($this->never())
            ->method('submit');

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(FormProcessEvent::class), Events::BEFORE_FORM_DATA_SET],
                [$this->isInstanceOf(FormProcessEvent::class), Events::BEFORE_FORM_SUBMIT]
            )
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (FormProcessEvent $event) {
                }),
                new ReturnCallback(function (FormProcessEvent $event) {
                    $event->interruptFormProcess();
                })
            );

        $expected = $this->getExpectedSaveData($this->form, $entity);

        $result = $this->handler->handleUpdate(
            $entity,
            $this->form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testUpdateInterruptedBeforeFormSubmit()
    {
        $this->request->initialize(['_wid' => 'WID']);
        $this->request->setMethod('POST');

        $entity = new ProductStub();

        $this->form->expects($this->never())
            ->method('submit');

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(FormProcessEvent::class), Events::BEFORE_FORM_DATA_SET],
                [$this->isInstanceOf(FormProcessEvent::class), Events::BEFORE_FORM_SUBMIT]
            )
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (FormProcessEvent $event) {
                }),
                new ReturnCallback(function (FormProcessEvent $event) {
                    $event->interruptFormProcess();
                })
            );

        $expected = $this->getExpectedSaveData($this->form, $entity);

        $result = $this->handler->update($entity, $this->form, 'Saved');
        $this->assertEquals($expected, $result);
    }

    public function testHandleUpdateWorksWithFormHandler()
    {
        $this->request->initialize(['_wid' => 'WID']);

        $entity = new ProductStub();

        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $this->getSubForm()],
                ['removeRelated', $this->getSubForm()],
            ]);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn(1);

        $expected = $this->getExpectedSaveData($this->form, $entity);
        $expected['savedId'] = 1;

        $result = $this->handler->handleUpdate(
            $entity,
            $this->form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved',
            $this->getHandlerStub($entity)
        );
        $this->assertEquals($expected, $result);
    }

    public function testHandleUpdateWorksWithRouteCallback()
    {
        $this->request->initialize(['_wid' => 'WID']);

        $entity = new ProductStub();

        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $this->getSubForm()],
                ['removeRelated', $this->getSubForm()],
            ]);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn(1);

        $saveAndStayRoute = ['route' => 'test_update'];
        $saveAndCloseRoute = ['route' => 'test_view'];
        $saveAndStayCallback = function () use ($saveAndStayRoute) {
            return $saveAndStayRoute;
        };
        $saveAndCloseCallback = function () use ($saveAndCloseRoute) {
            return $saveAndCloseRoute;
        };

        $expectedForm = $this->createMock(FormInterface::class);
        $expected = $this->getExpectedSaveData($this->form, $entity);
        $expected['savedId'] = 1;
        $expected['test'] = 1;
        $expected['form'] = $expectedForm;

        $result = $this->handler->handleUpdate(
            $entity,
            $this->form,
            $saveAndStayCallback,
            $saveAndCloseCallback,
            'Saved',
            $this->getHandlerStub($entity),
            $this->getResultCallback($expectedForm)
        );
        $this->assertTrue($this->resultCallbackInvoked);
        $this->assertEquals($expected, $result);
    }

    public function testHandleUpdateWorksWithoutWid()
    {
        $queryParameters = ['qwe' => 'rty'];
        $this->request->query = new ParameterBag($queryParameters);

        $message = 'Saved';

        $entity = new ProductStub();

        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $this->getSubForm()],
                ['removeRelated', $this->getSubForm()],
            ]);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with('success', $message);
        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $saveAndStayRoute = ['route' => 'test_update'];
        $saveAndCloseRoute = ['route' => 'test_view'];
        $expected = ['redirect' => true];
        $this->router->expects($this->once())
            ->method('redirectAfterSave')
            ->with(
                array_merge($saveAndStayRoute, ['parameters' => $queryParameters]),
                array_merge($saveAndCloseRoute, ['parameters' => $queryParameters]),
                $entity
            )
            ->willReturn($expected);

        $result = $this->handler->handleUpdate(
            $entity,
            $this->form,
            $saveAndStayRoute,
            $saveAndCloseRoute,
            $message,
            $this->getHandlerStub($entity)
        );
        $this->assertEquals($expected, $result);
    }

    public function testUpdateWorksWithoutWid()
    {
        $this->request->query = new ParameterBag(['qwe' => 'rty']);

        $message = 'Saved';

        $entity = new ProductStub();

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with('success', $message);
        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $redirectResponse = new \stdClass();
        $this->router->expects($this->once())
            ->method('redirect')
            ->with($entity)
            ->willReturn($redirectResponse);

        $actual = $this->handler->update($entity, $this->form, $message, $this->getHandlerStub($entity));
        $this->assertEquals($redirectResponse, $actual);
    }

    public function testUpdateWorksWithoutFormHandler()
    {
        $this->request->initialize(['_wid' => 'WID']);

        $entity = new ProductStub();

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn(1);

        $expected = $this->getExpectedSaveData($this->form, $entity);
        $expected['savedId'] = 1;

        $result = $this->handler->update($entity, $this->form, 'Saved', $this->getHandlerStub($entity));
        $this->assertEquals($expected, $result);
    }

    public function testUpdateWorksWithoutFormHandlerAndWithResultCallback()
    {
        $this->request->initialize(['_wid' => 'WID']);

        $entity = new ProductStub();

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn(1);

        $expected = $this->getExpectedSaveData($this->form, $entity);
        $expected['savedId'] = 1;
        $expected['form'] = $this->form;
        $expected['test'] = 1;

        $result = $this->handler->update(
            $entity,
            $this->form,
            'Saved',
            $this->getHandlerStub($entity),
            $this->getResultCallback($this->form)
        );
        $this->assertEquals($expected, $result);
    }

    public function testSaveAndDuplicate()
    {
        $queryParameters = ['qwe' => 'rty'];
        $this->request->initialize(
            $queryParameters,
            [Router::ACTION_PARAMETER => ProductUpdateHandler::ACTION_SAVE_AND_DUPLICATE]
        );
        $this->request->setMethod('POST');

        $message = 'Saved';
        $savedAndDuplicatedMessage = 'Saved and duplicated';

        $entity = $this->getProduct(0);

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $this->getSubForm()],
                ['removeRelated', $this->getSubForm()],
            ]);
        $form->expects($this->any())
            ->method('getErrors')
            ->willReturn(new FormErrorIterator($form, []));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($entity)
            ->willReturn($this->entityManager);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with('success', $message);
        $flashBag->expects($this->once())
            ->method('set')
            ->with('success', $savedAndDuplicatedMessage);
        $this->session->expects($this->any())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $saveAndStayRoute = ['route' => 'test_update'];
        $saveAndCloseRoute = ['route' => 'test_view'];

        $this->router->expects($this->once())
            ->method('redirectAfterSave')
            ->with(
                array_merge($saveAndStayRoute, ['parameters' => $queryParameters]),
                array_merge($saveAndCloseRoute, ['parameters' => $queryParameters]),
                $entity
            )
            ->willReturn(new RedirectResponse('test_url'));

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.product.controller.product.saved_and_duplicated.message')
            ->willReturn($savedAndDuplicatedMessage);

        /** @var \PHPUnit\Framework\MockObject\MockObject|ActionGroup $actionGroup */
        $actionGroup = $this->createMock(ActionGroup::class);

        $actionGroup->expects($this->once())
            ->method('execute')
            ->with(new ActionData(['data' => $entity]))
            ->willReturn(new ActionData(['productCopy' => $this->getProduct()]));

        $this->actionGroupRegistry->expects($this->once())
            ->method('findByName')
            ->with('oro_product_duplicate')
            ->willReturn($actionGroup);

        $result = $this->handler->handleUpdate(
            $entity,
            $form,
            $saveAndStayRoute,
            $saveAndCloseRoute,
            $message
        );

        $this->assertEquals('generated_redirect_url', $result->headers->get('location'));
        $this->assertEquals(302, $result->getStatusCode());
    }

    public function testBlankDataNoHandler()
    {
        $this->request->initialize(['_wid' => 'WID']);

        $entity = $this->getProduct(0);

        $form = $this->createMock(Form::class);

        $expected = $this->assertSaveData($form, $entity);

        $result = $this->handler->handleUpdate(
            $entity,
            $form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testSaveHandler()
    {
        $this->request->initialize(['_wid' => 'WID']);

        $entity = $this->getProduct(0);

        $form = $this->createMock(Form::class);
        $form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $this->getSubForm()],
                ['removeRelated', $this->getSubForm()],
            ]);

        $handler = $this->createMock(HandlerStub::class);
        $handler->expects($this->once())
            ->method('process')
            ->with($entity)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn(1);

        $expected = $this->assertSaveData($form, $entity);
        $expected['savedId'] = 1;

        $this->relatedItemsHandler->expects($this->never())
            ->method('process');

        $result = $this->handler->handleUpdate(
            $entity,
            $form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved',
            $handler
        );
        $this->assertEquals($expected, $result);
    }

    public function testSaveHandlerAddRelatedProducts()
    {
        $this->request->initialize(['_wid' => 'WID']);

        $entity = $this->getProduct(0);
        $relatedEntity = $this->getProduct(0);

        $appendRelatedProductsField = $this->getSubForm([$relatedEntity]);
        $removeRelatedProductsField = $this->getSubForm();

        $form = $this->prepareAppendedFields($appendRelatedProductsField, $removeRelatedProductsField, $entity);

        $handler = new ProductUpdateHandler(
            $this->requestStack,
            $this->session,
            $this->router,
            $this->doctrineHelper,
            $this->getFormHandlerStub($entity)
        );
        $handler->setRelatedItemsHandler($this->relatedItemsHandler);

        $expected = $this->assertSaveData($form, $entity);
        $expected['savedId'] = 1;

        $result = $handler->handleUpdate(
            $entity,
            $form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testSaveHandlerRemoveRelatedProducts()
    {
        $this->request->initialize(['_wid' => 'WID']);

        $entity = $this->getProduct(0);
        $relatedEntity = $this->getProduct(0);

        $appendRelatedProductsField = $this->getSubForm();
        $removeRelatedProductsField = $this->getSubForm([$relatedEntity]);

        $form = $this->prepareAppendedFields($appendRelatedProductsField, $removeRelatedProductsField, $entity);

        $expected = $this->assertSaveData($form, $entity);
        $expected['savedId'] = 1;

        $handler = new ProductUpdateHandler(
            $this->requestStack,
            $this->session,
            $this->router,
            $this->doctrineHelper,
            $this->getFormHandlerStub($entity)
        );
        $handler->setRelatedItemsHandler($this->relatedItemsHandler);

        $result = $handler->handleUpdate(
            $entity,
            $form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    public function testSaveHandlerAddRelatedProductsFails()
    {
        $this->request->initialize(['_wid' => 'WID']);

        $entity = $this->getProduct(0);
        $relatedEntity = $this->getProduct(0);

        $appendRelatedProductsField = $this->getSubForm([$relatedEntity]);
        $removeRelatedProductsField = $this->getSubForm();

        $form = $this->getFormThatReturnsNoErrors($appendRelatedProductsField, $removeRelatedProductsField);

        $this->doctrineHelper->expects($this->never())
            ->method('getSingleEntityIdentifier');

        $this->relatedItemsHandler->expects($this->once())
            ->method('process')
            ->with(
                RelatedItemsHandler::RELATED_PRODUCTS,
                $entity,
                $appendRelatedProductsField,
                $removeRelatedProductsField
            )
            ->willReturn(false);

        $handler = new ProductUpdateHandler(
            $this->requestStack,
            $this->session,
            $this->router,
            $this->doctrineHelper,
            $this->getFormHandlerStub($entity)
        );
        $handler->setRelatedItemsHandler($this->relatedItemsHandler);
        $handler->setTranslator($this->translator);

        $expected = $this->assertSaveData($form, $entity);

        $result = $handler->handleUpdate(
            $entity,
            $form,
            ['route' => 'test_update'],
            ['route' => 'test_view'],
            'Saved'
        );
        $this->assertEquals($expected, $result);
    }

    private function assertSaveData(
        FormInterface|\PHPUnit\Framework\MockObject\MockObject $form,
        Product $entity
    ): array {
        $formView = $this->createMock(FormView::class);
        $form->expects($this->any())
            ->method('createView')
            ->willReturn($formView);

        return [
            'entity' => $entity,
            'form'   => $formView,
            'isWidgetContext' => true
        ];
    }

    private function getProduct(int $getIdCalls = 1): Product
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->exactly($getIdCalls))
            ->method('getId')
            ->willReturn(self::PRODUCT_ID);

        return $product;
    }

    private function getResultCallback(FormInterface $expectedForm): \Closure
    {
        return function () use ($expectedForm) {
            $this->resultCallbackInvoked = true;
            return ['form' => $expectedForm, 'test' => 1];
        };
    }

    private function getHandlerStub(Product $entity): HandlerStub
    {
        $handler = $this->createMock(HandlerStub::class);
        $handler->expects($this->once())
            ->method('process')
            ->with($entity)
            ->willReturn(true);

        return $handler;
    }

    private function getFormHandlerStub(Product $entity): FormHandler
    {
        $formHandler = $this->createMock(FormHandler::class);
        $formHandler->expects($this->once())
            ->method('process')
            ->with($entity)
            ->willReturn(true);

        return $formHandler;
    }

    private function getExpectedSaveData(
        FormInterface|\PHPUnit\Framework\MockObject\MockObject $form,
        Product $entity
    ): array {
        $formView = $this->createMock(FormView::class);
        $form->expects($this->any())
            ->method('createView')
            ->willReturn($formView);

        return [
            'entity' => $entity,
            'form' => $formView,
            'isWidgetContext' => true
        ];
    }

    private function getSubForm(array $data = []): FormInterface
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        return $form;
    }

    private function getFormThatReturnsNoErrors(
        FormInterface $appendRelatedSubForm,
        FormInterface $removeRelatedSubForm
    ): FormInterface|\PHPUnit\Framework\MockObject\MockObject {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $appendRelatedSubForm],
                ['removeRelated', $removeRelatedSubForm],
            ]);
        $form->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['appendRelated', true],
                ['removeRelated', true],
            ]);

        return $form;
    }

    private function prepareAppendedFields(
        FormInterface $appendRelatedProductsField,
        FormInterface $removeRelatedProductsField,
        Product $entity
    ): FormInterface|\PHPUnit\Framework\MockObject\MockObject {
        $form = $this->createMock(Form::class);
        $form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $appendRelatedProductsField],
                ['removeRelated', $removeRelatedProductsField],
            ]);
        $form->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['appendRelated', true],
                ['removeRelated', true],
            ]);
        $form->expects($this->any())
            ->method('getErrors')
            ->willReturn(new FormErrorIterator($form, []));

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn(1);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($entity)
            ->willReturn($this->entityManager);

        $this->relatedItemsHandler->expects($this->once())
            ->method('process')
            ->with(
                RelatedItemsHandler::RELATED_PRODUCTS,
                $entity,
                $appendRelatedProductsField,
                $removeRelatedProductsField
            )
            ->willReturn(true);

        return $form;
    }
}
