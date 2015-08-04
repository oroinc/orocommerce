<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\EventListener\FormViewListener;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;

class FormViewListenerTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_CLASS = 'OroB2BProductBundle:Product';
    const SHOPPING_LIST_CLASS = 'OroB2BShoppingListBundle:ShoppingList';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent
     */
    protected $event;

    public function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testOnFrontendProductViewNoRequest()
    {
        $listener = new FormViewListener(
            $this->translator,
            $this->tokenStorage,
            $this->formFactory,
            $this->doctrineHelper
        );
        $listener->setRequest(null);
        $this->assertFalse($listener->onFrontendProductView($this->event));
    }

    public function testOnFrontendProductViewNoUser()
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);
        $listener = new FormViewListener(
            $this->translator,
            $this->tokenStorage,
            $this->formFactory,
            $this->doctrineHelper
        );
        $listener->setRequest($this->request);
        $this->assertFalse($listener->onFrontendProductView($this->event));
    }

    public function testOnFrontendProductView()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Organization $organization */
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');

        /** @var \PHPUnit_Framework_MockObject_MockObject|AccountUser $accountUser */
        $accountUser = $this->getMock('OroB2B\Bundle\CustomerBundle\Entity\AccountUser');
        $accountUser->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($accountUser);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $productId = 1;
        /** @var \PHPUnit_Framework_MockObject_MockObject|Product $product */
        $product = $this->getMock('OroB2B\Bundle\ProductBundle\Entity\Product');
        $this->request->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn($productId);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(self::PRODUCT_CLASS, $productId)
            ->willReturn($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListRepository $repository */
        $repository = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findCurrentForAccountUser')
            ->with($accountUser)
            ->willReturn(null);
        $repository->expects($this->once())
            ->method('findAllExceptCurrentForAccountUser')
            ->with($accountUser)
            ->willReturn([]);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(self::SHOPPING_LIST_CLASS)
            ->willReturn($repository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $environment->expects($this->once())
            ->method('render')
            ->willReturn(null);
        $this->event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($environment);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('createView')
            ->willReturn(null);
        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($form);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ScrollData $scrollData */
        $scrollData = $this->getMock('Oro\Bundle\UIBundle\View\ScrollData');
        $scrollData->expects($this->once())
            ->method('addBlock')
            ->willReturn(1);
        $scrollData->expects($this->once())
            ->method('addSubBlock')
            ->willReturn(1);
        $scrollData->expects($this->once())
            ->method('addSubBlockData');
        $this->event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($scrollData);

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn('');

        $listener = new FormViewListener(
            $this->translator,
            $this->tokenStorage,
            $this->formFactory,
            $this->doctrineHelper
        );
        $listener->setRequest($this->request);
        $this->assertTrue($listener->onFrontendProductView($this->event));
    }
}
