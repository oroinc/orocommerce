<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Validator\Constraint\NodeHasNoRestrictions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\When;

/**
 * Web-catalog and content node select form type that used to configure "Empty Search Result Page"
 */
class EmptySearchResultPageSelectSystemConfigType extends AbstractType
{
    public function __construct(private ConfigManager $configManager)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $emptySearchResultPageKey = TreeUtils::getConfigKey(
            Configuration::ROOT_NODE,
            Configuration::EMPTY_SEARCH_RESULT_PAGE
        );
        $emptySearchResultPage = $this->configManager->get($emptySearchResultPageKey) ?? [];
        $webCatalog = $emptySearchResultPage['webCatalog'] ?? null;

        $builder
            ->add(
                'webCatalog',
                WebCatalogSelectType::class,
                [
                    'label' => false,
                    'required' => false,
                    'create_enabled' => false,
                    'data' => $webCatalog,
                ]
            )
            ->add(
                'contentNode',
                ContentNodeFromWebCatalogSelectType::class,
                array_merge(
                    [
                        'label' => false,
                        'required' => true,
                        'error_bubbling' => false,
                        'constraints' => [
                            new NodeHasNoRestrictions(),
                            new When('this.getParent().get("webCatalog").getData()', new NotBlank())
                        ],
                    ],
                    $webCatalog instanceof WebCatalog ? ['web_catalog' => $webCatalog] : []
                )
            );
    }
}
