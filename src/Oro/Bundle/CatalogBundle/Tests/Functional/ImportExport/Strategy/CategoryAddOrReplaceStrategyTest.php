<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\ImportExport\Strategy;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\ImportExport\Strategy\CategoryAddOrReplaceStrategy;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoryAddOrReplaceStrategyTest extends WebTestCase
{
    use CatalogTrait;

    private Context $context;
    private CategoryAddOrReplaceStrategy $strategy;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadCategoryData::class,
            LoadOrganization::class,
        ]);

        $container = $this->getContainer();

        $container->get('oro_importexport.strategy.new_entities_helper')->onClear();
        $container->get('oro_importexport.field.database_helper')->onClear();

        $this->strategy = new CategoryAddOrReplaceStrategy(
            $container->get('event_dispatcher'),
            $container->get('oro_importexport.strategy.import.helper'),
            $container->get('oro_entity.helper.field_helper'),
            $container->get('oro_importexport.field.database_helper'),
            $container->get('oro_entity.entity_class_name_provider'),
            $container->get('translator'),
            $container->get('oro_importexport.strategy.new_entities_helper'),
            $container->get('oro_entity.doctrine_helper'),
            $container->get('oro_importexport.field.related_entity_state_helper')
        );
        $this->strategy->setOwnershipSetter($container->get('oro_organization.entity_ownership_associations_setter'));
        $this->strategy->setLocalizedFallbackValueClass(LocalizedFallbackValue::class);
        $this->strategy->setCategoryImportExportHelper(
            $container->get('oro_catalog.importexport.helper.category_import_export')
        );
        $this->strategy->setTokenAccessor($container->get('oro_security.token_accessor'));
        $this->strategy->setEntityName(Category::class);

        $this->context = new Context([]);
        $this->context->setValue('itemData', []);

        $this->strategy->setImportExportContext($this->context);

        $organization = $this->getReference('organization');
        $token = new UsernamePasswordOrganizationToken('user', 'password', 'key', $organization);
        $this->getContainer()->get('security.token_storage')->setToken($token);
    }

    protected function tearDown(): void
    {
        $this->getContainer()->get('oro_importexport.strategy.new_entities_helper')->onClear();
        $this->getContainer()->get('oro_importexport.field.database_helper')->onClear();

        parent::tearDown();
    }

    /**
     * @dataProvider parentCategoryDataProvider
     */
    public function testProcessWhenParentCategoryLoadedByPath(
        ?string $parentCategory,
        ?string $expectedParentCategory
    ): void {
        $this->context->setValue('rawItemData', ['parentCategory.title' => $parentCategory]);

        $category = $this->createCategoryWithTitle('title');
        $category->setParentCategory($this->createCategory());

        $this->assertSame(
            $expectedParentCategory ? $this->getReference($expectedParentCategory) : null,
            $this->strategy->process($category)->getParentCategory()
        );
    }

    public function parentCategoryDataProvider(): array
    {
        return [
            'parent category is loaded by name' => [
                'parentCategory' => LoadCategoryData::FIRST_LEVEL,
                'expectedParentCategory' => LoadCategoryData::FIRST_LEVEL,
            ],
            'parent category is loaded by path' => [
                'parentCategory' => LoadCategoryData::FIRST_LEVEL . ' / '
                    . LoadCategoryData::SECOND_LEVEL1 . ' / '
                    . LoadCategoryData::THIRD_LEVEL1,
                'expectedParentCategory' => LoadCategoryData::THIRD_LEVEL1,
            ],
        ];
    }

    public function testProcessWhenParentCategoryLoadedById(): void
    {
        $this->context->setValue(
            'rawItemData',
            ['parentCategory.title' => LoadCategoryData::FIRST_LEVEL]
        );

        $category = $this->createCategoryWithTitle('title');
        $expectedCategory = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $category->setParentCategory($this->createCategory($expectedCategory->getId()));

        $this->assertSame($expectedCategory, $this->strategy->process($category)->getParentCategory());
    }

    public function testProcessWhenParentCategoryIsNew(): void
    {
        $newCategory = $this->strategy->process($this->createCategoryWithTitle('new_category'));

        $category = $this->createCategoryWithTitle('title');
        $category->setParentCategory($this->createCategory());

        $this->context->setValue('rawItemData', ['parentCategory.title' => 'All Products / new_category']);

        $this->assertSame(
            $newCategory,
            $this->strategy->process($category)->getParentCategory()
        );
    }

    public function testProcessGedmoFieldsAreFilled(): void
    {
        $category = $this->strategy->process(
            $this->createCategoryWithTitle('title')
        );

        /** @var CategoryRepository $categoryRepo */
        $categoryRepo = $this->getContainer()->get('doctrine')->getRepository(Category::class);
        $rootCategory = $this->getRootCategory();
        $maxLeft = $categoryRepo->getMaxLeft();

        $this->assertEquals(0, $category->getLevel(), 'Gedmo level field is invalid');
        $this->assertEquals($maxLeft, $category->getLeft(), 'Gedmo left field is invalid');
        $this->assertEquals(0, $category->getRight(), 'Gedmo right field is invalid');
        $this->assertEquals($rootCategory->getId(), $category->getRoot(), 'Gedmo root is invalid');
    }

    public function testProcessWhenRootHasParent(): void
    {
        $rootCategory = $this->getRootCategory();
        $rootCategory->setParentCategory(
            $this->createCategoryWithTitle('title')
        );

        $category = $this->strategy->process($rootCategory);

        $this->assertNull($category);
        static::assertContains(
            'Error in row #0. Skipping category "All Products". Root category cannot have a parent',
            $this->context->getErrors()
        );
    }

    public function testProcessParentIsSetToRootByDefault(): void
    {
        $rootCategory = $this->getRootCategory();

        /** @var Category $category */
        $category = $this->strategy->process(
            $this->createCategoryWithTitle('title')
        );

        $this->assertEquals($rootCategory->getId(), $category->getParentCategory()->getId());
    }

    public function testProcessWhenParentIsNotFoundById(): void
    {
        $category = $this->createCategoryWithTitle('sample category');
        $category->setParentCategory($this->createCategory('999999999'));

        $category = $this->strategy->process($category);

        $this->assertNull($category);
        $this->assertContains(
            'Error in row #0. Skipping category "sample category". Cannot find parent category with id "999999999"',
            $this->context->getErrors()
        );
    }

    public function testProcessWhenParentIsNotFoundByTitle(): void
    {
        $category = $this->createCategoryWithTitle('sample category');
        $category->setParentCategory($this->createCategory());

        $this->context->setValue('rawItemData', $itemData = ['parentCategory.title' => 'All Products / non-existing']);

        $category = $this->strategy->process($category);

        $this->assertNull($category);
        $this->assertContains(
            'Row #0. Cannot find parent category "All Products / non-existing". Pushing category' .
            ' "sample category" to the end of the queue.',
            $this->context->getErrors()
        );
        $this->assertContains($itemData, $this->context->getPostponedRows());
    }

    public function testSlugGenerateWhenNotProvided(): void
    {
        $category = $this->createCategoryWithTitle('test category');
        $category->setParentCategory($this->getReference(LoadCategoryData::FIRST_LEVEL));

        $this->strategy->process($category);

        $this->assertEquals(
            [(new LocalizedFallbackValue())->setString('test-category')->setSerializedData(null)],
            $category->getSlugPrototypes()->toArray()
        );
    }

    public function testProcessWhenCategoryHasNoTitle()
    {
        $category = $this->strategy->process($this->createCategory());

        $this->assertNull($category);
        $this->assertContains(
            'Error in row #0. Title Localization Name: Category Title should not be blank.',
            $this->context->getErrors()
        );
    }

    private function createCategory(int $id = null): Category
    {
        $category = new Category();
        if (null !== $id) {
            ReflectionUtil::setId($category, $id);
        }

        return $category;
    }

    private function createCategoryWithTitle(string $title): Category
    {
        $category = $this->createCategory();
        $category->addTitle((new CategoryTitle())->setString($title));

        return $category;
    }
}
