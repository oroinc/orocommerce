<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Form\Extension;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductCollectionData;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentChoiceType;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Form\Type\SearchTermType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormFactoryInterface;

class AddProductCollectionToWebsiteSearchTermFormExtensionTest extends WebTestCase
{
    use FormAwareTestTrait;

    private FormFactoryInterface $formFactory;

    private string $phraseDelimiter;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadProductCollectionData::class,
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
                    'productCollectionSegment' => 'data.actionType != "modify" || '
                        . 'data.modifyActionType != "product_collection"',
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
                    'oro.websitesearchterm.searchterm.modify_action_type.choices.product_collection.label' =>
                        'product_collection',
                ],
            ]
        );

        self::assertFormHasField(
            $form,
            'productCollectionSegment',
            SegmentChoiceType::class,
            [
                'required' => true,
                'entityClass' => Product::class,
                'entityChoices' => true,
            ]
        );
    }

    public function testFieldsAreDisabledWhenActionTypeIsRedirect(): void
    {
        /** @var Segment $segment */
        $segment = $this->getReference(LoadProductCollectionData::SEGMENT);
        $searchTerm = (new SearchTerm())
            ->setActionType('modify')
            ->setProductCollectionSegment($segment);

        $form = $this->formFactory->create(SearchTermType::class, $searchTerm, ['csrf_protection' => false]);

        $form->submit(
            [
                'phrases' => 'sample phrase',
                'actionType' => 'redirect',
                'modifyActionType' => 'uri',
                'redirectUri' => 'http://example.com',
            ]
        );

        self::assertFormOptions($form->get('productCollectionSegment'), ['disabled' => true]);

        self::assertNull($searchTerm->getRedirectContentNode());
    }

    public function testFieldsAreDisabledWhenActionTypeIsModifyWithProductCollection(): void
    {
        /** @var Segment $segment */
        $segment = $this->getReference(LoadProductCollectionData::SEGMENT);
        $searchTerm = (new SearchTerm())
            ->setActionType('redirect')
            ->setRedirectActionType('uri');

        $form = $this->formFactory->create(SearchTermType::class, $searchTerm, ['csrf_protection' => false]);

        $form->submit(
            [
                'phrases' => 'sample phrase',
                'actionType' => 'modify',
                'modifyActionType' => 'product_collection',
                'productCollectionSegment' => (string)$segment->getId(),
            ]
        );

        self::assertFormOptions($form->get('productCollectionSegment'), ['disabled' => false]);

        self::assertEquals('modify', $searchTerm->getActionType());
        self::assertEquals('product_collection', $searchTerm->getModifyActionType());
        self::assertEquals($segment, $searchTerm->getProductCollectionSegment());
        self::assertNull($searchTerm->getRedirectActionType());
    }
}
