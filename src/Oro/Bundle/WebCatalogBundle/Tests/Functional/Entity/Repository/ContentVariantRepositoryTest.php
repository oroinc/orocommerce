<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantsData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantSlugsData;

class ContentVariantRepositoryTest extends WebTestCase
{
    /**
     * @var ContentVariantRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadContentVariantSlugsData::class
            ]
        );
        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(ContentVariant::class)
            ->getRepository(ContentVariant::class);
    }

    public function testFindVariantBySlugFound()
    {
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_USER);
        /** @var ContentVariant $expectedVariant */
        $expectedVariant = $this->getReference(LoadContentVariantsData::CUSTOMER_VARIANT);
        $this->assertEquals($expectedVariant, $this->repository->findVariantBySlug($slug));
    }

    public function testFindVariantBySlugNotFound()
    {
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);
        $this->assertNull($this->repository->findVariantBySlug($slug));
    }
}
