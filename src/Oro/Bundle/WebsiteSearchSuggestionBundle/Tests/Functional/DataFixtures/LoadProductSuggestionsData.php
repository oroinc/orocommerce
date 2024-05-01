<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\ProductSuggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads Product Suggestions data into database
 */
class LoadProductSuggestionsData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    public const SUGGESTION_WITHOUT_PRODUCT = 'no_product_suggestion';

    public const SUGGESTION_WITH_PRODUCT = 'with_product_suggestion';

    public const PRODUCT_SUGGESTION_1 = 'product_suggestion_1';

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadProductData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /**
         * @var Product $product1
         */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $localization = $this->getReference(LoadLocalizationData::DEFAULT_LOCALIZATION_CODE);

        $suggestion = new Suggestion();
        $suggestion->setPhrase('suggest1');
        $suggestion->setOrganization($product1->getOrganization());
        $suggestion->setWordsCount(1);
        $suggestion->setLocalization($localization);

        $manager->persist($suggestion);
        $this->setReference(self::SUGGESTION_WITHOUT_PRODUCT, $suggestion);


        $suggestion = new Suggestion();
        $suggestion->setPhrase('suggest2');
        $suggestion->setOrganization($product1->getOrganization());
        $suggestion->setWordsCount(1);
        $suggestion->setLocalization($localization);

        $manager->persist($suggestion);

        $productSuggestion = new ProductSuggestion();
        $productSuggestion->setProduct($product1);
        $productSuggestion->setSuggestion($suggestion);

        $manager->persist($productSuggestion);
        $this->setReference(self::SUGGESTION_WITH_PRODUCT, $suggestion);
        $this->setReference(self::PRODUCT_SUGGESTION_1, $productSuggestion);

        $manager->flush();
    }
}
