<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SubOrderOrganizationProviderInterface;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SubOrderOwnerProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use PHPUnit\Framework\Constraint\Constraint;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SubOrderOwnerProviderTest extends OrmTestCase
{
    /** @var SubOrderOrganizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $subOrderOrganizationProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var OwnershipMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $metadataProvider;

    /** @var EntityManagerInterface */
    private $em;

    /** @var SubOrderOwnerProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->subOrderOrganizationProvider = $this->createMock(SubOrderOrganizationProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->metadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(
            new AnnotationReader(),
            [dirname((new \ReflectionClass(User::class))->getFileName())]
        ));

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($this->em);

        $this->provider = new SubOrderOwnerProvider(
            $this->subOrderOrganizationProvider,
            $this->configManager,
            PropertyAccess::createPropertyAccessor(),
            $this->metadataProvider,
            $doctrine
        );
    }

    private function getProduct(int $organizationId, int $businessUnitId): Product
    {
        $organization = new Organization();
        $organization->setId($organizationId);

        $businessUnit = new BusinessUnit();
        $businessUnit->setId($businessUnitId);

        $product = new Product();
        $product->setOrganization($organization);
        $product->setOwner($businessUnit);

        return $product;
    }

    private function getLineItem(?Product $product = null): CheckoutLineItem
    {
        $lineItem = new CheckoutLineItem();
        if (null !== $product) {
            $lineItem->setProduct($product);
        }

        return $lineItem;
    }

    private function getBusinessUnitEnabledUserQuery(): Constraint
    {
        return self::logicalAnd(
            self::stringStartsWith('SELECT o0_.id AS id_0,'),
            self::stringEndsWith(
                'FROM oro_user o0_ WHERE EXISTS ('
                . 'SELECT 1 FROM oro_user_business_unit o1_'
                . ' WHERE o1_.user_id = o0_.id AND o1_.business_unit_id IN (?))'
                . ' AND o0_.enabled = ? ORDER BY o0_.id ASC LIMIT 1'
            )
        );
    }

    private function getBusinessUnitUserQuery(): Constraint
    {
        return self::logicalAnd(
            self::stringStartsWith('SELECT o0_.id AS id_0,'),
            self::stringEndsWith(
                'FROM oro_user o0_ WHERE EXISTS ('
                . 'SELECT 1 FROM oro_user_business_unit o1_'
                . ' WHERE o1_.user_id = o0_.id AND o1_.business_unit_id IN (?))'
                . ' ORDER BY o0_.id ASC LIMIT 1'
            )
        );
    }

    private function getOrganizationEnabledUserQuery(): Constraint
    {
        return self::logicalAnd(
            self::stringStartsWith('SELECT o0_.id AS id_0,'),
            self::stringEndsWith(
                'FROM oro_user o0_ WHERE EXISTS ('
                . 'SELECT 1 FROM oro_user_organization o1_'
                . ' WHERE o1_.user_id = o0_.id AND o1_.organization_id IN (?))'
                . ' AND o0_.enabled = ? ORDER BY o0_.id ASC LIMIT 1'
            )
        );
    }

    private function getOrganizationUserQuery(): Constraint
    {
        return self::logicalAnd(
            self::stringStartsWith('SELECT o0_.id AS id_0,'),
            self::stringEndsWith(
                'FROM oro_user o0_ WHERE EXISTS ('
                . 'SELECT 1 FROM oro_user_organization o1_'
                . ' WHERE o1_.user_id = o0_.id AND o1_.organization_id IN (?))'
                . ' ORDER BY o0_.id ASC LIMIT 1'
            )
        );
    }

    private function expectsGetConfiguredOwner(
        ArrayCollection $lineItems,
        string $groupingPath,
        ?int $configuredOwnerId
    ): void {
        $organization = $this->createMock(Organization::class);

        $this->subOrderOrganizationProvider->expects(self::once())
            ->method('getOrganization')
            ->with(self::identicalTo($lineItems), $groupingPath)
            ->willReturn($organization);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(
                'oro_order.order_creation_new_order_owner',
                self::isFalse(),
                self::isFalse(),
                self::identicalTo($organization)
            )
            ->willReturn($configuredOwnerId);
    }

    public function testGetOwnerWhenOwnerSourceIsObject(): void
    {
        $userId = 123;
        $category = new Category();
        $product = $this->getProduct(1, 1);
        $product->setCategory($category);
        $category->setOrganization($product->getOrganization());
        $lineItems = new ArrayCollection([$this->getLineItem($product)]);
        $groupingPath = 'product.category:1';

        $this->expectsGetConfiguredOwner($lineItems, $groupingPath, null);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->willReturn(new OwnershipMetadata('ORGANIZATION', 'organization', 'organization_id'));

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            $this->getOrganizationEnabledUserQuery(),
            [['id_0' => $userId]],
            [1 => 1, 2 => true],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_BOOL]
        );

        $owner = $this->provider->getOwner($lineItems, $groupingPath);
        self::assertEquals($userId, $owner->getId());
    }

    public function testGetOwnerWhenOwnerSourceIsObjectAndOwnerIsConfiguredForOrganization(): void
    {
        $configuredOwnerId = 100;
        $category = new Category();
        $product = $this->getProduct(1, 1);
        $product->setCategory($category);
        $category->setOrganization($product->getOrganization());
        $lineItems = new ArrayCollection([$this->getLineItem($product)]);
        $groupingPath = 'product.category:1';

        $this->expectsGetConfiguredOwner($lineItems, $groupingPath, $configuredOwnerId);

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $owner = $this->provider->getOwner($lineItems, $groupingPath);
        self::assertEquals($configuredOwnerId, $owner->getId());
    }

    public function testGetOwnerWhenOwnerSourceIsScalarValue(): void
    {
        $userId = 123;
        $product = $this->getProduct(1, 1);
        $product->setSku('sku');
        $lineItems = new ArrayCollection([$this->getLineItem($product)]);
        $groupingPath = 'product.sku:SKU-TEST';

        $this->expectsGetConfiguredOwner($lineItems, $groupingPath, null);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->willReturn(new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'business_unit_owner_id'));

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            $this->getBusinessUnitEnabledUserQuery(),
            [['id_0' => $userId]],
            [1 => 1, 2 => true],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_BOOL]
        );

        $owner = $this->provider->getOwner($lineItems, $groupingPath);
        self::assertEquals($userId, $owner->getId());
    }

    public function testGetOwnerWhenOwnerSourceIsScalarValueAndOwnerIsConfiguredForOrganization(): void
    {
        $configuredOwnerId = 100;
        $product = $this->getProduct(1, 1);
        $product->setSku('sku');
        $lineItems = new ArrayCollection([$this->getLineItem($product)]);
        $groupingPath = 'product.sku:SKU-TEST';

        $this->expectsGetConfiguredOwner($lineItems, $groupingPath, $configuredOwnerId);

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $owner = $this->provider->getOwner($lineItems, $groupingPath);
        self::assertEquals($configuredOwnerId, $owner->getId());
    }

    public function testGetOwnerWhenOwnerSourceWithFreeFromProduct(): void
    {
        $user = new User();
        $checkout = new Checkout();
        $checkout->setOwner($user);
        $lineItem = $this->getLineItem();
        $lineItem->setCheckout($checkout);
        $lineItems = new ArrayCollection([$lineItem]);
        $groupingPath = 'other-items';

        $this->expectsGetConfiguredOwner($lineItems, $groupingPath, null);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->willReturn(new OwnershipMetadata('USER', 'owner', 'user_owner_id'));

        $owner = $this->provider->getOwner($lineItems, $groupingPath);
        self::assertSame($user, $owner);
    }

    public function testGetOwnerWhenOwnerSourceHasUserOwnership(): void
    {
        $user = new User();
        $checkout = new Checkout();
        $checkout->setOwner($user);
        $lineItem = $this->getLineItem($this->getProduct(1, 1));
        $lineItem->setCheckout($checkout);
        $lineItems = new ArrayCollection([$lineItem]);
        $groupingPath = 'checkout:1';

        $this->expectsGetConfiguredOwner($lineItems, $groupingPath, null);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->willReturn(new OwnershipMetadata('USER', 'owner', 'user_owner_id'));

        $owner = $this->provider->getOwner($lineItems, 'checkout:1');
        self::assertSame($user, $owner);
    }

    public function testGetOwnerWhenOwnerSourceHasNoneOwnership(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to determine order owner.');

        $user = new User();
        $checkout = new Checkout();
        $checkout->setOwner($user);
        $lineItem1 = $this->getLineItem($this->getProduct(1, 1));
        $lineItem1->setCheckout($checkout);
        $lineItem2 = $this->getLineItem($this->getProduct(1, 2));
        $lineItem2->setCheckout($checkout);
        $lineItems = new ArrayCollection([$lineItem1, $lineItem2]);
        $groupingPath = 'checkout:1';

        $this->expectsGetConfiguredOwner($lineItems, $groupingPath, null);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->willReturn(new OwnershipMetadata('NONE'));

        $this->provider->getOwner($lineItems, $groupingPath);
    }

    public function testGetOwnerWhenOwnerSourceHasEmptyOwner(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to determine order owner.');

        $category = new Category();
        $product1 = $this->getProduct(1, 1);
        $product1->setCategory($category);
        $product2 = $this->getProduct(1, 2);
        $product1->setCategory($category);
        $lineItems = new ArrayCollection([$this->getLineItem($product1), $this->getLineItem($product2)]);
        $groupingPath = 'product.category:1';

        $this->expectsGetConfiguredOwner($lineItems, $groupingPath, null);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->willReturn(new OwnershipMetadata('ORGANIZATION', 'organization', 'organization_id'));

        $this->provider->getOwner($lineItems, $groupingPath);
    }

    public function testGetOwnerWhenOwnerSourceIsUser(): void
    {
        $user = new User();
        $checkout = new Checkout();
        $checkout->setOwner($user);
        $lineItem1 = $this->getLineItem($this->getProduct(1, 1));
        $lineItem1->setCheckout($checkout);
        $lineItem2 = $this->getLineItem($this->getProduct(1, 2));
        $lineItem2->setCheckout($checkout);
        $lineItems = new ArrayCollection([$lineItem1, $lineItem2]);

        $this->subOrderOrganizationProvider->expects(self::never())
            ->method('getOrganization');

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $owner = $this->provider->getOwner($lineItems, 'checkout.owner:1');
        self::assertSame($user, $owner);
    }

    public function testGetOwnerWhenOwnerSourceIsBusinessUnit(): void
    {
        $userId = 123;
        $product1 = $this->getProduct(1, 1);
        $product2 = $this->getProduct(1, 2);
        $lineItems = new ArrayCollection([$this->getLineItem($product1), $this->getLineItem($product2)]);
        $groupingPath = 'product.owner:1';

        $this->expectsGetConfiguredOwner($lineItems, $groupingPath, null);

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            $this->getBusinessUnitEnabledUserQuery(),
            [['id_0' => $userId]],
            [1 => 1, 2 => true],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_BOOL]
        );

        $owner = $this->provider->getOwner($lineItems, $groupingPath);
        self::assertEquals($userId, $owner->getId());
    }

    public function testGetOwnerWhenOwnerSourceIsOrganization(): void
    {
        $userId = 123;
        $lineItems = new ArrayCollection([
            $this->getLineItem($this->getProduct(1, 1)),
            $this->getLineItem($this->getProduct(1, 2))
        ]);
        $groupingPath = 'product.organization:1';

        $this->expectsGetConfiguredOwner($lineItems, $groupingPath, null);

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            $this->getOrganizationEnabledUserQuery(),
            [['id_0' => $userId]],
            [1 => 1, 2 => true],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_BOOL]
        );

        $owner = $this->provider->getOwner($lineItems, $groupingPath);
        self::assertEquals($userId, $owner->getId());
    }

    public function testGetOwnerWhenOwnerSourceIsBusinessUnitThatDoesNotHaveEnabledUsers(): void
    {
        $userId = 123;
        $product1 = $this->getProduct(1, 1);
        $product2 = $this->getProduct(1, 2);
        $lineItems = new ArrayCollection([$this->getLineItem($product1), $this->getLineItem($product2)]);
        $groupingPath = 'product.owner:1';

        $this->expectsGetConfiguredOwner($lineItems, $groupingPath, null);

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->addQueryExpectation(
            $this->getBusinessUnitEnabledUserQuery(),
            [],
            [1 => 1, 2 => true],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_BOOL]
        );
        $this->addQueryExpectation(
            $this->getBusinessUnitUserQuery(),
            [['id_0' => $userId]],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $owner = $this->provider->getOwner($lineItems, $groupingPath);
        self::assertEquals($userId, $owner->getId());
    }

    public function testGetOwnerWhenOwnerSourceIsOrganizationThatDoesNotHaveEnabledUsers(): void
    {
        $userId = 123;
        $lineItems = new ArrayCollection([
            $this->getLineItem($this->getProduct(1, 1)),
            $this->getLineItem($this->getProduct(1, 2))
        ]);
        $groupingPath = 'product.organization:1';

        $this->expectsGetConfiguredOwner($lineItems, $groupingPath, null);

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->addQueryExpectation(
            $this->getOrganizationEnabledUserQuery(),
            [],
            [1 => 1, 2 => true],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_BOOL]
        );
        $this->addQueryExpectation(
            $this->getOrganizationUserQuery(),
            [['id_0' => $userId]],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $owner = $this->provider->getOwner($lineItems, $groupingPath);
        self::assertEquals($userId, $owner->getId());
    }

    public function testGetOwnerWhenOwnerSourceIsBusinessUnitThatDoesNotHaveUsers(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to determine order owner.');

        $product1 = $this->getProduct(1, 1);
        $product2 = $this->getProduct(1, 2);
        $lineItems = new ArrayCollection([$this->getLineItem($product1), $this->getLineItem($product2)]);
        $groupingPath = 'product.owner:1';

        $this->expectsGetConfiguredOwner($lineItems, $groupingPath, null);

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->addQueryExpectation(
            $this->getBusinessUnitEnabledUserQuery(),
            [],
            [1 => 1, 2 => true],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_BOOL]
        );
        $this->addQueryExpectation(
            $this->getBusinessUnitUserQuery(),
            [],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $this->provider->getOwner($lineItems, $groupingPath);
    }

    public function testGetOwnerWhenOwnerSourceIsOrganizationThatDoesNotHaveUsers(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to determine order owner.');

        $lineItems = new ArrayCollection([
            $this->getLineItem($this->getProduct(1, 1)),
            $this->getLineItem($this->getProduct(1, 2))
        ]);
        $groupingPath = 'product.organization:1';

        $this->expectsGetConfiguredOwner($lineItems, $groupingPath, null);

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->addQueryExpectation(
            $this->getOrganizationEnabledUserQuery(),
            [],
            [1 => 1, 2 => true],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_BOOL]
        );
        $this->addQueryExpectation(
            $this->getOrganizationUserQuery(),
            [],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $this->provider->getOwner($lineItems, $groupingPath);
    }

    public function testGetOwnerNoLineItems(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to determine order owner.');

        $this->subOrderOrganizationProvider->expects(self::never())
            ->method('getOrganization');

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->provider->getOwner(new ArrayCollection([]), 'product.testField:1');
    }
}
