<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryWithImageData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class FeaturedCategoriesProviderTest extends FrontendWebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadCategoryWithImageData::class,
                LoadOrganization::class,
            ]
        );

        $this->setCurrentWebsite('default');

        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $token = new UsernamePasswordOrganizationToken('user', 'password', 'key', $organization);
        self::getContainer()->get('security.token_storage')->setToken($token);
    }

    protected function tearDown(): void
    {
        self::getContainer()->get('oro_catalog.layout.data_provider.category.cache')->deleteAll();

        parent::tearDown();
    }

    public function testGetAll(): void
    {
        self::getContainer()->get('oro_catalog.layout.data_provider.category.cache')->deleteAll();
        $provider = self::getContainer()->get('oro_catalog.layout.data_provider.featured_categories');

        $this->assertFeaturedCategories($provider->getAll());

        // Check cached value
        $this->assertFeaturedCategories($provider->getAll());
    }

    private function assertFeaturedCategories(array $featuredCategories): void
    {
        $expectedResult = [
            [
                'id' => $this->getReference(LoadCategoryWithImageData::FIRST_LEVEL)->getId(),
                'title' => LoadCategoryWithImageData::FIRST_LEVEL,
                'short' => '',
            ],
        ];

        self::assertArrayIntersectEquals($expectedResult, $featuredCategories);
        self::assertArrayHasKey('small_image', $featuredCategories[0]);

        $smallImage = $featuredCategories[0]['small_image'];
        self::assertInstanceOf(File::class, $smallImage);
        self::assertEquals('small_image.png', $smallImage->getOriginalFilename());
    }
}
