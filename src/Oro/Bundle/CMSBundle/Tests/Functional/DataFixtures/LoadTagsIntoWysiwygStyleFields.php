<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadTagsIntoWysiwygStyleFields extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    public const PRODUCT_1_DESCRIPTION = LoadProductData::PRODUCT_1 . '-description';

    private string $cssWithTags = 'body { color: black; }</style><st<sTyLe id="style_tag">yle></sTyLe><script>';

    public function getDependencies()
    {
        return [
            LoadProductData::class,
            LoadPageData::class,
            LoadTextContentVariantsData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Page $page */
        $page = $this->getReference(LoadPageData::PAGE_1);
        $page->setContentStyle($this->cssWithTags);

        $manager->persist($page);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $description = $product->getDescription();
        $description->setWysiwygStyle($this->cssWithTags);
        $this->setReference(self::PRODUCT_1_DESCRIPTION, $description);
        $manager->persist($product);

        /** @var TextContentVariant $textContentVariant */
        $textContentVariant = $this->getReference('text_content_variant1');
        $textContentVariant->setContentStyle($this->cssWithTags);
        $manager->persist($textContentVariant);

        $manager->flush();
    }
}
