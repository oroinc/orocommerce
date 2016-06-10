<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\EventListener\FormViewListener;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class FormViewListenerTest extends FormViewListenerTestCase
{
    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->translator);
    }

    public function testOnViewNoRequest()
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $listener = $this->getListener($requestStack);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMock('\Twig_Environment');
        $event = $this->createEvent($env);
        $listener->onAccountView($event);
        $listener->onAccountGroupView($event);
        $listener->onProductView($event);
    }

    public function testOnAccountView()
    {
        $accountId = 1;
        $account = new Account();
        $websiteId1 = 12;
        $websiteId2 = 13;
        $websiteId3 = 14;

        /** @var Website $website1 */
        $website1 = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', $websiteId1);
        /** @var Website $website2 */
        $website2 = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', $websiteId2);
        /** @var Website $website3 */
        $website3 = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', $websiteId3);
        $websites = [$website1, $website2, $website3];
        $priceListToAccount1 = new PriceListToAccount();
        $priceListToAccount1->setAccount($account);
        $priceListToAccount1->setWebsite($website1);
        $priceListToAccount1->setPriority(3);
        $priceListToAccount2 = clone $priceListToAccount1;
        $priceListToAccount2->setWebsite($website2);
        $priceListsToAccount = [$priceListToAccount1, $priceListToAccount2];
        $templateHtml = 'template_html';

        $fallbackEntity = new PriceListAccountFallback();
        $fallbackEntity->setAccount($account);
        $fallbackEntity->setWebsite($website3);
        $fallbackEntity->setFallback(PriceListAccountFallback::CURRENT_ACCOUNT_ONLY);

        $request = new Request(['id' => $accountId]);
        $requestStack = $this->getRequestStack($request);


        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);

        $this->setRepositoryExpectationsForAccount($websites, $account, $priceListsToAccount, $fallbackEntity);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with(
                'OroB2BPricingBundle:Account:price_list_view.html.twig',
                [
                    'priceListsByWebsites' => [
                        $websiteId1 => [$priceListToAccount1],
                        $websiteId2 => [$priceListToAccount2],
                    ],
                    'fallbackByWebsites' => [
                        $websiteId3 => PriceListAccountFallback::CURRENT_ACCOUNT_ONLY,
                    ],
                    'websites' => [$website1, $website2, $website3],
                    'choices' => [
                        'orob2b.pricing.fallback.account_group.label',
                        'orob2b.pricing.fallback.current_account_only.label',
                    ],
                ]
            )
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $listener->onAccountView($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            [$templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    public function testOnAccountGroupView()
    {
        $accountGroupId = 1;
        $accountGroup = new AccountGroup();
        $websiteId1 = 12;
        $websiteId2 = 13;
        $websiteId3 = 14;
        /** @var Website $website1 */
        $website1 = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', $websiteId1);
        /** @var Website $website2 */
        $website2 = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', $websiteId2);
        /** @var Website $website3 */
        $website3 = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', $websiteId3);
        $websites = [$website1, $website2, $website3];
        $priceListToAccountGroup1 = new PriceListToAccountGroup();
        $priceListToAccountGroup1->setAccountGroup($accountGroup);
        $priceListToAccountGroup1->setWebsite($website1);
        $priceListToAccountGroup1->setPriority(3);
        $priceListToAccountGroup2 = clone $priceListToAccountGroup1;
        $priceListToAccountGroup2->setWebsite($website2);
        $priceListsToAccountGroup = [$priceListToAccountGroup1, $priceListToAccountGroup2];
        $templateHtml = 'template_html';

        $fallbackEntity = new PriceListAccountGroupFallback();
        $fallbackEntity->setAccountGroup($accountGroup);
        $fallbackEntity->setWebsite($website3);
        $fallbackEntity->setFallback(PriceListAccountFallback::CURRENT_ACCOUNT_ONLY);
        $request = new Request(['id' => $accountGroupId]);

        $requestStack = $this->getRequestStack($request);

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);

        $this->setRepositoryExpectationsForAccountGroup(
            $websites,
            $accountGroup,
            $priceListsToAccountGroup,
            $fallbackEntity
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with(
                'OroB2BPricingBundle:Account:price_list_view.html.twig',
                [
                    'priceListsByWebsites' => [
                        $websiteId1 => [$priceListToAccountGroup1],
                        $websiteId2 => [$priceListToAccountGroup2],

                    ],
                    'fallbackByWebsites' => [
                        $websiteId3 => PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY,
                    ],
                    'websites' => [$website1, $website2, $website3],
                    'choices' => [
                        'orob2b.pricing.fallback.website.label',
                        'orob2b.pricing.fallback.current_account_group_only.label',
                    ],
                ]
            )
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $listener->onAccountGroupView($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            [$templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    public function testOnEntityEdit()
    {
        $formView = new FormView();
        $templateHtml = 'template_html';
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with('OroB2BPricingBundle:Account:price_list_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment, $formView);
        $listener->onEntityEdit($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            [$templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    public function testOnProductView()
    {
        $productId = 1;
        $product = new Product();
        $templateHtml = 'template_html';

        $request = new Request(['id' => $productId]);
        $requestStack = $this->getRequestStack($request);

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BProductBundle:Product', $productId)
            ->willReturn($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with('OroB2BPricingBundle:Product:prices_view.html.twig', ['entity' => $product])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $listener->onProductView($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertScrollDataPriceBlock($scrollData, $templateHtml);
    }

    public function testOnProductEdit()
    {
        $formView = new FormView();
        $templateHtml = 'prices_update_html';
        $productPriceAttributesPricesUpdateHtml = 'product_price_attributes_prices_update_html';

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->exactly(2))
            ->method('render')
            ->willReturnMap([
                [
                    'OroB2BPricingBundle:Product:product_price_attributes_prices_update.html.twig',
                    ['form' => $formView],
                    $productPriceAttributesPricesUpdateHtml
                ],
                [
                    'OroB2BPricingBundle:Product:prices_update.html.twig',
                    ['form' => $formView],
                    $templateHtml
                ]
            ]);

        $event = $this->createEvent($environment, $formView);
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);
        $listener->onProductEdit($event);
        $scrollData = $event->getScrollData()->getData();

        $expectedBlocks = [
            [
                'title' => 'orob2b.pricing.priceattributeprice.entity_plural_label.trans',
                'useSubBlockDivider' => true,
                'subblocks' => [['data' => ['product_price_attributes_prices_update_html']]],
            ],
            [
                'title' => 'orob2b.pricing.productprice.entity_plural_label.trans',
                'useSubBlockDivider' => true,
                'subblocks' => [['data' => ['prices_update_html']]],
            ],
        ];

        foreach ($expectedBlocks as $block) {
            $this->assertContains($block, $scrollData[ScrollData::DATA_BLOCKS]);
        }
    }

    /**
     * @param array $scrollData
     * @param string $html
     */
    protected function assertScrollDataPriceBlock(array $scrollData, $html)
    {
        $this->assertEquals(
            'orob2b.pricing.productprice.entity_plural_label.trans',
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::TITLE]
        );
        $this->assertEquals(
            [$html],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    /**
     * @param \Twig_Environment $environment
     * @param FormView $formView
     * @return BeforeListRenderEvent
     */
    protected function createEvent(\Twig_Environment $environment, FormView $formView = null)
    {
        $defaultData = [
            ScrollData::DATA_BLOCKS => [
                [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => [],
                        ]
                    ]
                ]
            ]
        ];

        return new BeforeListRenderEvent($environment, new ScrollData($defaultData), $formView);
    }

    /**
     * @param string $class
     * @param int $id
     * @return object
     */
    protected function getEntity($class, $id)
    {
        $entity = new $class();
        $reflection = new \ReflectionProperty(get_class($entity), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $id);

        return $entity;
    }

    /**
     * @param RequestStack $requestStack
     * @return FormViewListener
     */
    protected function getListener(RequestStack $requestStack)
    {
        return new FormViewListener(
            $requestStack,
            $this->translator,
            $this->doctrineHelper
        );
    }

    /**
     * @param Website[] $websites
     * @param Account $account
     * @param PriceListToAccount[] $priceListsToAccount
     * @param PriceListAccountFallback $fallbackEntity
     */
    protected function setRepositoryExpectationsForAccount(
        $websites,
        Account $account,
        $priceListsToAccount,
        PriceListAccountFallback $fallbackEntity
    ) {
        $websiteRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $websiteRepository->expects($this->once())
            ->method('findBy')
            ->willReturn($websites);

        $priceToAccountRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $priceToAccountRepository->expects($this->once())
            ->method('findBy')
            ->with(['account' => $account])
            ->willReturn($priceListsToAccount);

        $fallbackRepository = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $fallbackRepository->expects($this->once())
            ->method('findBy')
            ->with(['account' => $account])
            ->willReturn([$fallbackEntity]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($account);
        $this->doctrineHelper->expects($this->exactly(3))
            ->method('getEntityRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['OroB2BPricingBundle:PriceListToAccount', $priceToAccountRepository],
                        ['OroB2BPricingBundle:PriceListAccountFallback', $fallbackRepository],
                        ['OroB2BWebsiteBundle:Website', $websiteRepository],
                    ]
                )
            );
    }

    /**
     * @param Website[] $websites
     * @param AccountGroup $accountGroup
     * @param PriceListToAccountGroup[] $priceListsToAccountGroup
     * @param PriceListAccountGroupFallback $fallbackEntity
     */
    protected function setRepositoryExpectationsForAccountGroup(
        $websites,
        AccountGroup $accountGroup,
        $priceListsToAccountGroup,
        PriceListAccountGroupFallback $fallbackEntity
    ) {
        $websiteRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $websiteRepository->expects($this->once())
            ->method('findBy')
            ->willReturn($websites);

        $priceToAccountGroupRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $priceToAccountGroupRepository->expects($this->once())
            ->method('findBy')
            ->with(['accountGroup' => $accountGroup])
            ->willReturn($priceListsToAccountGroup);

        $fallbackRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $fallbackRepository->expects($this->once())
            ->method('findBy')
            ->with(['accountGroup' => $accountGroup])
            ->willReturn([$fallbackEntity]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($accountGroup);
        $this->doctrineHelper->expects($this->exactly(3))
            ->method('getEntityRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['OroB2BPricingBundle:PriceListToAccountGroup', $priceToAccountGroupRepository],
                        ['OroB2BPricingBundle:PriceListAccountGroupFallback', $fallbackRepository],
                        ['OroB2BWebsiteBundle:Website', $websiteRepository],
                    ]
                )
            );
    }

    /**
     * @param $request
     * @return \PHPUnit_Framework_MockObject_MockObject|RequestStack
     */
    protected function getRequestStack($request)
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        return $requestStack;
    }
}
