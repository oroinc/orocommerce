<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Functional\EventListener\Doctrine;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\ProductSuggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\SuggestionRepository;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;

/**
 * @dbIsolationPerTest
 */
final class CreateProductSuggestionListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    private ObjectManager $manager;
    private SuggestionRepository $suggestionRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadUser::class,
            LoadProductData::class
        ]);

        $this->manager = $this->getReferenceRepository()->getManager();

        $this->getOptionalListenerManager()->enableListener(
            'oro_website_search_suggestion.entity_listener.doctrine.create_product_suggestion'
        );

        $this->suggestionRepository = $this->getReferenceRepository()->getManager()->getRepository(Suggestion::class);
    }

    public function testThatNewSuggestionsCreatedWhenProductCreated(): void
    {
        self::assertEmpty($this->suggestionRepository->findAll());

        /** @var EnumOptionInterface[] $enumInventoryStatuses */
        $inStockInventoryStatus = $this->manager->getRepository(EnumOption::class)->findOneBy([
            'id' => ExtendHelper::buildEnumOptionId('prod_inventory_status', Product::INVENTORY_STATUS_IN_STOCK)
        ]);

        $product = new Product();
        $product->setDefaultName('product-1');
        $product->setSku('sku');
        $product->setStatus(Product::STATUS_ENABLED);
        $product->setInventoryStatus($inStockInventoryStatus);
        $product->setOrganization($this->getReference(LoadUser::USER)->getOrganization());

        $this->manager->persist($product);
        $this->manager->flush();

        $this->consumeAllMessages();

        self::assertCount(6, $this->suggestionRepository->findAll());
    }

    public function testThatSuggestionsUpdatedWhenProductUpdated(): void
    {
        self::assertEmpty($this->suggestionRepository->findAll());

        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $product->setDefaultName('another name');
        $product->setSku('another sku');

        $this->manager->flush();

        $this->consumeAllMessages();

        self::assertCount(14, $this->suggestionRepository->findAll());
    }

    public function testThatNewProductsAddedForAlreadyExistedSuggestions(): void
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $product2*/
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        $suggestion = new Suggestion();
        $suggestion->setPhrase('product-1');
        $suggestion->setOrganization($product1->getOrganization());
        $suggestion->setWordsCount(1);
        $suggestion->setLocalization($this->getReference(LoadLocalizationData::DEFAULT_LOCALIZATION_CODE));

        $productSuggestion = new ProductSuggestion();
        $productSuggestion->setProduct($product1);
        $productSuggestion->setSuggestion($suggestion);

        $this->manager->persist($productSuggestion);
        $this->manager->persist($suggestion);

        $this->manager->flush();

        $product2->getName()->setString('product-1');

        $this->manager->flush();

        $this->consumeAllMessages();

        $productSuggestions = $this->manager->getRepository(ProductSuggestion::class)->findBy([
            'suggestion' => $suggestion
        ]);

        self::assertCount(2, $productSuggestions);
        self::assertEquals(
            array_map(
                fn (ProductSuggestion $productSuggestion) => $productSuggestion->getProduct()->getId(),
                $productSuggestions
            ),
            [$product1->getId(), $product2->getId()]
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->getOptionalListenerManager()->disableListener(
            'oro_website_search_suggestion.entity_listener.doctrine.create_product_suggestion'
        );

        parent::tearDown();
    }
}
