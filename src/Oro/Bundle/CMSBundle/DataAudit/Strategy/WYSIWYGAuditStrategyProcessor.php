<?php

namespace Oro\Bundle\CMSBundle\DataAudit\Strategy;

use DOMDocument;
use DOMElement;
use Exception;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;

/**
 * Entity like Product Description saved, HTML attributes will be re-arranged then send,
 * once check the differences are only cuz by arranging, this audit action will bypass.
 */
class WYSIWYGAuditStrategyProcessor implements EntityAuditStrategyProcessorInterface
{
    private EntityAuditStrategyProcessorInterface $innerProcessor;

    public function __construct(EntityAuditStrategyProcessorInterface $innerProcessor)
    {
        $this->innerProcessor = $innerProcessor;
    }

    public function processInverseCollections(array $sourceEntityData): array
    {
        if (isset($sourceEntityData['change_set']['wysiwyg'])) {
            [$beforeHtml, $afterHtml] = $sourceEntityData['change_set']['wysiwyg'];

            if ($this->isNotChangedActual($beforeHtml, $afterHtml)) {
                return [];
            }
        }

        return $this->innerProcessor->processInverseCollections($sourceEntityData);
    }

    private function isNotChangedActual(?string $beforeHtml, ?string $afterHtml): bool
    {
        if (mb_strlen((string)$beforeHtml) !== mb_strlen((string)$afterHtml)) {
            return false;
        }

        // clear twig brackets.
        $beforeHtml = preg_replace("/{{([^{]*)}}/", "\\1", (string)$beforeHtml);
        $afterHtml = preg_replace("/{{([^{]]*)}}/", "\\1", (string)$afterHtml);

        if ($beforeHtml && $afterHtml) {
            try {
                $beforeDom = new DOMDocument();
                $beforeDom->loadHTML($beforeHtml);
                /** @var DOMElement $node */
                $this->resetDOMElementsAttributes($beforeDom);

                $afterDom = new DOMDocument();
                $afterDom->loadHTML($afterHtml);
                $this->resetDOMElementsAttributes($afterDom);

                if ($beforeDom->saveHTML() === $afterDom->saveHTML()) {
                    return true;
                }
            } catch (Exception $exception) {
                return false;
            }
        }

        return false;
    }

    private function resetDOMElementsAttributes(DOMDocument $dom): void
    {
        foreach ($dom->getElementsByTagName('*') as $node) {
            if ($node->hasAttributes()) {
                $reorderedAttributes = [];

                foreach ($node->attributes as $attribute) {
                    $attributeName = $attribute->nodeName;
                    $attributeValue = $attribute->nodeValue;

                    if ($attributeName === 'id' && preg_match('/^isolation-scope-\w+$/', $attributeValue)) {
                        $attributeValue = "";
                    }

                    $reorderedAttributes[$attributeName] = $attributeValue;
                }

                ksort($reorderedAttributes);

                foreach ($reorderedAttributes as $attrName => $attrValue) {
                    $node->removeAttribute($attrName);
                    $node->setAttribute($attrName, $attrValue);
                }
            }
        }
    }

    public function processChangedEntities(array $sourceEntityData): array
    {
        return $this->innerProcessor->processChangedEntities($sourceEntityData);
    }

    public function processInverseRelations(array $sourceEntityData): array
    {
        return $this->innerProcessor->processInverseRelations($sourceEntityData);
    }
}
