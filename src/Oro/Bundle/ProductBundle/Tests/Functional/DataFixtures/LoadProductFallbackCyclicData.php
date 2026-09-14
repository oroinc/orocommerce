<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;

/**
 * Makes the fallbacks of two categories point at each other, so resolving the field of
 * {@see LoadProductFallbackData::PRODUCT_NESTED} runs in a loop.
 *
 * Both categories need their own fallback row: an empty field would stop the resolution at the next level and
 * the loop would never be entered. The parent is repointed with a plain query on purpose, because the category
 * tree is a Gedmo nested set whose listener would recalculate it away.
 */
class LoadProductFallbackCyclicData extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadProductFallbackData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        /** @var Category $middle */
        $middle = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        /** @var Category $deepest */
        $deepest = $this->getReference(LoadCategoryData::THIRD_LEVEL1);

        $value = new EntityFieldFallbackValue();
        $value->setFallback(ParentCategoryFallbackProvider::FALLBACK_ID);
        $manager->persist($value);
        PropertyAccess::createPropertyAccessor()->setValue($deepest, 'manageInventory', $value);
        $manager->flush();

        // category_1_2 -> category_1_2_3 -> category_1_2
        $metadata = $manager->getClassMetadata(Category::class);
        $manager->getConnection()->executeStatement(
            sprintf(
                'UPDATE %s SET %s = :parent WHERE id = :id',
                $metadata->getTableName(),
                $metadata->getSingleAssociationJoinColumnName('parentCategory')
            ),
            ['parent' => $deepest->getId(), 'id' => $middle->getId()]
        );
    }
}
