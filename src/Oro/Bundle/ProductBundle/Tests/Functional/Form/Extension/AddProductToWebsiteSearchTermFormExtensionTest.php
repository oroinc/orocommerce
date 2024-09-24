<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Form\Extension;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Form\Type\SearchTermType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormFactoryInterface;

class AddProductToWebsiteSearchTermFormExtensionTest extends WebTestCase
{
    use FormAwareTestTrait;

    private FormFactoryInterface $formFactory;

    private string $phraseDelimiter;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadProductData::class,
        ]);

        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);
        $this->phraseDelimiter = self::getContainer()->getParameter('oro_website_search_term.phrase_delimiter');
    }

    public function testFormContainsFields(): void
    {
        $form = $this->formFactory->create(SearchTermType::class);

        self::assertFormOptions(
            $form,
            [
                'disable_fields_if' => [
                    'redirectProduct' => 'data.actionType != "redirect" || data.redirectActionType != "product"',
                ],
            ]
        );

        self::assertFormHasField(
            $form,
            'redirectActionType',
            ChoiceType::class,
            [
                'required' => true,
                'choices' => [
                    'oro.websitesearchterm.searchterm.redirect_action_type.choices.product.label' => 'product',
                ],
            ]
        );

        self::assertFormHasField(
            $form,
            'redirectProduct',
            ProductSelectType::class,
            [
                'required' => true,
                // Enables also configurable products.
                'autocomplete_alias' => 'oro_all_product_visibility_limited',
                'grid_name' => 'all-products-select-grid',
                'create_enabled' => false,
            ]
        );
    }

    public function testFieldsAreDisabledWhenActionTypeIsModify(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $searchTerm = (new SearchTerm())
            ->setActionType('redirect')
            ->setRedirectProduct($product);

        $form = $this->formFactory->create(SearchTermType::class, $searchTerm, ['csrf_protection' => false]);

        $form->submit(
            [
                'phrases' => 'sample phrase',
                'actionType' => 'modify',
                'modifyActionType' => 'original_results',
            ]
        );

        self::assertFormOptions($form->get('redirectProduct'), ['disabled' => true]);

        self::assertNull($searchTerm->getRedirectProduct());
    }

    public function testFieldsAreDisabledWhenActionTypeIsRedirectProduct(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $searchTerm = (new SearchTerm())
            ->setActionType('modify')
            ->setModifyActionType('original_results');

        $form = $this->formFactory->create(SearchTermType::class, $searchTerm, ['csrf_protection' => false]);

        $form->submit(
            [
                'phrases' => 'sample phrase',
                'actionType' => 'redirect',
                'redirectActionType' => 'product',
                'redirectProduct' => $product->getId(),
            ]
        );

        self::assertFormOptions($form->get('redirectProduct'), ['disabled' => false]);

        self::assertEquals('redirect', $searchTerm->getActionType());
        self::assertEquals('product', $searchTerm->getRedirectActionType());
        self::assertEquals($product, $searchTerm->getRedirectProduct());
        self::assertNull($searchTerm->getModifyActionType());
    }
}
