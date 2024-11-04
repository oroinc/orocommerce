<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Form\Extension;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Form\Type\SearchTermType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormFactoryInterface;

class AddCategoryToWebsiteSearchTermFormExtensionTest extends WebTestCase
{
    use FormAwareTestTrait;

    private FormFactoryInterface $formFactory;

    private string $phraseDelimiter;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadCategoryData::class,
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
                    'redirectCategory' => 'data.actionType != "redirect" || data.redirectActionType != "category"',
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
                    'oro.websitesearchterm.searchterm.redirect_action_type.choices.category.label' => 'category',
                ],
            ]
        );

        self::assertFormHasField(
            $form,
            'redirectCategory',
            CategoryTreeType::class,
            [
                'required' => true,
                'error_bubbling' => false,
            ]
        );
    }

    public function testFieldsAreDisabledWhenActionTypeIsModify(): void
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $searchTerm = (new SearchTerm())
            ->setActionType('redirect')
            ->setRedirectCategory($category);

        $form = $this->formFactory->create(SearchTermType::class, $searchTerm, ['csrf_protection' => false]);

        $form->submit(
            [
                'phrases' => 'sample phrase',
                'actionType' => 'modify',
                'modifyActionType' => 'original_results',
            ]
        );

        self::assertFormOptions($form->get('redirectCategory'), ['disabled' => true]);

        self::assertNull($searchTerm->getRedirectCategory());
    }

    public function testFieldsAreDisabledWhenActionTypeIsRedirectCategory(): void
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $searchTerm = (new SearchTerm())
            ->setActionType('modify')
            ->setModifyActionType('original_results');

        $form = $this->formFactory->create(SearchTermType::class, $searchTerm, ['csrf_protection' => false]);

        $form->submit(
            [
                'phrases' => 'sample phrase',
                'actionType' => 'redirect',
                'redirectActionType' => 'category',
                'redirectCategory' => $category->getId(),
            ]
        );

        self::assertFormOptions($form->get('redirectCategory'), ['disabled' => false]);

        self::assertEquals('redirect', $searchTerm->getActionType());
        self::assertEquals('category', $searchTerm->getRedirectActionType());
        self::assertEquals($category, $searchTerm->getRedirectCategory());
        self::assertNull($searchTerm->getModifyActionType());
    }
}
