<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\PricingBundle\Debug\Handler\DebugProductPricesPriceListRequestHandler;
use Oro\Bundle\PricingBundle\Debug\Provider\CustomerGroupPriceListsAssignmentProvider;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerGroupFallbackRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomerGroupPriceListsAssignmentProviderTest extends TestCase
{
    use EntityTrait;

    private DebugProductPricesPriceListRequestHandler|MockObject $requestHandler;
    private ManagerRegistry|MockObject $registry;
    private TranslatorInterface|MockObject $translator;
    private UrlGeneratorInterface|MockObject $urlGenerator;
    private CustomerUserRelationsProvider|MockObject $relationsProvider;
    private AuthorizationCheckerInterface|MockObject $authorizationChecker;

    private CustomerGroupPriceListsAssignmentProvider $provider;

    protected function setUp(): void
    {
        $this->requestHandler = $this->createMock(DebugProductPricesPriceListRequestHandler::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->relationsProvider = $this->createMock(CustomerUserRelationsProvider::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->provider = new CustomerGroupPriceListsAssignmentProvider(
            $this->requestHandler,
            $this->registry,
            $this->translator,
            $this->urlGenerator,
            $this->relationsProvider,
            $this->authorizationChecker
        );
    }

    /**
     * @dataProvider infoDataProvider
     */
    public function testGetPriceListAssignments(
        ?PriceListCustomerGroupFallback $fallbackEntity,
        string $expectedFallback,
        ?string $expectedFallbackTitle,
        bool $expectedStop
    ) {
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 100]);
        $customerGroup->setName('Test Name');
        $customer = $this->getEntity(Customer::class, ['id' => 50]);
        $customer->setGroup($customerGroup);
        $website = $this->getEntity(Website::class, ['id' => 10]);
        $website->setName('Test Website');

        $this->requestHandler->expects($this->once())
            ->method('getWebsite')
            ->willReturn($website);
        $this->requestHandler->expects($this->once())
            ->method('getCustomer')
            ->willReturn($customer);

        $relations = [
            (new PriceListToCustomerGroup())
                ->setPriceList($this->getEntity(PriceList::class, ['id' => 1]))
                ->setSortOrder(10)
                ->setMergeAllowed(true)
        ];

        $entityRepo = $this->createMock(PriceListToCustomerGroupRepository::class);
        $entityRepo->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'customerGroup' => $customerGroup,
                    'website' => $website
                ],
                ['sortOrder' => PriceListCollectionType::DEFAULT_ORDER]
            )
            ->willReturn($relations);
        $fallbackRepo = $this->createMock(PriceListCustomerGroupFallbackRepository::class);
        $fallbackRepo->expects($this->once())
            ->method('findOneBy')
            ->with(
                [
                    'customerGroup' => $customerGroup,
                    'website' => $website
                ]
            )
            ->willReturn($fallbackEntity);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [PriceListToCustomerGroup::class, null, $entityRepo],
                [PriceListCustomerGroupFallback::class, null, $fallbackRepo]
            ]);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(fn ($str) => $str . ' TR');

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $customerGroup)
            ->willReturn(true);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with(
                'oro_customer_customer_group_view',
                ['id' => 100]
            )
            ->willReturn('/view-url');

        $expected = [
            'section_title' => 'oro.customer.customergroup.entity_label TR',
            'link' => '/view-url',
            'link_title' => 'Test Name',
            'fallback' => $expectedFallback,
            'fallback_entity_title' => $expectedFallbackTitle,
            'price_lists' => $relations,
            'stop' => $expectedStop
        ];

        $this->assertEquals($expected, $this->provider->getPriceListAssignments());
    }

    public static function infoDataProvider(): array
    {
        return [
            [
                null,
                'oro.pricing.fallback.website.label',
                'Test Website',
                false
            ],
            [
                (new PriceListCustomerGroupFallback())->setFallback(PriceListCustomerGroupFallback::WEBSITE),
                'oro.pricing.fallback.website.label',
                'Test Website',
                false
            ],
            [
                (new PriceListCustomerGroupFallback())
                    ->setFallback(PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY),
                'oro.pricing.fallback.current_customer_group_only.label',
                null,
                true
            ]
        ];
    }

    public function testGetPriceListAssignmentsWhenNoCustomer()
    {
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 100]);
        $customerGroup->setName('Test Name');
        $customer = null;
        $website = $this->getEntity(Website::class, ['id' => 10]);
        $website->setName('Test Website');

        $this->requestHandler->expects($this->once())
            ->method('getWebsite')
            ->willReturn($website);
        $this->requestHandler->expects($this->once())
            ->method('getCustomer')
            ->willReturn($customer);

        $this->relationsProvider->expects($this->once())
            ->method('getCustomerGroup')
            ->willReturn($customerGroup);

        $relations = [
            (new PriceListToCustomerGroup())
                ->setPriceList($this->getEntity(PriceList::class, ['id' => 1]))
                ->setSortOrder(10)
                ->setMergeAllowed(true)
        ];

        $entityRepo = $this->createMock(PriceListToCustomerGroupRepository::class);
        $entityRepo->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'customerGroup' => $customerGroup,
                    'website' => $website
                ],
                ['sortOrder' => PriceListCollectionType::DEFAULT_ORDER]
            )
            ->willReturn($relations);
        $fallbackRepo = $this->createMock(PriceListCustomerGroupFallbackRepository::class);
        $fallbackRepo->expects($this->once())
            ->method('findOneBy')
            ->with(
                [
                    'customerGroup' => $customerGroup,
                    'website' => $website
                ]
            )
            ->willReturn(null);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [PriceListToCustomerGroup::class, null, $entityRepo],
                [PriceListCustomerGroupFallback::class, null, $fallbackRepo]
            ]);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(fn ($str) => $str . ' TR');

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $customerGroup)
            ->willReturn(true);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with(
                'oro_customer_customer_group_view',
                ['id' => 100]
            )
            ->willReturn('/view-url');

        $expected = [
            'section_title' => 'oro.customer.customergroup.entity_label TR',
            'link' => '/view-url',
            'link_title' => 'Test Name',
            'fallback' => 'oro.pricing.fallback.website.label',
            'fallback_entity_title' => 'Test Website',
            'price_lists' => $relations,
            'stop' => false
        ];

        $this->assertEquals($expected, $this->provider->getPriceListAssignments());
    }

    public function testGetPriceListAssignmentsWhenNoCustomerViewNotGranted()
    {
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 100]);
        $customerGroup->setName('Test Name');
        $customer = null;
        $website = $this->getEntity(Website::class, ['id' => 10]);
        $website->setName('Test Website');

        $this->requestHandler->expects($this->once())
            ->method('getWebsite')
            ->willReturn($website);
        $this->requestHandler->expects($this->once())
            ->method('getCustomer')
            ->willReturn($customer);

        $this->relationsProvider->expects($this->once())
            ->method('getCustomerGroup')
            ->willReturn($customerGroup);

        $relations = [
            (new PriceListToCustomerGroup())
                ->setPriceList($this->getEntity(PriceList::class, ['id' => 1]))
                ->setSortOrder(10)
                ->setMergeAllowed(true)
        ];

        $entityRepo = $this->createMock(PriceListToCustomerGroupRepository::class);
        $entityRepo->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'customerGroup' => $customerGroup,
                    'website' => $website
                ],
                ['sortOrder' => PriceListCollectionType::DEFAULT_ORDER]
            )
            ->willReturn($relations);
        $fallbackRepo = $this->createMock(PriceListCustomerGroupFallbackRepository::class);
        $fallbackRepo->expects($this->once())
            ->method('findOneBy')
            ->with(
                [
                    'customerGroup' => $customerGroup,
                    'website' => $website
                ]
            )
            ->willReturn(null);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [PriceListToCustomerGroup::class, null, $entityRepo],
                [PriceListCustomerGroupFallback::class, null, $fallbackRepo]
            ]);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(fn ($str) => $str . ' TR');

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $customerGroup)
            ->willReturn(false);

        $this->urlGenerator->expects($this->never())
            ->method('generate');

        $expected = [
            'section_title' => 'oro.customer.customergroup.entity_label TR',
            'link' => null,
            'link_title' => 'Test Name',
            'fallback' => 'oro.pricing.fallback.website.label',
            'fallback_entity_title' => 'Test Website',
            'price_lists' => $relations,
            'stop' => false
        ];

        $this->assertEquals($expected, $this->provider->getPriceListAssignments());
    }

    public function testGetPriceListAssignmentsWhenNoCustomerGroup()
    {
        $customer = $this->getEntity(Customer::class, ['id' => 50]);
        $website = $this->getEntity(Website::class, ['id' => 10]);

        $this->requestHandler->expects($this->any())
            ->method('getWebsite')
            ->willReturn($website);
        $this->requestHandler->expects($this->once())
            ->method('getCustomer')
            ->willReturn($customer);

        $this->relationsProvider->expects($this->never())
            ->method('getCustomerGroup');

        $this->registry->expects($this->never())
            ->method('getRepository');

        $this->assertNull($this->provider->getPriceListAssignments());
    }
}
