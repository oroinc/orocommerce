<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Form\Extension;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\Type\PageSelectType;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Form\Type\SearchTermType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormFactoryInterface;

class AddPageToWebsiteSearchTermFormExtensionTest extends WebTestCase
{
    use FormAwareTestTrait;

    private FormFactoryInterface $formFactory;

    private string $phraseDelimiter;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadPageData::class,
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
                    'redirectCmsPage' => 'data.actionType != "redirect" || data.redirectActionType != "cms_page"',
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
                    'oro.websitesearchterm.searchterm.redirect_action_type.choices.cms_page.label' => 'cms_page',
                ],
            ]
        );

        self::assertFormHasField(
            $form,
            'redirectCmsPage',
            PageSelectType::class,
            [
                'required' => true,
                'create_enabled' => false,
            ]
        );
    }

    public function testFieldsAreDisabledWhenActionTypeIsModify(): void
    {
        /** @var Page $page */
        $page = $this->getReference(LoadPageData::PAGE_1);
        $searchTerm = (new SearchTerm())
            ->setActionType('redirect')
            ->setRedirectCmsPage($page);

        $form = $this->formFactory->create(SearchTermType::class, $searchTerm, ['csrf_protection' => false]);

        $form->submit(
            [
                'phrases' => 'sample phrase',
                'actionType' => 'modify',
                'modifyActionType' => 'original_results',
            ]
        );

        self::assertFormOptions($form->get('redirectCmsPage'), ['disabled' => true]);

        self::assertNull($searchTerm->getRedirectCmsPage());
    }

    public function testFieldsAreDisabledWhenActionTypeIsRedirectPage(): void
    {
        /** @var Page $page */
        $page = $this->getReference(LoadPageData::PAGE_1);
        $searchTerm = (new SearchTerm())
            ->setActionType('modify')
            ->setModifyActionType('original_results');

        $form = $this->formFactory->create(SearchTermType::class, $searchTerm, ['csrf_protection' => false]);

        $form->submit(
            [
                'phrases' => 'sample phrase',
                'actionType' => 'redirect',
                'redirectActionType' => 'cms_page',
                'redirectCmsPage' => $page->getId(),
            ]
        );

        self::assertFormOptions($form->get('redirectCmsPage'), ['disabled' => false]);

        self::assertEquals('redirect', $searchTerm->getActionType());
        self::assertEquals('cms_page', $searchTerm->getRedirectActionType());
        self::assertEquals($page, $searchTerm->getRedirectCmsPage());
        self::assertNull($searchTerm->getModifyActionType());
    }
}
