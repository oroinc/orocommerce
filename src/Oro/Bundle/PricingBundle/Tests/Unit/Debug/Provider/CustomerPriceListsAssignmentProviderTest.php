<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Debug\Handler\DebugProductPricesPriceListRequestHandler;
use Oro\Bundle\PricingBundle\Debug\Provider\CustomerPriceListsAssignmentProvider;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerFallbackRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomerPriceListsAssignmentProviderTest extends TestCase
{
    use EntityTrait;

    private DebugProductPricesPriceListRequestHandler|MockObject $requestHandler;
    private ManagerRegistry|MockObject $registry;
    private TranslatorInterface|MockObject $translator;
    private UrlGeneratorInterface|MockObject $urlGenerator;
    private AuthorizationCheckerInterface|MockObject $authorizationChecker;

    private CustomerPriceListsAssignmentProvider $provider;

    protected function setUp(): void
    {
        $this->requestHandler = $this->createMock(DebugProductPricesPriceListRequestHandler::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->provider = new CustomerPriceListsAssignmentProvider(
            $this->requestHandler,
            $this->registry,
            $this->translator,
            $this->urlGenerator,
            $this->authorizationChecker
        );
    }

    /**
     * @dataProvider infoDataProvider
     */
    public function testGetPriceListAssignments(
        ?PriceListCustomerFallback $fallbackEntity,
        string $expectedFallback,
        ?string $expectedFallbackTitle,
        bool $expectedStop,
        bool $hasGroup
    ) {
        $customer = $this->getEntity(Customer::class, ['id' => 50]);
        $customer->setName('Test Name');
        if ($hasGroup) {
            $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 40]);
            $customerGroup->setName('Test Group');
            $customer->setGroup($customerGroup);
        }
        $website = $this->getEntity(Website::class, ['id' => 10]);

        $this->requestHandler->expects($this->once())
            ->method('getWebsite')
            ->willReturn($website);
        $this->requestHandler->expects($this->once())
            ->method('getCustomer')
            ->willReturn($customer);

        $relations = [
            (new PriceListToCustomer())
                ->setPriceList($this->getEntity(PriceList::class, ['id' => 1]))
                ->setSortOrder(10)
                ->setMergeAllowed(true)
        ];

        $entityRepo = $this->createMock(PriceListToCustomerRepository::class);
        $entityRepo->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'customer' => $customer,
                    'website' => $website
                ],
                ['sortOrder' => PriceListCollectionType::DEFAULT_ORDER]
            )
            ->willReturn($relations);
        $fallbackRepo = $this->createMock(PriceListCustomerFallbackRepository::class);
        $fallbackRepo->expects($this->once())
            ->method('findOneBy')
            ->with(
                [
                    'customer' => $customer,
                    'website' => $website
                ]
            )
            ->willReturn($fallbackEntity);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [PriceListToCustomer::class, null, $entityRepo],
                [PriceListCustomerFallback::class, null, $fallbackRepo]
            ]);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(fn ($str) => $str . ' TR');

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $customer)
            ->willReturn(true);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with(
                'oro_customer_customer_view',
                ['id' => 50]
            )
            ->willReturn('/view-url');

        $expected = [
            'section_title' => 'oro.customer.customer.entity_label TR',
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
                'oro.pricing.fallback.customer_group.label',
                'Test Group',
                false,
                true
            ],
            [
                (new PriceListCustomerFallback())->setFallback(PriceListCustomerFallback::ACCOUNT_GROUP),
                'oro.pricing.fallback.customer_group.label',
                'Test Group',
                false,
                true
            ],
            [
                null,
                'oro.pricing.fallback.customer_group.label',
                null,
                false,
                false
            ],
            [
                (new PriceListCustomerFallback())->setFallback(PriceListCustomerFallback::ACCOUNT_GROUP),
                'oro.pricing.fallback.customer_group.label',
                null,
                false,
                false
            ],
            [
                (new PriceListCustomerFallback())
                    ->setFallback(PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY),
                'oro.pricing.fallback.current_customer_only.label',
                null,
                true,
                true
            ]
        ];
    }

    public function testGetPriceListAssignmentsWithNotGrantedView()
    {
        $customer = $this->getEntity(Customer::class, ['id' => 50]);
        $customer->setName('Test Name');
        $website = $this->getEntity(Website::class, ['id' => 10]);

        $this->requestHandler->expects($this->once())
            ->method('getWebsite')
            ->willReturn($website);
        $this->requestHandler->expects($this->once())
            ->method('getCustomer')
            ->willReturn($customer);

        $relations = [
            (new PriceListToCustomer())
                ->setPriceList($this->getEntity(PriceList::class, ['id' => 1]))
                ->setSortOrder(10)
                ->setMergeAllowed(true)
        ];

        $entityRepo = $this->createMock(PriceListToCustomerRepository::class);
        $entityRepo->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'customer' => $customer,
                    'website' => $website
                ],
                ['sortOrder' => PriceListCollectionType::DEFAULT_ORDER]
            )
            ->willReturn($relations);
        $fallbackRepo = $this->createMock(PriceListCustomerFallbackRepository::class);
        $fallbackRepo->expects($this->once())
            ->method('findOneBy')
            ->with(
                [
                    'customer' => $customer,
                    'website' => $website
                ]
            )
            ->willReturn(null);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [PriceListToCustomer::class, null, $entityRepo],
                [PriceListCustomerFallback::class, null, $fallbackRepo]
            ]);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(fn ($str) => $str . ' TR');

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $customer)
            ->willReturn(false);

        $this->urlGenerator->expects($this->never())
            ->method('generate');

        $expected = [
            'section_title' => 'oro.customer.customer.entity_label TR',
            'link' => null,
            'link_title' => 'Test Name',
            'fallback' => 'oro.pricing.fallback.customer_group.label',
            'fallback_entity_title' => null,
            'price_lists' => $relations,
            'stop' => false
        ];

        $this->assertEquals($expected, $this->provider->getPriceListAssignments());
    }

    public function testGetPriceListAssignmentsWhenNoCustomer()
    {
        $website = $this->getEntity(Website::class, ['id' => 10]);

        $this->requestHandler->expects($this->any())
            ->method('getWebsite')
            ->willReturn($website);
        $this->requestHandler->expects($this->once())
            ->method('getCustomer')
            ->willReturn(null);

        $this->registry->expects($this->never())
            ->method('getRepository');

        $this->assertNull($this->provider->getPriceListAssignments());
    }
}
