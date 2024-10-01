<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityTreeSelectType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentNodeSelectType extends AbstractType
{
    const NAME = 'oro_web_catalog_content_node_select';

    /**
     * @var ContentNodeTreeHandler
     */
    private $treeHandler;

    public function __construct(ContentNodeTreeHandler $treeHandler)
    {
        $this->treeHandler = $treeHandler;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['web_catalog']);
        $resolver->setAllowedTypes('web_catalog', [WebCatalog::class]);
        $resolver->setDefaults([
            'class' => ContentNode::class,
            'tree_key' => 'commerce-category',
            'tree_data' => []
        ]);

        $resolver->setNormalizer(
            'tree_data',
            function (Options $options) {
                $webCatalog = $options['web_catalog'];

                return function () use ($webCatalog) {
                    $treeRoot = $this->treeHandler->getTreeRootByWebCatalog($webCatalog);

                    return $this->treeHandler->createTree($treeRoot, true);
                };
            }
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return EntityTreeSelectType::class;
    }
}
