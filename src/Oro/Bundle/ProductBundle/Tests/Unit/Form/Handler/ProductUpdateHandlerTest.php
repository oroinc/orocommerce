<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Handler\FormHandler;
use Oro\Bundle\FormBundle\Tests\Unit\Model\UpdateHandlerTest;
use Oro\Bundle\ProductBundle\Form\Handler\ProductUpdateHandler;
use Oro\Bundle\ProductBundle\Form\Handler\RelatedItemsHandler;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Bundle\FrameworkBundle\Routing\Router as SymfonyRouter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductUpdateHandlerTest extends UpdateHandlerTest
{
    const PRODUCT_ID = 1;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected $session;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Router
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ActionGroupRegistry
     */
    protected $actionGroupRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RelatedItemsHandler */
    protected $relatedItemsHandler;

    /**
     * @var ProductUpdateHandler
     */
    protected $handler;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->actionGroupRegistry = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroupRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \PHPUnit_Framework_MockObject_MockObject|SymfonyRouter $symfonyRouter */
        $symfonyRouter = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();
        $symfonyRouter
            ->expects($this->any())
            ->method('generate')
            ->willReturn('generated_redirect_url');

        $this->relatedItemsHandler = $this->getMockBuilder(RelatedItemsHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new ProductUpdateHandler(
            $this->requestStack,
            $this->session,
            $this->router,
            $this->doctrineHelper,
            $this->formHandler
        );
        $this->handler->setTranslator($this->translator);
        $this->handler->setActionGroupRegistry($this->actionGroupRegistry);
        $this->handler->setRouter($symfonyRouter);
        $this->handler->setRelatedItemsHandler($this->relatedItemsHandler);
    }

    /**
     * @param int $getIdCalls
     * @return object
     */
    protected function getProductMock($getIdCalls = 1)
    {
        $product = $this->createMock('Oro\Bundle\ProductBundle\Entity\Product');
        $product->expects($this->exactly($getIdCalls))
            ->method('getId')
            ->will($this->returnValue(self::PRODUCT_ID));

        return $product;
    }

    public function testSaveAndDuplicate()
    {
        $entity = $this->getProductMock(0);
        $queryParameters = ['qwe' => 'rty'];
        $this->request->query = new ParameterBag($queryParameters);
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $this->request->expects($this->at(1))
            ->method('get')
            ->with($this->anything())
            ->will($this->returnValue(false));
        $this->request->expects($this->at(2))
            ->method('get')
            ->with(Router::ACTION_PARAMETER)
            ->will($this->returnValue(ProductUpdateHandler::ACTION_SAVE_AND_DUPLICATE));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($entity)
            ->will($this->returnValue($this->entityManager));

        $message = 'Saved';
        $savedAndDuplicatedMessage = 'Saved and duplicated';

        /** @var \PHPUnit_Framework_MockObject_MockObject|Form $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));
        $form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $this->getSubForm()],
                ['removeRelated', $this->getSubForm()],
            ]);
        $form->expects($this->any())
            ->method('getErrors')
            ->willReturn(new FormErrorIterator($form, []));

        $flashBag = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface')
            ->getMock();
        $flashBag->expects($this->once())
            ->method('add')
            ->with('success', $message);
        $flashBag->expects($this->once())
            ->method('set')
            ->with('success', $savedAndDuplicatedMessage);
        $this->session->expects($this->any())
            ->method('getFlashBag')
            ->will($this->returnValue($flashBag));

        $saveAndStayRoute = ['route' => 'test_update'];
        $saveAndCloseRoute = ['route' => 'test_view'];

        $this->router->expects($this->once())
            ->method('redirectAfterSave')
            ->with(
                array_merge($saveAndStayRoute, ['parameters' => $queryParameters]),
                array_merge($saveAndCloseRoute, ['parameters' => $queryParameters]),
                $entity
            )
            ->will($this->returnValue(new RedirectResponse('test_url')));

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.product.controller.product.saved_and_duplicated.message')
            ->will($this->returnValue($savedAndDuplicatedMessage));

        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionGroup $actionGroup */
        $actionGroup = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionGroup')
            ->disableOriginalConstructor()
            ->getMock();

        $actionGroup->expects($this->once())
            ->method('execute')
            ->with(new ActionData(['data' => $entity]))
            ->willReturn(new ActionData(['productCopy' => $this->getProductMock()]));

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
        /** @var \PHPUnit_Framework_MockObject_MockObject|Form $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $entity = $this->getProductMock(0);
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
        /** @var \PHPUnit_Framework_MockObject_MockObject|Form $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $this->getSubForm()],
                ['removeRelated', $this->getSubForm()],
            ]);
        $entity = $this->getProductMock(0);

        $handler = $this->getMockBuilder('Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\HandlerStub')
            ->getMock();
        $handler->expects($this->once())
            ->method('process')
            ->with($entity)
            ->will($this->returnValue(true));
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue(1));

        $expected = $this->assertSaveData($form, $entity);
        $expected['savedId'] = 1;

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
        $entity = $this->getProductMock(0);
        $relatedEntity = $this->getProductMock(0);

        $appendRelatedProductsField = $this->getSubForm([$relatedEntity]);
        $removeRelatedProductsField = $this->getSubForm();

        /** @var \PHPUnit_Framework_MockObject_MockObject|Form $form */
        $form = $this->prepareAppendedFields($appendRelatedProductsField, $removeRelatedProductsField, $entity);
        /** @var FormHandler|\PHPUnit_Framework_MockObject_MockObject $formHandlerMock */
        $formHandlerMock = $this->getFormHandlerMock($entity);

        $handler = new ProductUpdateHandler(
            $this->requestStack,
            $this->session,
            $this->router,
            $this->doctrineHelper,
            $formHandlerMock
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
        $entity = $this->getProductMock(0);
        $relatedEntity = $this->getProductMock(0);

        $appendRelatedProductsField = $this->getSubForm();
        $removeRelatedProductsField = $this->getSubForm([$relatedEntity]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Form $form */
        $form = $this->prepareAppendedFields($appendRelatedProductsField, $removeRelatedProductsField, $entity);
        /** @var FormHandler|\PHPUnit_Framework_MockObject_MockObject $formHandlerMock */
        $formHandlerMock = $this->getFormHandlerMock($entity);

        $expected = $this->assertSaveData($form, $entity);
        $expected['savedId'] = 1;

        $handler = new ProductUpdateHandler(
            $this->requestStack,
            $this->session,
            $this->router,
            $this->doctrineHelper,
            $formHandlerMock
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
        $entity = $this->getProductMock(0);
        $relatedEntity = $this->getProductMock(0);

        $appendRelatedProductsField = $this->getSubForm([$relatedEntity]);
        $removeRelatedProductsField = $this->getSubForm();

        $form = $this->getFormThatReturnsNoErrors($appendRelatedProductsField, $removeRelatedProductsField);

        /** @var FormHandler|\PHPUnit_Framework_MockObject_MockObject $formHandlerMock */
        $formHandlerMock = $this->getFormHandlerMock($entity);
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
            $formHandlerMock
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

    public function testUpdateWorksWithValidForm()
    {
        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $this->getSubForm()],
                ['removeRelated', $this->getSubForm()],
            ]);
        $this->form->expects($this->any())
            ->method('getErrors')
            ->willReturn(new FormErrorIterator($this->form, []));

        parent::testUpdateWorksWithValidForm();
    }

    public function testHandleUpdateWorksWithValidForm()
    {
        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $this->getSubForm()],
                ['removeRelated', $this->getSubForm()],
            ]);
        $this->form->expects($this->any())
            ->method('getErrors')
            ->willReturn(new FormErrorIterator($this->form, []));

        parent::testHandleUpdateWorksWithValidForm();
    }

    public function testHandleUpdateWorksWithFormHandler()
    {
        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $this->getSubForm()],
                ['removeRelated', $this->getSubForm()],
            ]);

        parent::testHandleUpdateWorksWithFormHandler();
    }

    public function testHandleUpdateWorksWithRouteCallback()
    {
        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $this->getSubForm()],
                ['removeRelated', $this->getSubForm()],
            ]);

        return parent::testHandleUpdateWorksWithRouteCallback();
    }

    public function testHandleUpdateWorksWithoutWid()
    {
        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['appendRelated', $this->getSubForm()],
                ['removeRelated', $this->getSubForm()],
            ]);

        parent::testHandleUpdateWorksWithoutWid();
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|Form $form
     * @param object $entity
     * @param string $wid
     * @return array
     */
    protected function assertSaveData($form, $entity, $wid = 'WID')
    {
        $this->request->expects($this->atLeastOnce())
            ->method('get')
            ->with('_wid', false)
            ->will($this->returnValue($wid));
        $formView = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->any())
            ->method('createView')
            ->will($this->returnValue($formView));

        return [
            'entity' => $entity,
            'form'   => $formView,
            'isWidgetContext' => true
        ];
    }

    /**
     * @return Product
     */
    protected function getObject()
    {
        return new Product;
    }

    /**
     * @param array $data
     * @return \PHPUnit_Framework_MockObject_MockObject|Form
     */
    private function getSubForm($data = [])
    {
        $form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($data));

        return $form;
    }

    /**
     * @param FormInterface $appendRelatedSubForm
     * @param FormInterface $removeRelatedSubForm
     * @return \PHPUnit_Framework_MockObject_MockObject|Form
     */
    private function getFormThatReturnsNoErrors(
        FormInterface $appendRelatedSubForm,
        FormInterface $removeRelatedSubForm
    ) {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Form $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
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

    /**
     * @param $entity
     * @return FormHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getFormHandlerMock($entity)
    {
        /** @var FormHandler|\PHPUnit_Framework_MockObject_MockObject $formHandlerMock */
        $formHandlerMock = $this->getMockBuilder(FormHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $formHandlerMock->expects($this->once())
            ->method('process')
            ->with($entity)
            ->will($this->returnValue(true));
        return $formHandlerMock;
    }

    /**
     * @param $appendRelatedProductsField
     * @param $removeRelatedProductsField
     * @param $entity
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Form
     */
    private function prepareAppendedFields($appendRelatedProductsField, $removeRelatedProductsField, $entity)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Form $form */
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
            ->will($this->returnValue(1));
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($entity)
            ->will($this->returnValue($this->entityManager));

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
