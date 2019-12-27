<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\Layout\LayoutContext;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Renders content widget.
 */
class ContentWidgetRenderer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var LayoutManager */
    private $layoutManager;

    /** @var ContentWidgetTypeRegistry */
    private $contentWidgetTypeRegistry;

    /** @var TokenAccessorInterface|null */
    private $tokenAccessor;

    /**
     * @param ManagerRegistry $doctrine
     * @param LayoutManager $layoutManager
     * @param ContentWidgetTypeRegistry $contentWidgetTypeRegistry
     */
    public function __construct(
        ManagerRegistry $doctrine,
        LayoutManager $layoutManager,
        ContentWidgetTypeRegistry $contentWidgetTypeRegistry
    ) {
        $this->doctrine = $doctrine;
        $this->layoutManager = $layoutManager;
        $this->contentWidgetTypeRegistry = $contentWidgetTypeRegistry;
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
        $type = $this->contentWidgetTypeRegistry->getWidgetType($contentWidget->getWidgetType());
        if (!$type) {
            return '';
        }

        $layoutContext = new LayoutContext(
            [
                'data' => $type->getWidgetData($contentWidget),
                'content_widget' => $contentWidget,
            ],
            ['content_widget']
        );

        try {
            $layoutBuilder = $this->layoutManager->getLayoutBuilder();
            $layoutBuilder->add('content_widget_root', null, 'content_widget_root');

            return $layoutBuilder->getLayout($layoutContext)
                ->render();
        } catch (\Exception $exception) {
            if ($this->logger) {
                $this->logger->error(
                    sprintf('Error occurred while rendering content widget %s', $contentWidget->getName()),
                    ['exception' => $exception]
                );
            }
        }

        return '';
    }

    /**
     * @return Organization|null
     */
    private function getOrganization(): ?Organization
    {
        return $this->tokenAccessor ? $this->tokenAccessor->getOrganization() : null;
    }
}
