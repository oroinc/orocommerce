<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\ImportExport\Strategy;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\ImportExport\Strategy\CategoryAddOrReplaceStrategy;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Component\Testing\Unit\EntityTrait;

class CategoryAddOrReplaceStrategyTest extends WebTestCase
{
    use EntityTrait;

    /** @var Context */
    private $context;

    /** @var CategoryAddOrReplaceStrategy */
    private $strategy;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadCategoryData::class,
                LoadOrganization::class,
            ]
        );

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
            $container->get('oro_security.owner.checker')
        );
        $this->strategy->setRelatedEntityStateHelper(
            $container->get('oro_importexport.field.related_entity_state_helper')
        );
        $this->strategy->setLocalizedFallbackValueClass(
            $container->getParameter('oro_locale.entity.localized_fallback_value.class')
        );
        $this->strategy->setCategoryImportExportHelper(
            $container->get('oro_catalog.importexport.helper.category_import_export')
        );

        $this->strategy->setEntityName(Category::class);
        $this->strategy->setImportExportContext($this->context = new Context([]));

        $organization = $this->getReference('organization');
        $token = new UsernamePasswordOrganizationToken('user', 'password', 'key', $organization);
        $this->getContainer()->get('security.token_storage')->setToken($token);
    }

    protected function tearDown()
    {
        $this->getContainer()->get('oro_importexport.strategy.new_entities_helper')->onClear();
        $this->getContainer()->get('oro_importexport.field.database_helper')->onClear();

        parent::tearDown();
    }

    /**
     * @dataProvider parentCategoryDataProvider
     *
     * @param string|null $parentCategory
     * @param string|null $expectedParentCategory
     */
    public function testProcessWhenParentCategoryLoadedByPath(
        ?string $parentCategory,
        ?string $expectedParentCategory
    ): void {
        $this->context->setValue('rawItemData', ['parentCategory.title' => $parentCategory]);

        $category = new Category();
        $category->setParentCategory(new Category());

        $this->assertSame(
            $expectedParentCategory ? $this->getReference($expectedParentCategory) : null,
            $this->strategy->process($category)->getParentCategory()
        );
    }

    /**
     * @return array
     */
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

        $category = new Category();
        $expectedCategory = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $category->setParentCategory($this->getEntity(Category::class, ['id' => $expectedCategory->getId()]));

        $this->assertSame($expectedCategory, $this->strategy->process($category)->getParentCategory());
    }

    public function testProcessWhenParentCategoryIsNew(): void
    {
        $newCategory = $this->strategy->process($this->createCategoryWithTitle('new_category'));

        $category = new Category();
        $category->setParentCategory(new Category());

        $this->context->setValue('rawItemData', ['parentCategory.title' => 'All Products / new_category']);

        $this->assertSame(
            $newCategory,
            $this->strategy->process($category)->getParentCategory()
        );
    }

    public function testProcessGedmoFieldsAreFilled(): void
    {
        $category = $this->strategy->process(new Category());

        $categoryRepo = $this->getCategoryRepository();

        $rootCategory = $categoryRepo->getMasterCatalogRoot($this->getReference('organization'));
        $categoriesCount = $categoryRepo->getCategoriesCount();

        $this->assertEquals(0, $category->getLevel(), 'Gedmo level field is invalid');
        $this->assertEquals($categoriesCount, $category->getLeft(), 'Gedmo left field is invalid');
        $this->assertEquals(0, $category->getRight(), 'Gedmo right field is invalid');
        $this->assertEquals($rootCategory->getId(), $category->getRoot(), 'Gedmo root is invalid');
    }

    public function testProcessWhenRootHasParent(): void
    {
        $rootCategory = $this->getRootCategory();
        $rootCategory->setParentCategory(new Category());

        $category = $this->strategy->process($rootCategory);

        $this->assertNull($category);
        $this->assertContains(
            'Error in row #0. Skipping category "All Products". Root category cannot have a parent',
            $this->context->getErrors()
        );
    }

    public function testProcessParentIsSetToRootByDefault(): void
    {
        $rootCategory = $this->getRootCategory();

        /** @var Category $category */
        $category = $this->strategy->process(new Category());

        $this->assertEquals($rootCategory->getId(), $category->getParentCategory()->getId());
    }

    public function testProcessWhenParentIsNotFoundById(): void
    {
        $category = $this->createCategoryWithTitle('sample category');
        $category->setParentCategory($this->getEntity(Category::class, ['id' => '999999999']));

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
        $category->setParentCategory(new Category());

        $this->context->setValue('rawItemData', $itemData = ['parentCategory.title' => 'All Products / non-existing']);

        $category = $this->strategy->process($category);

        $this->assertNull($category);
        $this->assertContains(
            'Row #0. Postponing category "sample category". Cannot find parent category "All Products / non-existing"',
            $this->context->getErrors()
        );
        $this->assertContains($itemData, $this->context->getPostponedRows());
    }

    /**
     * @param string $title
     *
     * @return Category
     */
    private function createCategoryWithTitle(string $title): Category
    {
        return (new Category())->addTitle(
            (new LocalizedFallbackValue())->setString($title)
        );
    }

    /**
     * @return CategoryRepository
     */
    private function getCategoryRepository(): CategoryRepository
    {
        /** @var CategoryRepository $categoryRepo */
        $categoryRepo = $this->getContainer()->get('doctrine')->getRepository(Category::class);

        return $categoryRepo;
    }

    /**
     * @return Category
     */
    private function getRootCategory(): Category
    {
        $categoryRepo = $this->getCategoryRepository();
        $rootCategory = $categoryRepo->getMasterCatalogRoot($this->getReference('organization'));

        return $rootCategory;
    }
}
