<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Form\Extension;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Form\Type\ContentBlockSelectType;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadContentBlockData;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Form\Type\SearchTermType;
use Symfony\Component\Form\FormFactoryInterface;

class AddContentBlockToWebsiteSearchTermFormExtensionTest extends WebTestCase
{
    use FormAwareTestTrait;

    private FormFactoryInterface $formFactory;

    private string $phraseDelimiter;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadContentBlockData::class,
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
                    'contentBlock' => 'data.actionType != "modify"',
                ],
            ]
        );

        self::assertFormHasField(
            $form,
            'contentBlock',
            ContentBlockSelectType::class,
            [
                'required' => false,
            ]
        );
    }

    public function testFieldsAreDisabledWhenActionTypeIsRedirect(): void
    {
        $searchTerm = (new SearchTerm())
            ->setActionType('modify')
            ->setModifyActionType('original_results');

        $form = $this->formFactory->create(SearchTermType::class, $searchTerm, ['csrf_protection' => false]);

        $form->submit(
            [
                'phrases' => 'sample phrase',
                'actionType' => 'redirect',
                'redirectActionType' => 'uri',
                'redirectUri' => 'https://example.com',
            ]
        );

        self::assertFormOptions($form->get('contentBlock'), ['disabled' => true]);

        self::assertNull($searchTerm->getContentBlock());
    }

    public function testFieldsAreDisabledWhenActionTypeIsModify(): void
    {
        /** @var ContentBlock $contentBlock */
        $contentBlock = $this->getReference('content_block_1');
        $searchTerm = (new SearchTerm())
            ->setActionType('redirect')
            ->setRedirectActionType('uri')
            ->setRedirectUri('https://example.com');

        $form = $this->formFactory->create(SearchTermType::class, $searchTerm, ['csrf_protection' => false]);

        $form->submit(
            [
                'phrases' => 'sample phrase',
                'actionType' => 'modify',
                'modifyActionType' => 'original_results',
                'contentBlock' => $contentBlock->getId(),
            ]
        );

        self::assertFormOptions($form->get('contentBlock'), ['disabled' => false]);

        self::assertEquals('modify', $searchTerm->getActionType());
        self::assertEquals('original_results', $searchTerm->getModifyActionType());
        self::assertEquals($contentBlock, $searchTerm->getContentBlock());
        self::assertNull($searchTerm->getRedirectActionType());
    }
}
