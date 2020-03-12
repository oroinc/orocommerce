<?php

namespace Oro\Bundle\WebCatalogBundle\Actions;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\ActionException;
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
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * {@inheritDoc}
     * @param CanonicalUrlGenerator $canonicalUrlGenerator
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        CanonicalUrlGenerator $canonicalUrlGenerator,
        ManagerRegistry $managerRegistry
    ) {
        parent::__construct($contextAccessor);

        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
        $this->managerRegistry = $managerRegistry;
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
        if (!$contentNode) {
            throw new ActionException('Content node is empty');
        }

        $organization = $this->getWebCatalogOrganization($contentNode);
        $website = $this->getContentNodeWebsite($organization);
        $absoluteUrl = $this->getTargetUrl($contentNode, $website);

        $result = [
            'targetUrl' => $absoluteUrl,
            'website' => $website,
            'organization' => $organization
        ];
        $this->contextAccessor->setValue($context, $this->options[self::ATTRIBUTE], $result);
    }

    /**
     * @param ContentNode $contentNode
     * @param Website $website
     * @return string
     */
    private function getTargetUrl(ContentNode $contentNode, Website $website): string
    {
        $slug = $contentNode->getDefaultVariant()->getBaseSlug();
        if (!$slug) {
            $slug = $this->getContentNodeVariantSlug($contentNode);
        }

        $url = $slug ? $slug->getUrl() : '/';

        return $this->canonicalUrlGenerator->getAbsoluteUrl($url, $website);
    }

    /**
     * @param ContentNode $contentNode
     *
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
     * @param Organization $organization
     *
     * @return Website
     *
     * @throws ActionException
     */
    private function getContentNodeWebsite(Organization $organization): Website
    {
        $websites = $this->getWebsites($organization);
        if ($websites) {
            return reset($websites);
        }

        throw new ActionException('There must be at least one website.');
    }

    /**
     * Get websites by organization
     *
     * @param Organization $organization
     *
     * @return Website[]
     */
    private function getWebsites(Organization $organization): array
    {
        /** @var WebsiteRepository $websiteRepository */
        $websiteRepository = $this->managerRegistry->getManagerForClass(Website::class)->getRepository(Website::class);

        return $websiteRepository->getAllWebsites($organization);
    }

    /**
     * @param ContentNode $contentNode
     *
     * @return OrganizationInterface
     */
    private function getWebCatalogOrganization(ContentNode $contentNode): OrganizationInterface
    {
        return $contentNode->getWebCatalog()->getOrganization();
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
