<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use OroB2B\Bundle\ProductBundle\Form\Handler\QuickAddImportHandler;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddImportHandlerTest extends \PHPUnit_Framework_TestCase
{
    const QUICK_ADD_URL = 'http://quick-add.com';
    const COMPONENT_NAME = 'component';
    const SHOPPING_LIST_ID = 1;

    /**
     * @var array
     */
    static protected $products = ['products'];

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ComponentProcessorRegistry
     */
    protected $componentRegistry;

    /**
     * @var QuickAddImportHandler
     */
    protected $handler;


    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->urlGenerator = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $this->urlGenerator->expects($this->any())
            ->method('generate')
            ->with('orob2b_product_frontend_quick_add')
            ->willReturn(self::QUICK_ADD_URL);

        $this->componentRegistry = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new QuickAddImportHandler($this->translator, $this->urlGenerator, $this->componentRegistry);
    }

    public function testRedirectsToQuickAddPageIfNoProductsSubmitted()
    {
        $response = $this->handler->process($this->prepareRequest());

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertEquals(self::QUICK_ADD_URL, $response->getTargetUrl());
    }

    public function testRedirectsToQuickAddPageIfProcessorNotAvailable()
    {
        $this->componentRegistry->expects($this->once())
            ->method('getProcessorByName')
            ->with(self::COMPONENT_NAME)
            ->willReturn(null);

        $response = $this->handler->process($this->prepareRequest(self::$products, self::COMPONENT_NAME));

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertEquals(self::QUICK_ADD_URL, $response->getTargetUrl());
    }

    public function testRedirectsToAppropriateUrl()
    {
        $response = new RedirectResponse(self::QUICK_ADD_URL);
        $request = $this->prepareRequest(
            self::$products,
            self::COMPONENT_NAME,
            self::SHOPPING_LIST_ID
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|ComponentProcessorInterface $processor */
        $processor = $this->prepareProcessor($request, $response, false);

        $this->componentRegistry->expects($this->once())
            ->method('getProcessorByName')
            ->with(self::COMPONENT_NAME)
            ->willReturn($processor);

        $response = $this->handler->process($request);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertEquals(self::QUICK_ADD_URL, $response->getTargetUrl());
    }

    public function testRedirectsToQuickAddUrlWhenProcessorIsNowAllowed()
    {
        $response = new RedirectResponse(self::QUICK_ADD_URL);
        $request = $this->prepareRequest(
            self::$products,
            self::COMPONENT_NAME,
            self::SHOPPING_LIST_ID
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|ComponentProcessorInterface $processor */
        $processor = $this->prepareProcessor($request, $response, false);

        $this->componentRegistry->expects($this->once())
            ->method('getProcessorByName')
            ->with(self::COMPONENT_NAME)
            ->willReturn($processor);

        $response = $this->handler->process($request);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertEquals(self::QUICK_ADD_URL, $response->getTargetUrl());
    }

    /**
     * @param array $products
     * @param string|null $componentName
     * @param null|int $shoppingListId
     * @param bool $hasFlashMessages
     * @return Request
     */
    private function prepareRequest(
        array $products = [],
        $componentName = null,
        $shoppingListId = null,
        $hasFlashMessages = true
    ) {
        $flashBag = $this->getMock('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface');

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\Session');

        if ($hasFlashMessages) {
            $session->expects($this->any())
                ->method('getFlashBag')
                ->willReturn($flashBag);
        }

        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->any())
            ->method('getSession')
            ->willReturn($session);

        $request->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [sprintf('%s[%s]', QuickAddType::NAME, QuickAddType::PRODUCTS_FIELD_NAME), [], true, $products],
                [
                    sprintf('%s[%s]', QuickAddType::NAME, QuickAddType::ADDITIONAL_FIELD_NAME),
                    0,
                    true,
                    $shoppingListId
                ],
                [QuickAddType::NAME, [], false, [QuickAddType::COMPONENT_FIELD_NAME => $componentName]]
            ]);

        return $request;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param bool $isAllowed
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareProcessor(Request $request, Response $response, $isAllowed = true)
    {
        $processor = $this->getMock('OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface');
        $processor->expects($this->any())
            ->method('process')
            ->with(
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => self::$products,
                    ProductDataStorage::ADDITIONAL_DATA_KEY => [
                        ProductDataStorage::SHOPPING_LIST_ID_KEY => self::SHOPPING_LIST_ID
                    ]
                ],
                $request
            )->willReturn($response);
        $processor->expects($this->once())
            ->method('isAllowed')
            ->willReturn($isAllowed);

        return $processor;
    }
}
