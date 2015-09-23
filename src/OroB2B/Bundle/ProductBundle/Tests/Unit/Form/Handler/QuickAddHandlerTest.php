<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Form\Handler\QuickAddHandler;
use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorRegistry;
use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorInterface;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddHandlerTest extends \PHPUnit_Framework_TestCase
{
    const COMPONENT_NAME = 'component';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ComponentProcessorRegistry
     */
    protected $componentRegistry;

    /**
     * @var QuickAddHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($message) {
                    return $message . '.trans';
                }
            );

        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $this->componentRegistry = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Model\ComponentProcessorRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new QuickAddHandler($this->translator, $this->formFactory, $this->componentRegistry);
    }

    public function testProcessGetRequest()
    {
        $request = Request::create('/get');

        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(QuickAddType::NAME, null, [])
            ->willReturn($form);

        $this->assertEquals(['form' => $form, 'response' => null], $this->handler->process($request));
    }

    public function testProcessNoProcessor()
    {
        $request = Request::create('/post/no-processor', 'POST');
        $request->setSession($this->getSessionWithErrorMessage());

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('submit')
            ->with($request);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(QuickAddType::NAME, null, [])
            ->willReturn($form);

        $this->assertEquals(['form' => $form, 'response' => null], $this->handler->process($request));
    }

    public function testProcessNotAllowedProcessor()
    {
        $request = Request::create('/post/not-allowed-processor', 'POST');
        $request->request->set(QuickAddType::NAME, [QuickAddType::COMPONENT_FIELD_NAME => self::COMPONENT_NAME]);
        $request->setSession($this->getSessionWithErrorMessage());

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('submit')
            ->with($request);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(QuickAddType::NAME, null, ['validation_required' => false])
            ->willReturn($form);

        $processor = $this->getProcessor(false, false);
        $processor->expects($this->never())
            ->method('process');

        $this->assertEquals(['form' => $form, 'response' => null], $this->handler->process($request));
    }

    public function testProcessInvalidForm()
    {
        $request = Request::create('/post/invalid-form', 'POST');
        $request->request->set(QuickAddType::NAME, [QuickAddType::COMPONENT_FIELD_NAME => self::COMPONENT_NAME]);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('submit')
            ->with($request);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(QuickAddType::NAME, null, ['validation_required' => true])
            ->willReturn($form);

        $processor = $this->getProcessor();
        $processor->expects($this->never())
            ->method('process');

        $this->assertEquals(['form' => $form, 'response' => null], $this->handler->process($request));
    }

    public function testProcessValidDataWithoutResponse()
    {
        $request = Request::create('/post/valid-without-response', 'POST');
        $request->request->set(QuickAddType::NAME, [QuickAddType::COMPONENT_FIELD_NAME => self::COMPONENT_NAME]);

        $products = [['sku' => '111', 'qty' => 123], ['sku' => '222', 'qty' => 234]];

        $productsForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $productsForm->expects($this->once())
            ->method('getData')
            ->willReturn($products);

        $mainForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $mainForm->expects($this->once())
            ->method('submit')
            ->with($request);
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $mainForm->expects($this->once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($productsForm);

        $clearForm = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->formFactory->expects($this->at(0))
            ->method('create')
            ->with(QuickAddType::NAME, null, ['validation_required' => true])
            ->willReturn($mainForm);
        $this->formFactory->expects($this->at(1))
            ->method('create')
            ->with(QuickAddType::NAME, null, ['validation_required' => true])
            ->willReturn($clearForm);

        $processor = $this->getProcessor();
        $processor->expects($this->once())
            ->method('process')
            ->with([ProductDataStorage::ENTITY_ITEMS_DATA_KEY => $products], $request);

        $this->assertEquals(['form' => $clearForm, 'response' => null], $this->handler->process($request));
    }

    public function testProcessValidDataWithResponse()
    {
        $request = Request::create('/post/valid-with-response', 'POST');
        $request->request->set(QuickAddType::NAME, [QuickAddType::COMPONENT_FIELD_NAME => self::COMPONENT_NAME]);

        $response = new RedirectResponse('/processor-redirect');

        $products = [['sku' => '111', 'qty' => 123], ['sku' => '222', 'qty' => 234]];

        $productsForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $productsForm->expects($this->once())
            ->method('getData')
            ->willReturn($products);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('submit')
            ->with($request);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('get')
            ->with(QuickAddType::PRODUCTS_FIELD_NAME)
            ->willReturn($productsForm);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(QuickAddType::NAME, null, ['validation_required' => true])
            ->willReturn($form);

        $processor = $this->getProcessor();
        $processor->expects($this->once())
            ->method('process')
            ->with([ProductDataStorage::ENTITY_ITEMS_DATA_KEY => $products], $request)
            ->willReturn($response);

        $this->assertEquals(['form' => $form, 'response' => $response], $this->handler->process($request));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected function getSessionWithErrorMessage()
    {
        $flashBag = $this->getMock('Symfony\Component\HttpFoundation\Session\Flash\FlashBag');
        $flashBag->expects($this->once())
            ->method('add')
            ->with('error', 'orob2b.product.frontend.component_not_accessible.message.trans');

        $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects($this->any())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        return $session;
    }

    /**
     * @param bool $isValidationRequired
     * @param bool $isAllowed
     * @return \PHPUnit_Framework_MockObject_MockObject|ComponentProcessorInterface
     */
    protected function getProcessor($isValidationRequired = true, $isAllowed = true)
    {
        $processor = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ComponentProcessorInterface');
        $processor->expects($this->any())
            ->method('isValidationRequired')
            ->willReturn($isValidationRequired);
        $processor->expects($this->any())
            ->method('isAllowed')
            ->willReturn($isAllowed);

        $this->componentRegistry->expects($this->once())
            ->method('getProcessorByName')
            ->with(self::COMPONENT_NAME)
            ->willReturn($processor);

        return $processor;
    }
}
