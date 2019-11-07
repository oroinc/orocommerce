<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Twig\Environment;

/**
 * Renders content widget.
 */
class ContentWidgetRenderer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ContentWidgetTypeRegistry */
    private $contentWidgetTypeRegistry;

    /** @var RegistryInterface */
    private $doctrine;

    /** @var Environment */
    private $twig;

    /** @var TokenAccessorInterface|null */
    private $tokenAccessor;

    /**
     * @param ContentWidgetTypeRegistry $contentWidgetTypeRegistry
     * @param RegistryInterface $doctrine
     * @param Environment $twig
     */
    public function __construct(
        ContentWidgetTypeRegistry $contentWidgetTypeRegistry,
        RegistryInterface $doctrine,
        Environment $twig
    ) {
        $this->contentWidgetTypeRegistry = $contentWidgetTypeRegistry;
        $this->doctrine = $doctrine;
        $this->twig = $twig;
        $this->logger = new NullLogger();
    }

    /**
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function setTokenAccessor(TokenAccessorInterface $tokenAccessor): void
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param string $widgetName
     * @param Organization|null $organization
     *
     * @return string
     */
    public function render(string $widgetName, Organization $organization = null): string
    {
        $organization = $organization ?? $this->getOrganization();
        if (!$organization) {
            $this->logger->error(
                sprintf('Could not render content widget %s: cannot detect organization', $widgetName)
            );

            return '';
        }

        /** @var ContentWidget $contentWidget */
        $contentWidget = $this->doctrine
            ->getManagerForClass(ContentWidget::class)
            ->getRepository(ContentWidget::class)
            ->findOneByName($widgetName, $organization);

        if (!$contentWidget) {
            $this->logger->error(
                sprintf('Could not render content widget %s: cannot find content widget', $widgetName)
            );

            return '';
        }

        return $this->renderWidget($contentWidget);
    }

    /**
     * @param ContentWidget $contentWidget
     *
     * @return string
     */
    private function renderWidget(ContentWidget $contentWidget): string
    {
        $template = $this->getTemplate($contentWidget);

        try {
            return $this->twig->render($template, $this->getData($contentWidget));
        } catch (\Exception $exception) {
            $this->logger->error(
                sprintf('Error occurred while rendering content widget %s', $contentWidget->getName()),
                ['exception' => $exception]
            );

            return '';
        }
    }

    /**
     * @param ContentWidget $contentWidget
     *
     * @return string
     */
    private function getTemplate(ContentWidget $contentWidget): string
    {
        // Make correct template resolving.
        if ($contentWidget->getWidgetType() === 'copyright') {
            return '@ACMECopyright/CopyrightContentWidget/widget.html.twig';
        }

        if ($contentWidget->getWidgetType() === 'contact_us_form') {
            return '@OroContactUsBridge/ContactUsFormContentWidget/widget.html.twig';
        }

        if ($contentWidget->getWidgetType() === 'image_slider') {
            return '@OroCMS/ImageSliderContentWidget/widget.html.twig';
        }

        return (string) $contentWidget->getTemplate();
    }

    /**
     * @param ContentWidget $contentWidget
     *
     * @return array
     */
    private function getData(ContentWidget $contentWidget): array
    {
        $contentWidgetType = $this->getType($contentWidget);

        return $contentWidgetType ? $contentWidgetType->getWidgetData($contentWidget) : [];
    }

    /**
     * @param ContentWidget $contentWidget
     *
     * @return ContentWidgetTypeInterface|null
     */
    private function getType(ContentWidget $contentWidget): ?ContentWidgetTypeInterface
    {
        $contentWidgetType = $this->contentWidgetTypeRegistry->getWidgetType($contentWidget->getWidgetType());
        if (!$contentWidgetType) {
            $this->logger->error(
                sprintf('Content widget type %s is not registered', $contentWidget->getWidgetType())
            );
        }

        return $contentWidgetType;
    }

    /**
     * @return Organization|null
     */
    private function getOrganization(): ?Organization
    {
        return $this->tokenAccessor ? $this->tokenAccessor->getOrganization() : null;
    }
}
