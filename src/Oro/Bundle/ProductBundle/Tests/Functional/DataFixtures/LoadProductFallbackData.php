<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Products in every state the fallback population has to handle, and a category chain that resolves a value
 * through several levels of fallbacks.
 */
class LoadProductFallbackData extends AbstractFixture implements DependentFixtureInterface
{
    public const PRODUCT_WITHOUT_CATEGORY = 'product_fallback.without_category';
    public const PRODUCT_NESTED = 'product_fallback.nested';
    public const PRODUCT_PARTIAL = 'product_fallback.partial';
    public const PRODUCT_FILLED = 'product_fallback.filled';

    /** The value the top category of the chain holds, resolved through two levels of parent fallbacks. */
    public const NESTED_MANAGE_INVENTORY = 1;

    /** The value the partially populated product already owns and that must survive the population. */
    public const PARTIAL_MANAGE_INVENTORY = 0;
    public const PARTIAL_PAGE_TEMPLATE = ['oro_product_frontend_product_view' => 'short'];

    /** Every field the population fills. */
    public const FALLBACK_FIELDS = [
        'pageTemplate',
        'manageInventory',
        'highlightLowInventory',
        'inventoryThreshold',
        'lowInventoryThreshold',
        'backOrder',
        'decrementQuantity',
        'minimumQuantityToOrder',
        'maximumQuantityToOrder',
        'isUpcoming',
    ];

    private PropertyAccessorInterface $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadUser::class,
            LoadAttributeFamilyData::class,
            LoadCategoryData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->loadCategoryChain($manager);

        $references = [
            self::PRODUCT_WITHOUT_CATEGORY,
            self::PRODUCT_NESTED,
            self::PRODUCT_PARTIAL,
            self::PRODUCT_FILLED,
        ];

        $products = [];
        foreach ($references as $reference) {
            $products[$reference] = $this->createProduct($manager, $reference);
        }

        /** @var Category $deepestCategory */
        $deepestCategory = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $deepestCategory->addProduct($products[self::PRODUCT_NESTED]);

        $manager->flush();

        // The fields are cleared explicitly instead of relying on which of them a prePersist listener fills,
        // so every product starts from a state this fixture states outright.
        foreach ($products as $product) {
            $this->clearFallbackFields($manager, $product);
        }
        $manager->flush();

        $this->propertyAccessor->setValue(
            $products[self::PRODUCT_PARTIAL],
            'manageInventory',
            $this->createFallbackValue($manager, scalarValue: self::PARTIAL_MANAGE_INVENTORY)
        );
        $this->propertyAccessor->setValue(
            $products[self::PRODUCT_PARTIAL],
            'pageTemplate',
            $this->createFallbackValue($manager, arrayValue: self::PARTIAL_PAGE_TEMPLATE)
        );

        foreach (self::FALLBACK_FIELDS as $field) {
            $this->propertyAccessor->setValue(
                $products[self::PRODUCT_FILLED],
                $field,
                $this->createFallbackValue($manager, scalarValue: 1)
            );
        }

        $manager->flush();
    }

    /**
     * category_1 owns the value, category_1_2 and category_1_2_3 only point at their parent, so resolving the
     * field for a product of category_1_2_3 has to walk two fallbacks before it reaches a value.
     */
    private function loadCategoryChain(ObjectManager $manager): void
    {
        /** @var Category $top */
        $top = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $this->propertyAccessor->setValue(
            $top,
            'manageInventory',
            $this->createFallbackValue($manager, scalarValue: self::NESTED_MANAGE_INVENTORY)
        );

        // The middle category only points at its parent. The category of the product itself is left empty on
        // purpose: that is the state in which the population changes what the field resolves to.
        $middle = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $this->propertyAccessor->setValue(
            $middle,
            'manageInventory',
            $this->createFallbackValue($manager, fallback: ParentCategoryFallbackProvider::FALLBACK_ID)
        );

        $manager->flush();
    }

    private function createProduct(ObjectManager $manager, string $reference): Product
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        $name = new ProductName();
        $name->setString($reference);

        $product = new Product();
        $product->setSku(strtoupper(str_replace('.', '-', $reference)))
            ->setOwner($user->getOwner())
            ->setOrganization($user->getOrganization())
            ->setAttributeFamily($this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1))
            ->setStatus(Product::STATUS_ENABLED)
            ->addName($name);

        $manager->persist($product);
        $this->addReference($reference, $product);

        return $product;
    }

    private function clearFallbackFields(ObjectManager $manager, Product $product): void
    {
        foreach (self::FALLBACK_FIELDS as $field) {
            $value = $this->propertyAccessor->getValue($product, $field);
            if ($value instanceof EntityFieldFallbackValue) {
                $this->propertyAccessor->setValue($product, $field, null);
                $manager->remove($value);
            }
        }
    }

    private function createFallbackValue(
        ObjectManager $manager,
        ?string $fallback = null,
        mixed $scalarValue = null,
        ?array $arrayValue = null
    ): EntityFieldFallbackValue {
        $value = new EntityFieldFallbackValue();
        $value->setFallback($fallback);
        $value->setScalarValue($scalarValue);
        $value->setArrayValue($arrayValue);

        $manager->persist($value);

        return $value;
    }
}
