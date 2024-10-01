<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Functional\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2TextTagType;
use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Form\Type\SearchTermType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;

class SearchTermTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    private FormFactoryInterface $formFactory;

    private string $phraseDelimiter;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);
        $this->phraseDelimiter = self::getContainer()->getParameter('oro_website_search_term.phrase_delimiter');
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testFormContainsFields(): void
    {
        $form = $this->formFactory->create(SearchTermType::class);

        self::assertFormOptions(
            $form,
            [
                'disable_fields_if' => [
                    'redirect301' => 'data.actionType != "redirect" || data.redirectActionType == "uri"',
                    'redirectUri' => 'data.actionType != "redirect" || data.redirectActionType != "uri"',
                    'redirectSystemPage' => 'data.actionType != "redirect" || '
                        . 'data.redirectActionType != "system_page"',
                ],
            ]
        );

        self::assertFormHasField(
            $form,
            'phrases',
            Select2TextTagType::class,
            [
                'required' => true,
                'configs' => [
                    'separator' => $this->phraseDelimiter,
                    'minimumInputLength' => 1,
                    'selectOnBlur' => true,
                ],
            ]
        );

        self::assertFormHasField(
            $form,
            'scopes',
            ScopeCollectionType::class,
            [
                'required' => false,
                'entry_options' => [
                    'scope_type' => 'website_search_term',
                ],
                'block_prefix' => 'oro_website_search_term_scopes',
            ]
        );

        self::assertFormHasField(
            $form,
            'actionType',
            ChoiceType::class,
            [
                'required' => true,
                'choices' => [
                    'oro.websitesearchterm.searchterm.action_type.choices.modify.label' => 'modify',
                    'oro.websitesearchterm.searchterm.action_type.choices.redirect.label' => 'redirect',
                ],
            ]
        );

        self::assertFormHasField(
            $form,
            'modifyActionType',
            ChoiceType::class,
            [
                'required' => true,
                'choices' => [
                    'oro.websitesearchterm.searchterm.modify_action_type.choices.original_results.label' =>
                        'original_results',
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
                    'oro.websitesearchterm.searchterm.redirect_action_type.choices.system_page.label' =>
                        'system_page',
                    'oro.websitesearchterm.searchterm.redirect_action_type.choices.uri.label' => 'uri',
                ],
            ]
        );

        self::assertFormHasField(
            $form,
            'redirectUri',
            TextType::class,
            [
                'required' => true,
            ]
        );

        self::assertFormHasField(
            $form,
            'redirectSystemPage',
            RouteChoiceType::class,
            [
                'required' => true,
                'placeholder' => 'oro.websitesearchterm.searchterm.redirect_system_page.placeholder',
                'options_filter' => [
                    'frontend' => true,
                ],
                'menu_name' => 'frontend_menu',
            ]
        );

        self::assertFormHasField(
            $form,
            'redirect301',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );

        self::assertFormHasField(
            $form,
            'partialMatch',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );
    }

    public function testFormViewContainsPhraseDelimiter(): void
    {
        $form = $this->formFactory->create(SearchTermType::class, null, ['csrf_protection' => false]);

        $formView = $form->createView();
        self::assertSame($this->phraseDelimiter, $formView['scopes']->vars['phraseDelimiter']);
    }

    public function testFieldsAreDisabledWhenActionTypeIsModify(): void
    {
        $searchTerm = (new SearchTerm())
            ->setActionType('redirect')
            ->setRedirectActionType('uri')
            ->setRedirectSystemPage('sample_route')
            ->setRedirectUri('http://example.com');

        $form = $this->formFactory->create(SearchTermType::class, $searchTerm, ['csrf_protection' => false]);

        $form->submit(
            [
                'phrases' => 'sample phrase',
                'actionType' => 'modify',
                'modifyActionType' => 'original_results',
            ]
        );

        self::assertFormOptions($form->get('redirectActionType'), ['disabled' => true]);
        self::assertFormOptions($form->get('redirectSystemPage'), ['disabled' => true]);
        self::assertFormOptions($form->get('redirectUri'), ['disabled' => true]);
        self::assertFormOptions($form->get('redirect301'), ['disabled' => true]);

        self::assertEquals('modify', $searchTerm->getActionType());
        self::assertEquals('original_results', $searchTerm->getModifyActionType());
        self::assertNull($searchTerm->getRedirectActionType());
        self::assertNull($searchTerm->getRedirectSystemPage());
        self::assertNull($searchTerm->getRedirectUri());
    }

    public function testFieldsAreDisabledWhenActionTypeIsRedirectSystemPage(): void
    {
        $searchTerm = (new SearchTerm())
            ->setActionType('modify')
            ->setModifyActionType('original_results');

        $form = $this->formFactory->create(SearchTermType::class, $searchTerm, ['csrf_protection' => false]);

        $redirectSystemPage = 'oro_frontend_root';
        $form->submit(
            [
                'phrases' => 'sample phrase',
                'actionType' => 'redirect',
                'redirectActionType' => 'system_page',
                'redirectSystemPage' => $redirectSystemPage,
            ]
        );

        self::assertFormOptions($form->get('modifyActionType'), ['disabled' => true]);
        self::assertFormOptions($form->get('redirectSystemPage'), ['disabled' => false]);
        self::assertFormOptions($form->get('redirectUri'), ['disabled' => true]);
        self::assertFormOptions($form->get('redirect301'), ['disabled' => false]);

        self::assertEquals('redirect', $searchTerm->getActionType());
        self::assertEquals('system_page', $searchTerm->getRedirectActionType());
        self::assertEquals($redirectSystemPage, $searchTerm->getRedirectSystemPage());
        self::assertNull($searchTerm->getModifyActionType());
        self::assertNull($searchTerm->getRedirectUri());
    }

    public function testFieldsAreDisabledWhenActionTypeIsRedirectUri(): void
    {
        $searchTerm = (new SearchTerm())
            ->setActionType('modify')
            ->setModifyActionType('original_results')
            ->setRedirect301(true);

        $form = $this->formFactory->create(SearchTermType::class, $searchTerm, ['csrf_protection' => false]);

        $redirectUri = 'http://example.com';
        $form->submit(
            [
                'phrases' => 'sample phrase',
                'actionType' => 'redirect',
                'redirectActionType' => 'uri',
                'redirectUri' => $redirectUri,
            ]
        );

        self::assertFormOptions($form->get('modifyActionType'), ['disabled' => true]);
        self::assertFormOptions($form->get('redirectSystemPage'), ['disabled' => true]);
        self::assertFormOptions($form->get('redirectUri'), ['disabled' => false]);
        self::assertFormOptions($form->get('redirect301'), ['disabled' => true]);

        self::assertEquals('redirect', $searchTerm->getActionType());
        self::assertEquals('uri', $searchTerm->getRedirectActionType());
        self::assertEquals($redirectUri, $searchTerm->getRedirectUri());
        self::assertFalse($searchTerm->isRedirect301());
        self::assertNull($searchTerm->getModifyActionType());
        self::assertNull($searchTerm->getRedirectSystemPage());
    }
}
