<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Route\Router;

use OroB2B\Bundle\ProductBundle\Form\Handler\ProductUpdateHandler;

class ProductUpdateHandlerTest extends \PHPUnit_Framework_TestCase
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
     * @var \PHPUnit_Framework_MockObject_MockObject|UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @var ProductUpdateHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $this->router = $this->getMockBuilder('Oro\Bundle\UIBundle\Route\Router')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlGenerator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGeneratorInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new ProductUpdateHandler(
            $this->request,
            $this->session,
            $this->router,
            $this->doctrineHelper,
            $this->eventDispatcher
        );
        $this->handler->setTranslator($this->translator);
        $this->handler->setUrlGenerator($this->urlGenerator);
    }

    /**
     * @param bool $getIdShouldBeCalled
     * @return object
     */
    protected function getProductMock($getIdShouldBeCalled = true)
    {
        $product = $this->getMock('OroB2B\Bundle\ProductBundle\Entity\Product');

        if ($getIdShouldBeCalled) {
            $product->expects($this->once())
                ->method('getId')
                ->will($this->returnValue(self::PRODUCT_ID));
        }

        return $product;
    }

    public function testSaveAndDuplicate()
    {
        $entity = $this->getProductMock();
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

        $this->doctrineHelper->expects($this->once())
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
            ->with('orob2b.product.controller.product.saved_and_duplicated.message')
            ->will($this->returnValue($savedAndDuplicatedMessage));
        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('orob2b_product_duplicate', ['id' => self::PRODUCT_ID])
            ->will($this->returnValue('generated_redirect_url'));

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
        $entity = $this->getProductMock(false);
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
        $entity = $this->getProductMock(false);

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
}
