<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\ImportExport\Normalizer;

use Oro\Bundle\CatalogBundle\ImportExport\Normalizer\CategoryNormalizer;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class CategoryNormalizerTest extends WebTestCase
{
    use CatalogTrait;

    private Context $context;
    private CategoryNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadCategoryData::class,
            LoadOrganization::class,
        ]);

        $container = $this->getContainer();

        $container->get('oro_importexport.field.database_helper')->onClear();
        $this->normalizer = new CategoryNormalizer($container->get('oro_entity.helper.field_helper'));
        $this->normalizer->setDispatcher($container->get('event_dispatcher'));
        $this->normalizer->setSerializer($container->get('oro_importexport.serializer'));
        $this->normalizer->setCategoryImportExportHelper(
            $container->get('oro_catalog.importexport.helper.category_import_export')
        );
        $this->normalizer->setDoctrine($container->get('doctrine'));
    }

    protected function tearDown(): void
    {
        $this->getContainer()->get('oro_importexport.field.database_helper')->onClear();

        parent::tearDown();
    }

    public function testNormalizeOrganizationIsAbsent(): void
    {
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $data = $this->normalizer->normalize($category);

        $this->assertArrayNotHasKey('organization', $data);
    }

    public function testNormalizeParentCategoryTitleIsPresent(): void
    {
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $data = $this->normalizer->normalize($category);

        $this->assertEquals(
            'All Products / ' . LoadCategoryData::FIRST_LEVEL,
            $data['parentCategory']['titles']['default']['string'] ?? ''
        );
    }

    public function testNormalizeParentCategoryTitleIsAbsentWhenNotFullMode(): void
    {
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $data = $this->normalizer->normalize($category, null, ['mode' => 'short']);

        $this->assertArrayNotHasKey('parentCategory', $data);
    }

    public function testNormalizeParentCategoryTitleIsAbsentWhenRootCategory(): void
    {
        $organization = $this->getReference('organization');
        $token = new UsernamePasswordOrganizationToken('user', 'password', 'key', $organization);
        $this->getContainer()->get('security.token_storage')->setToken($token);

        $rootCategory = $this->getRootCategory();

        $data = $this->normalizer->normalize($rootCategory);

        $this->assertNull($data['parentCategory']);
    }

    public function testNormalizeWhenParentCategoryIsBroken(): void
    {
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        // attempt to simulate that parent category is cleared and broken.
        $category->setParentCategory(new \Proxies\__CG__\Oro\Bundle\CatalogBundle\Entity\Category());

        $data = $this->normalizer->normalize($category);

        $this->assertEquals(
            'All Products / ' . LoadCategoryData::FIRST_LEVEL,
            $data['parentCategory']['titles']['default']['string'] ?? ''
        );
    }
}
