<?php

declare(strict_types=1);

namespace Oro\Bundle\ApplicationBundle\Tests\Functional\Search;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountriesAndRegions;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUser;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderInternalStatuses;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductDefaultAttributeFamily;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SearchBundle\Tests\Functional\Engine\AbstractEntitiesOrmIndexerTest;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

/**
 * Tests that Commerce entities can be indexed without type casting errors with the ORM search engine.
 *
 * @group search
 * @dbIsolationPerTest
 */
class ApplicationEntitiesOrmIndexerTest extends AbstractEntitiesOrmIndexerTest
{
    #[\Override]
    protected function getSearchableEntityClassesToTest(): array
    {
        return [
            Brand::class,
            Category::class,
            Consent::class,
            ContentTemplate::class,
            ContentWidget::class,
            Coupon::class,
            CustomerTaxCode::class,
            Order::class,
            Page::class,
            PaymentTerm::class,
            PriceList::class,
            Product::class,
            ProductTaxCode::class,
            Promotion::class,
            Quote::class,
            Request::class,
            ShoppingList::class,
            Tax::class,
            TaxJurisdiction::class,
            WebCatalog::class,
        ];
    }

    #[\Override]
    protected function getFieldsToExcludeFromValidation(): array
    {
        return [
            /**
             * "oro_category_organization" is added dynamically, not read from the entity,
             * so we cannot and do not need to validate it in this test.
             * @see \Oro\Bundle\MarketplaceSellerOrganizationBundle\EventListener\CategorySearchListener
             */
            Category::class => ['oro_category_organization'],
        ];
    }

    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadOrganization::class,
            LoadUser::class,
            LoadProductDefaultAttributeFamily::class,
            LoadCustomerUser::class,
            LoadCountriesAndRegions::class,
            LoadOrderInternalStatuses::class,
        ]);

        $manager = $this->getDoctrine()->getManagerForClass(Product::class);
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        $businessUnit = $user->getOwner();

        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUser::CUSTOMER_USER);

        $category = (new Category())
            ->setDefaultTitle('Test Category')
            ->setOrganization($organization);
        $this->persistTestEntity($category);

        $brand = (new Brand())
            ->setOrganization($organization)
            ->setOwner($businessUnit)
            ->addName((new LocalizedFallbackValue())->setString('Test Brand'))
            ->addDescription((new LocalizedFallbackValue())->setString('Test Description'))
            ->addShortDescription((new LocalizedFallbackValue())->setString('Test Short Description'));
        $this->persistTestEntity($brand);

        /** @var AttributeFamily $attributeFamily */
        $attributeFamily = $this->getReference('default_product_family');

        $productInventoryStatus = $manager->getReference(
            EnumOption::class,
            ExtendHelper::buildEnumOptionId(Product::INVENTORY_STATUS_ENUM_CODE, Product::INVENTORY_STATUS_IN_STOCK)
        );
        $product = (new Product())
            ->setOrganization($organization)
            ->setOwner($businessUnit)
            ->setSku('TEST-SKU')
            ->addName((new ProductName())->setString('Test Product'))
            ->addDescription((new ProductDescription())->setWysiwyg('<p>Test description</p>'))
            ->addShortDescription((new ProductShortDescription())->setText('Test short description'))
            ->setAttributeFamily($attributeFamily)
            ->setBrand($brand);
        $product->setInventoryStatus($productInventoryStatus);
        $this->persistTestEntity($product);

        $page = (new Page())
            ->setOrganization($organization)
            ->addSlugPrototype((new LocalizedFallbackValue())->setString('test-page'))
            ->addTitle((new LocalizedFallbackValue())->setString('Test Page'));

        // Create a Slug entity directly instead of relying on async message queue
        $slug = new Slug();
        $slug->setUrl('/test-page');
        $slug->setRouteName('oro_cms_frontend_page_view');
        $slug->setRouteParameters(['id' => 0]); // Will be updated after page is persisted
        $slug->setOrganization($organization);
        $page->addSlug($slug);

        $this->persistTestEntity($page);

        $consent = (new Consent())
            ->setOrganization($organization)
            ->setOwner($user)
            ->addName((new LocalizedFallbackValue())->setString('Test Consent'))
            ->setDefaultName('Test Consent');
        $this->persistTestEntity($consent);

        /** @var Country $country */
        $country = $this->getReference('country_usa');
        /** @var Region $region */
        $region = $this->getReference('region_usa_california');

        $orderAddress = (new OrderAddress())
            ->setOrganization($organization)
            ->setCountry($country)
            ->setRegion($region)
            ->setStreet('123 Main St')
            ->setCity('Test City')
            ->setPostalCode('12345');

        /** @var EnumOption $orderInternalStatus */
        $orderInternalStatus = $this->getReference(
            ExtendHelper::buildEnumOptionId(
                Order::INTERNAL_STATUS_CODE,
                OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
            )
        );
        $order = (new Order())
            ->setOrganization($organization)
            ->setOwner($user)
            ->setIdentifier('ORDER-001')
            ->setShippingAddress(clone $orderAddress)
            ->setBillingAddress(clone $orderAddress)
            ->setPoNumber('PO-001')
            ->setCustomerUser($customerUser)
            ->setShipUntil(new \DateTime('now', new \DateTimeZone('UTC')))
            ->setInternalStatus($orderInternalStatus)
            ->setSubtotal(100.00)
            ->setTotal(100.00);
        $this->persistTestEntity($order);

        $paymentTerm = (new PaymentTerm())->setLabel('Net 30');
        $this->persistTestEntity($paymentTerm);

        $priceList = (new PriceList())
            ->setOrganization($organization)
            ->setName('Test Price List');
        $this->persistTestEntity($priceList);

        $discountConfiguration = (new DiscountConfiguration())->setType('order');
        $manager->persist($discountConfiguration);

        $rule = (new Rule())->setName('Test Rule')->setSortOrder(1)->setEnabled(true);
        $manager->persist($rule);

        $segmentType = $manager->getRepository(SegmentType::class)->find(SegmentType::TYPE_DYNAMIC);
        $segment = (new Segment())
            ->setOrganization($organization)
            ->setOwner($user->getOwner())
            ->setName('Test Segment')
            ->setType($segmentType)
            ->setEntity(Product::class)
            ->setDefinition('{}');
        $manager->persist($segment);

        $promotion = (new Promotion())
            ->setOrganization($organization)
            ->setOwner($user)
            ->setDiscountConfiguration($discountConfiguration)
            ->setRule($rule)
            ->setProductsSegment($segment);
        $this->persistTestEntity($promotion);

        $requestCustomerStatus = $manager->getReference(
            EnumOption::class,
            ExtendHelper::buildEnumOptionId(Request::CUSTOMER_STATUS_CODE, 'submitted')
        );
        $requestInternalStatus = $manager->getReference(
            EnumOption::class,
            ExtendHelper::buildEnumOptionId(Request::INTERNAL_STATUS_CODE, 'open')
        );
        $request = (new Request())
            ->setOrganization($organization)
            ->setOwner($user)
            ->setCustomerUser($customerUser)
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john.doe@example.com')
            ->setCompany('Test Company')
            ->setPhone('123-456-7890')
            ->setRole('Test Role')
            ->setNote('Test note')
            ->setPoNumber('PO-001')
            ->setShipUntil(new \DateTime('now', new \DateTimeZone('UTC')));
        $request->setCustomerStatus($requestCustomerStatus);
        $request->setInternalStatus($requestInternalStatus);
        $this->persistTestEntity($request);

        $quoteAddress = (new QuoteAddress())
            ->setOrganization($organization)
            ->setCountry($country)
            ->setRegion($region)
            ->setStreet('123 Main St')
            ->setCity('Test City')
            ->setPostalCode('12345');

        $quoteCustomerStatus = $manager->getReference(
            EnumOption::class,
            ExtendHelper::buildEnumOptionId(Quote::CUSTOMER_STATUS_CODE, 'open')
        );
        $quoteInternalStatus = $manager->getReference(
            EnumOption::class,
            ExtendHelper::buildEnumOptionId(Quote::INTERNAL_STATUS_CODE, 'sent_to_customer')
        );
        $quote = (new Quote())
            ->setOrganization($organization)
            ->setOwner($user)
            ->setValidUntil(new \DateTime('now', new \DateTimeZone('UTC')))
            ->setShipUntil(new \DateTime('now', new \DateTimeZone('UTC')))
            ->setPoNumber('PO-001')
            ->setCustomerUser($customerUser)
            ->setShippingAddress($quoteAddress)
            ->setRequest($request);
        $quote->setCustomerStatus($quoteCustomerStatus);
        $quote->setInternalStatus($quoteInternalStatus);

        $this->persistTestEntity($quote);

        $shoppingList = (new ShoppingList())
            ->setOrganization($organization)
            ->setOwner($user)
            ->setLabel('Test Shopping List');
        $this->persistTestEntity($shoppingList);

        $tax = (new Tax())
            ->setCode('VAT')
            ->setRate(0.20)
            ->setDescription('Value Added Tax');
        $this->persistTestEntity($tax);

        $webCatalog = (new WebCatalog())
            ->setOrganization($organization)
            ->setName('Test Web Catalog')
            ->setDescription('Test Description');
        $this->persistTestEntity($webCatalog);

        $contentWidget = (new ContentWidget())
            ->setOrganization($organization)
            ->setName('Test Content Widget')
            ->setWidgetType('test_type')
            ->setDescription('Test Description');
        $this->persistTestEntity($contentWidget);

        $contentTemplate = (new ContentTemplate())
            ->setOrganization($organization)
            ->setName('Test Content Template')
            ->setContent('<p>Test content</p>');
        $this->persistTestEntity($contentTemplate);

        $productTaxCode = (new ProductTaxCode())
            ->setOrganization($organization)
            ->setCode('PRODUCT_TAX')
            ->setDescription('Product Tax');
        $this->persistTestEntity($productTaxCode);

        $customerTaxCode = (new CustomerTaxCode())
            ->setOrganization($organization)
            ->setCode('CUSTOMER_TAX')
            ->setDescription('Customer Tax');
        $this->persistTestEntity($customerTaxCode);

        $taxJurisdiction = (new TaxJurisdiction())
            ->setCode('US_CA')
            ->setDescription('California, USA');
        $this->persistTestEntity($taxJurisdiction);

        $coupon = (new Coupon())
            ->setOrganization($organization)
            ->setOwner($businessUnit)
            ->setCode('SAVE10')
            ->setPromotion($promotion)
            ->setEnabled(true)
            ->setUsesPerCoupon(100)
            ->setUsesPerPerson(1);
        $this->persistTestEntity($coupon);

        $manager->flush();
    }
}
