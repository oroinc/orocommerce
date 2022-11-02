<?php

namespace Oro\Bundle\ProductBundle\Form\Handler;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Exception\AssignerNotFoundException;
use Oro\Bundle\ProductBundle\RelatedItem\AssignerStrategyInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Allows to assign or remove related items to Product entity from UI.
 */
class RelatedItemsHandler
{
    const RELATED_PRODUCTS = 'relatedProducts';
    const UPSELL_PRODUCTS = 'upsellProducts';

    /** @var AssignerStrategyInterface[] */
    private $assigners;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param string $assignerName
     * @param AssignerStrategyInterface $assigner
     */
    public function addAssigner($assignerName, AssignerStrategyInterface $assigner)
    {
        $this->assigners[$assignerName] = $assigner;
    }

    /**
     * @param string $assignerName
     * @param Product $product
     * @param FormInterface $appendField
     * @param FormInterface $removeField
     * @throws AssignerNotFoundException
     *
     * @return bool
     */
    public function process($assignerName, Product $product, FormInterface $appendField, FormInterface $removeField)
    {
        $hasErrors = true;

        $appendRelated = (array) $appendField->getData();
        $removeRelated = (array) $removeField->getData();

        $assigner = $this->getAssigner($assignerName);
        $assigner->removeRelations($product, $removeRelated);

        try {
            $assigner->addRelations($product, $appendRelated);
            $hasErrors = false;
        } catch (\InvalidArgumentException|\LogicException|\OverflowException $e) {
            $this->addFormError($appendField, $e->getMessage());
        }

        return !$hasErrors;
    }

    /**
     * @param FormInterface $form
     * @param string|null $message
     */
    private function addFormError(FormInterface $form, ?string $message)
    {
        $form->addError(
            new FormError(
                $this->translator->trans((string) $message, [], 'validators')
            )
        );
    }

    /**
     * @param string $name
     * @throws AssignerNotFoundException
     *
     * @return AssignerStrategyInterface
     */
    private function getAssigner($name)
    {
        if (!isset($this->assigners[$name])) {
            throw new AssignerNotFoundException(
                sprintf(
                    'Unable to find %s assigner. 
                    Maybe you forgot to register it with RelatedItemsHandler::addAssigner() method?',
                    $name
                )
            );
        }

        return $this->assigners[$name];
    }
}
