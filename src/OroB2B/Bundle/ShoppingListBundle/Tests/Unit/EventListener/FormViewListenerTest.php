<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\EventListener\FormViewListener;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;

class FormViewListenerTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_CLASS = 'OroB2BProductBundle:Product';
    const SHOPPING_LIST_CLASS = 'OroB2BShoppingListBundle:ShoppingList';

    /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject $tokenStorage */
    protected $tokenStorage;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject $doctrineHelper */
    protected $doctrineHelper;

    /** @var FormViewListener */
    protected $listener;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $formFactory */
    protected $formFactory;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject $translator */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject $translator */
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject $doctrineHelper */
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject $tokenStorage */
        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $formFactory */
        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $this->listener = new FormViewListener(
            $this->translator,
            $this->tokenStorage,
            $this->formFactory,
            $this->doctrineHelper
        );
    }

    public function testOnFrontendProductView()
    {
        $productId = 1;
        $this->tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($this->getToken());

        $accountUser = $this->tokenStorage->getToken()->getUser();

        /** @var \PHPUnit_Framework_MockObject_MockObject|Product $product */
        $product = $this->getMock('OroB2B\Bundle\ProductBundle\Entity\Product');

        $request = $this->getRequest();
        $request->expects($this->once())
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

        $event = $this->getBeforeListRenderEvent();

        $event->expects($this->once())
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

        $event->expects($this->once())
            ->method('getScrollData')
            ->willReturn($scrollData);
        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn('');

        $this->listener->setRequest($request);
        $this->listener->onFrontendProductView($event);
    }

    public function testOnFrontendProductViewNoUser()
    {
        $request = $this->getRequest();
        $request
            ->expects($this->never())
            ->method('get');

        $this->listener->setRequest($request);
        $event = $this->getBeforeListRenderEvent();

        $this->listener->onFrontendProductView($event);
    }

    public function testOnFrontendProductViewInvalidId()
    {
        $this->tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($this->getToken());

        $event = $this->getBeforeListRenderEvent();

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityReference');

        $this->listener->onFrontendProductView($event);

        $request = $this->getRequest();
        $request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn('string');

        $this->listener->setRequest($request);
        $this->listener->onFrontendProductView($event);
    }

    public function testOnProductViewEmptyProduct()
    {
        $this->tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($this->getToken());

        $event = $this->getBeforeListRenderEvent();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(null);

        $request = $this->getRequest();
        $request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityRepository')
            ->willReturn(null);

        $this->listener->setRequest($request);
        $this->listener->onFrontendProductView($event);
    }

    /**
     * @return Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRequest()
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        return $request;
    }

    /**
     * @return TokenInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getToken()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $accountUser */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|AccountUser $accountUser */
        $accountUser = $this->getMockBuilder('OroB2B\Bundle\CustomerBundle\Entity\AccountUser')
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($accountUser);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Organization $organization */
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');

        $accountUser->expects($this->any())
            ->method('getOrganization')
            ->willReturn($organization);

        return $token;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent
     */
    protected function getBeforeListRenderEvent()
    {
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        return $event;
    }
}
