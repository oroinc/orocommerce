<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\EventListener\AttributeFormViewListener as BaseAttributeFormViewListener;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * This class allows to restrict moving of attributes
 */
class AttributeFormViewListener extends BaseAttributeFormViewListener
{
    private const DEFAULT_PRIORITY = 500;
    private const EVENT_TYPE_VIEW = 'view';
    private $fieldsRestrictedToMove = [
        'inventory_status',
        'images',
        'productPriceAttributesPrices',
        'shortDescriptions',
        'descriptions',
    ];

    /**
     * This property used to determine type of event inside moveFieldToBlock.
     * It's safe because it wll be cleared after event processing
     *
     * @var string
     */
    private $eventType;

    public function __construct(
        private AttributeManager $attributeManager,
        private FieldAclHelper $fieldAclHelper,
        private ConfigProvider $entityConfigProvider,
        private TranslatorInterface $translator,
    ) {
        parent::__construct($attributeManager, $fieldAclHelper);
    }

    #[\Override]
    public function onViewList(BeforeListRenderEvent $event)
    {
        $this->eventType = self::EVENT_TYPE_VIEW;

        parent::onViewList($event);

        $this->eventType = null;
    }

    #[\Override]
    protected function moveFieldToBlock(ScrollData $scrollData, $fieldName, $blockId)
    {
        if ($this->eventType === self::EVENT_TYPE_VIEW) {
            if (in_array($fieldName, $this->getRestrictedToMoveFields(), true)) {
                return;
            }
        }

        parent::moveFieldToBlock($scrollData, $fieldName, $blockId);
    }

    /**
     * @return array
     */
    protected function getRestrictedToMoveFields()
    {
        return $this->fieldsRestrictedToMove;
    }

    #[\Override]
    protected function addNotEmptyGroupBlocks(ScrollData $scrollData, array $groups)
    {
        parent::addNotEmptyGroupBlocks($scrollData, $groups);

        foreach ($groups as $group) {
            if (empty($group['attributes'])) {
                continue;
            }

            /** @var AttributeGroup $currentGroup */
            $currentGroup = $group['group'];

            $block = $scrollData->getBlock($currentGroup->getCode());

            $priority = $block[ScrollData::PRIORITY] ?? self::DEFAULT_PRIORITY;

            /** @var FieldConfigModel $attribute */
            foreach ($group['attributes'] as $attribute) {
                if (!$this->isSeparateGroup($attribute->getType())) {
                    continue;
                }

                $config = $this->entityConfigProvider->getConfig(Product::class, $attribute->getFieldName());

                $scrollData->addNamedBlock(
                    $attribute->getFieldName(),
                    $this->translator->trans((string) $config->get('label')),
                    ++$priority
                );
            }
        }
    }

    #[\Override]
    protected function addAttributeEditBlocks(BeforeListRenderEvent $event, AttributeGroup $group, array $attributes)
    {
        parent::addAttributeEditBlocks($event, $group, $attributes);

        foreach ($attributes as $attribute) {
            if (!$this->isSeparateGroup($attribute->getType())) {
                continue;
            }

            $this->moveFieldToBlock($event->getScrollData(), $attribute->getFieldName(), $attribute->getFieldName());
        }
    }

    #[\Override]
    protected function renderAttributeEditData(Environment $twig, FormView $attributeView, FieldConfigModel $attribute)
    {
        return $this->isSeparateGroup($attribute->getType())
            ? $twig->render('@OroEntityConfig/Attribute/widget.html.twig', ['child' => $attributeView])
            : parent::renderAttributeEditData($twig, $attributeView, $attribute);
    }

    #[\Override]
    protected function addAttributeViewBlocks(BeforeListRenderEvent $event, AttributeGroup $group, array $attributes)
    {
        parent::addAttributeViewBlocks($event, $group, $attributes);

        foreach ($attributes as $attribute) {
            if (!$this->isSeparateGroup($attribute->getType())) {
                continue;
            }

            $this->moveFieldToBlock($event->getScrollData(), $attribute->getFieldName(), $attribute->getFieldName());
        }
    }

    /**
     * @param Environment $twig
     * @param object $entity
     * @param FieldConfigModel $attribute
     * @return string
     */
    #[\Override]
    protected function renderAttributeViewData(Environment $twig, $entity, FieldConfigModel $attribute)
    {
        if ($this->isSeparateGroup($attribute->getType())) {
            return $twig->render(
                '@OroEntityConfig/Attribute/attributeCollapsibleView.html.twig',
                ['entity' => $entity, 'field' => $attribute]
            );
        }

        return parent::renderAttributeViewData($twig, $entity, $attribute);
    }

    protected function isSeparateGroup(?string $type): bool
    {
        return in_array((string)$type, [WYSIWYGType::TYPE], true);
    }
}
