<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Form\Extension;

use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeFromWebCatalogSelectType;
use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogSelectType;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogWithContentNodes;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Form\Type\SearchTermType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormFactoryInterface;

class AddContentNodeToWebsiteSearchTermFormExtensionTest extends WebTestCase
{
    use FormAwareTestTrait;

    private FormFactoryInterface $formFactory;

    private string $phraseDelimiter;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadWebCatalogWithContentNodes::class,
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
                    'redirectContentNode' => 'data.actionType != "redirect" || '
                        . 'data.redirectActionType != "content_node"',
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
                    'oro.websitesearchterm.searchterm.redirect_action_type.choices.content_node.label' =>
                        'content_node',
                ],
            ]
        );

        self::assertFormHasField(
            $form,
            'redirectWebCatalog',
            WebCatalogSelectType::class,
            [
                'required' => true,
                'mapped' => false,
                'error_bubbling' => false,
                'create_enabled' => false,
            ]
        );

        self::assertFormHasField(
            $form,
            'redirectContentNode',
            ContentNodeFromWebCatalogSelectType::class,
            [
                'required' => true,
                'error_bubbling' => false,
            ]
        );
    }

    public function testFieldsAreDisabledWhenActionTypeIsModify(): void
    {
        /** @var ContentNode $contentNode */
        $contentNode = $this->getReference(LoadWebCatalogWithContentNodes::CONTENT_NODE_1);
        $searchTerm = (new SearchTerm())
            ->setActionType('redirect')
            ->setRedirectContentNode($contentNode);

        $form = $this->formFactory->create(SearchTermType::class, $searchTerm, ['csrf_protection' => false]);

        $form->submit(
            [
                'phrases' => 'sample phrase',
                'actionType' => 'modify',
                'modifyActionType' => 'original_results',
            ]
        );

        self::assertFormOptions($form->get('redirectContentNode'), ['disabled' => true]);

        self::assertNull($searchTerm->getRedirectContentNode());
    }

    public function testFieldsAreDisabledWhenActionTypeIsRedirectContentNode(): void
    {
        /** @var ContentNode $contentNode */
        $contentNode = $this->getReference(LoadWebCatalogWithContentNodes::CONTENT_NODE_1);
        $searchTerm = (new SearchTerm())
            ->setActionType('modify')
            ->setModifyActionType('original_results');

        $form = $this->formFactory->create(SearchTermType::class, $searchTerm, ['csrf_protection' => false]);

        $form->submit(
            [
                'phrases' => 'sample phrase',
                'actionType' => 'redirect',
                'redirectActionType' => 'content_node',
                'redirectContentNode' => $contentNode->getId(),
            ]
        );

        self::assertFormOptions($form->get('redirectContentNode'), ['disabled' => false]);

        self::assertEquals('redirect', $searchTerm->getActionType());
        self::assertEquals('content_node', $searchTerm->getRedirectActionType());
        self::assertEquals($contentNode, $searchTerm->getRedirectContentNode());
        self::assertNull($searchTerm->getModifyActionType());
    }
}
