<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Oro\Bundle\ConsentBundle\Provider\ConsentContextProvider;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ConsentContextProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeManager;

    /**
     * @var SlugRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $slugRepository;

    /**
     * @var CustomerUserRelationsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerUserRelationsProvider;

    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestStack;

    /**
     * @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $frontendHelper;

    /**
     * @var ConsentContextProvider
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->slugRepository = $this->createMock(SlugRepository::class);
        $this->customerUserRelationsProvider = $this->createMock(CustomerUserRelationsProvider::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->provider = new ConsentContextProvider(
            $this->scopeManager,
            $this->slugRepository,
            $this->customerUserRelationsProvider,
            $this->requestStack,
            $this->frontendHelper
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->scopeManager,
            $this->slugRepository,
            $this->customerUserRelationsProvider,
            $this->requestStack,
            $this->frontendHelper,
            $this->provider
        );
    }

    public function testInitializeContextFrontendRequest()
    {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 1]);
        /** @var Scope $contentScope */
        $contentScope = $this->getEntity(Scope::class, ['id' => 123]);

        $request = new Request([], [], ['_web_content_scope' => $contentScope]);

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->provider->initializeContext($website);

        $this->assertSame(
            $contentScope,
            $this->provider->getScope()
        );
    }

    public function testInitializeContextFrontendRequestWithoutContentScope()
    {
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $customerUser = $this->getEntity(CustomerUser::class, [
            'id' => 1,
            'customer' => $customer
        ]);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 1]);
        /** @var Scope $contentScope */
        $contentScope = $this->getEntity(Scope::class, ['id' => 123]);
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 1]);

        $request = new Request();

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->scopeManager
            ->method('getCriteria')
            ->willReturnCallback(function ($scopeType, $context) {
                if (null === $context) {
                    $context = [];
                }
                return new ScopeCriteria($context, []);
            });

        $this->customerUserRelationsProvider
            ->expects($this->once())
            ->method('getCustomerGroup')
            ->with($customerUser)
            ->willReturn($customerGroup);

        $expectedScopeCriteria = new ScopeCriteria([
            'website' => $website,
            'customer' => $customer,
            'customerGroup' => $customerGroup
        ], []);

        $this->slugRepository
            ->expects($this->once())
            ->method('findMostSuitableUsedScope')
            ->with($expectedScopeCriteria)
            ->willReturn($contentScope);

        $this->provider->initializeContext($website, $customerUser);

        $this->assertSame(
            $contentScope,
            $this->provider->getScope()
        );
    }

    public function testInitializeContextBackendRequest()
    {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 1]);
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 12]);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getEntity(CustomerUser::class, [
            'id' => 12,
            'customer' => $customer
        ]);

        $expectedConsentContext = [
            'localization' => 'localization',
            'website' => $website,
            'customer' => $customer,
            'customerGroup' => 'customerGroup'
        ];

        $this->scopeManager->expects($this->any())
            ->method('getCriteria')
            ->willReturnCallback(
                function ($scopeName, $context = null) {
                    if (!is_array($context)) {
                        $context = [];
                    }
                    /**
                     * Emulate that localization given by app
                     */
                    if (empty($context)) {
                        $context['localization'] = 'localization';
                    }

                    return new ScopeCriteria($context, []);
                }
            );

        $this->customerUserRelationsProvider->expects($this->any())
            ->method('getCustomerGroup')
            ->with($customerUser)
            ->willReturn('customerGroup');

        $this->slugRepository->expects($this->any())
            ->method('findMostSuitableUsedScope')
            ->with(new ScopeCriteria($expectedConsentContext, []))
            ->willReturn(122);

        $this->provider->initializeContext($website, $customerUser);
    }

    public function testResetContext()
    {
        $this->provider->resetContext();

        $this->assertEquals(false, $this->provider->isInitialized());
        $this->assertEquals(null, $this->provider->getWebsite());
        $this->assertEquals(null, $this->provider->getCustomerUser());
        $this->assertEquals(null, $this->provider->getScope());
    }
}
