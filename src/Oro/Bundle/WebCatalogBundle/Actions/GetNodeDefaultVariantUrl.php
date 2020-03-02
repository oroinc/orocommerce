<?php

namespace Oro\Bundle\WebCatalogBundle\Actions;

use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Get Content Node Default Variant full URL
 */
class GetNodeDefaultVariantUrl extends AbstractAction
{
    private const CONTENT_NODE = 'content_node';
    private const ATTRIBUTE = 'attribute';

    /**
     * @var array
     */
    private $options;

    /**
     * @var CanonicalUrlGenerator
     */
    private $canonicalUrlGenerator;

    /**
     * {@inheritDoc}
     * @param CanonicalUrlGenerator $canonicalUrlGenerator
     */
    public function __construct(ContextAccessor $contextAccessor, CanonicalUrlGenerator $canonicalUrlGenerator)
    {
        parent::__construct($contextAccessor);

        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        $this->options = $this->getOptionResolver()->resolve($options);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function executeAction($context)
    {
        /** @var ContentNode $contentNode */
        $contentNode = $this->contextAccessor->getValue($context, $this->options[self::CONTENT_NODE]);

        $slug = $contentNode->getDefaultVariant()->getBaseSlug();
        if (!$slug) {
            $slug = $this->getContentNodeVariantSlug($contentNode);
        }

        $url = $slug ? $slug->getUrl() : '/';
        $absoluteUrl = $this->canonicalUrlGenerator->getAbsoluteUrl($url);

        $this->contextAccessor->setValue($context, $this->options[self::ATTRIBUTE], $absoluteUrl);
    }

    /**
     * @param ContentNode $contentNode
     * @return Slug|null
     */
    private function getContentNodeVariantSlug(ContentNode $contentNode)
    {
        $contentVariants = $contentNode->getContentVariants();
        foreach ($contentVariants as $contentVariant) {
            if ($contentVariant->getBaseSlug()) {
                return $contentVariant->getBaseSlug();
            }
        }

        return null;
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionResolver(): OptionsResolver
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setRequired(self::CONTENT_NODE);
        $optionResolver->setRequired(self::ATTRIBUTE);
        $optionResolver->setAllowedTypes(self::CONTENT_NODE, ['object', PropertyPathInterface::class]);
        $optionResolver->setAllowedTypes(self::ATTRIBUTE, ['object', PropertyPathInterface::class]);

        return $optionResolver;
    }
}
