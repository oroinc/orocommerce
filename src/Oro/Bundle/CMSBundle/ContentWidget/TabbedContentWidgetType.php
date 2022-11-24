<?php

namespace Oro\Bundle\CMSBundle\ContentWidget;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\TabbedContentItem;
use Oro\Bundle\CMSBundle\Form\Type\TabbedContentItemCollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Valid;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Type for the tabbed content widgets.
 */
class TabbedContentWidgetType extends AbstractContentWidgetType
{
    public const CONTENT_WIDGET_NAME = 'oro_tabbed_content';

    private ManagerRegistry $managerRegistry;

    private int $instanceNumber = 0;

    private array $widgetData = [];

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public static function getName(): string
    {
        return self::CONTENT_WIDGET_NAME;
    }

    public function getLabel(): string
    {
        return 'oro.cms.content_widget_type.tabbed_content.label';
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function getBackOfficeViewSubBlocks(ContentWidget $contentWidget, Environment $twig): array
    {
        $data = $this->getWidgetData($contentWidget);

        return [
            [
                'title' => 'oro.cms.contentwidget.sections.tabbed_content_items.label',
                'subblocks' => [
                    [
                        'data' => [
                            $twig->render(
                                '@OroCMS/TabbedContentContentWidget/tabbed_content_items.html.twig',
                                $data
                            ),
                        ]
                    ],
                ]
            ],
        ];
    }

    public function getSettingsForm(ContentWidget $contentWidget, FormFactoryInterface $formFactory): ?FormInterface
    {
        return $formFactory
            ->create(FormType::class)
            ->add(
                'tabbedContentItems',
                TabbedContentItemCollectionType::class,
                [
                    'data' => $this->getItemsData($contentWidget),
                    'entry_options'  => ['content_widget' => $contentWidget],
                    'block' => 'tabbed_content_items',
                    'block_config' => [
                        'tabbed_content_items' => [
                            'title' => 'oro.cms.contentwidget.sections.tabbed_content_items.label'
                        ]
                    ],
                    'constraints' => [
                        new Valid(),
                    ],
                ]
            );
    }

    public function getWidgetData(ContentWidget $contentWidget): array
    {
        $data = $contentWidget->getSettings();
        $data['instanceNumber'] = $this->instanceNumber++;

        $key = spl_object_hash($contentWidget);

        if (!isset($this->widgetData[$key])) {
            $this->widgetData[$key] = [
                'tabbedContentItems' => $this->getItemsData($contentWidget)
            ];
        }

        return array_merge(['tabbedContentItems' => $this->widgetData[$key]['tabbedContentItems']], $data);
    }

    public function getDefaultTemplate(ContentWidget $contentWidget, Environment $twig): string
    {
        return '';
    }

    private function getItemsData(ContentWidget $contentWidget): Collection
    {
        $repository = $this->managerRegistry->getRepository(TabbedContentItem::class);

        return new ArrayCollection(
            $repository->findBy(['contentWidget' => $contentWidget], ['itemOrder' => 'ASC'])
        );
    }
}
