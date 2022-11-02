<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityTreeSelectType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that allows to select Content Node from chosen WebCatalog
 */
class ContentNodeFromWebCatalogSelectType extends AbstractType
{
    /**
     * @var ContentNodeTreeHandler
     */
    private $treeHandler;

    public function __construct(ContentNodeTreeHandler $treeHandler)
    {
        $this->treeHandler = $treeHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['web_catalog']);
        $resolver->setAllowedTypes('web_catalog', [WebCatalog::class]);
        $resolver->setDefaults([
            'class' => ContentNode::class,
            'tree_key' => 'web-catalog-content-node',
            'tree_data' => [],
            'auto_initialize' => false,
            'page_component_module' => 'orowebcatalog/js/app/views/content-node-from-webcatalog-form-view',
            'page_component_options' => [
                'updateApiAccessor' => [
                    'http_method' => 'GET',
                    'route' => 'oro_rest_api_item',
                    'routeQueryParameterNames' => ['entity', 'id']
                ]
            ],
            'error_bubbling' => false,
        ]);

        $resolver->setNormalizer(
            'tree_data',
            function (Options $options) {
                $webCatalog = $options['web_catalog'] ?? null;

                if (null === $webCatalog) {
                    return [];
                }

                return function () use ($webCatalog) {
                    $treeRoot = $this->treeHandler->getTreeRootByWebCatalog($webCatalog);

                    return $this->treeHandler->createTree($treeRoot, true);
                };
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_web_catalog_content_node_from_web_catalog_select';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityTreeSelectType::class;
    }
}
