<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeFromWebCatalogSelectType;
use Oro\Bundle\WebCatalogBundle\Form\Type\EmptySearchResultPageSelectSystemConfigType;
use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogSelectType;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\NodeHasNoRestrictions;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\When;

class EmptySearchResultPageSelectSystemConfigTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    private FormFactoryInterface $formFactory;

    private ConfigManager $configManager;

    protected function setUp(): void
    {
        $this->initClient();

        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);
        $this->configManager = self::getContainer()->get('oro_config.manager');
    }

    /**
     * @dataProvider getFormContainsFieldsDataProvider
     */
    public function testFormContainsFields(?WebCatalog $webCatalog): void
    {
        $emptySearchResultPageKey = TreeUtils::getConfigKey(
            Configuration::ROOT_NODE,
            Configuration::EMPTY_SEARCH_RESULT_PAGE
        );
        $this->configManager->set($emptySearchResultPageKey, ['webCatalog' => $webCatalog]);

        $form = $this->formFactory->create(EmptySearchResultPageSelectSystemConfigType::class);

        self::assertFormHasField(
            $form,
            'webCatalog',
            WebCatalogSelectType::class,
            [
                'label' => false,
                'create_enabled' => false,
                'data' => $webCatalog,
            ]
        );

        self::assertFormHasField(
            $form,
            'contentNode',
            ContentNodeFromWebCatalogSelectType::class,
            array_merge(
                [
                    'label' => false,
                    'required' => true,
                    'error_bubbling' => false,
                    'constraints' => [
                        new NodeHasNoRestrictions(),
                        new When('this.getParent().get("webCatalog").getData()', new NotBlank()),
                    ],
                ],
                $webCatalog instanceof WebCatalog ? ['web_catalog' => $webCatalog] : []
            )
        );
    }

    public function getFormContainsFieldsDataProvider(): array
    {
        return [
            [
                'webCatalog' => null,
            ],
            [
                'webCatalog' => new WebCatalog(),
            ],
        ];
    }
}
