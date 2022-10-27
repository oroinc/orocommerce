<?php

namespace Oro\Bundle\ProductBundle\Form\Handler;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\FormBundle\Model\UpdateInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles the action of creating or editing a product. Allows to assign related or up-sell items to product.
 */
class ProductUpdateHandler extends UpdateHandlerFacade
{
    const ACTION_SAVE_AND_DUPLICATE = 'save_and_duplicate';

    private ActionGroupRegistry $actionGroupRegistry;
    private TranslatorInterface $translator;
    private UrlGeneratorInterface $urlGenerator;
    private RelatedItemsHandler $relatedItemsHandler;

    public function setActionGroupRegistry(ActionGroupRegistry $actionGroupRegistry): void
    {
        $this->actionGroupRegistry = $actionGroupRegistry;
    }

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator): void
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function setRelatedItemsHandler(RelatedItemsHandler $relatedItemsHandler): void
    {
        $this->relatedItemsHandler = $relatedItemsHandler;
    }

    /**
     * {@inheritDoc}
     */
    protected function constructResponse(
        UpdateInterface $update,
        Request $request,
        ?string $saveMessage
    ): array|RedirectResponse {
        $entity = $update->getFormData();
        if (false === $this->saveAllRelatedItems($update->getForm(), $entity)) {
            return $this->getResult($update, $request);
        }

        if ($isSaveAndDuplicateAction = $this->isSaveAndDuplicateAction($request)) {
            $saveMessage = $this->translator->trans('oro.product.controller.product.saved_and_duplicated.message');
        }

        $result = parent::constructResponse($update, $request, $saveMessage);

        if ($result instanceof RedirectResponse && $isSaveAndDuplicateAction) {
            if ($actionGroup = $this->actionGroupRegistry->findByName('oro_product_duplicate')) {
                $actionData = $actionGroup->execute(new ActionData(['data' => $entity]));
                /** @var Product $productCopy */
                if ($productCopy = $actionData->offsetGet('productCopy')) {
                    return new RedirectResponse(
                        $this->urlGenerator->generate('oro_product_view', ['id' => $productCopy->getId()])
                    );
                }
            }
        }

        return $result;
    }

    protected function isSaveAndDuplicateAction(Request $request): bool
    {
        return $request->get(Router::ACTION_PARAMETER) === self::ACTION_SAVE_AND_DUPLICATE;
    }

    private function saveAllRelatedItems(FormInterface $form, Product $entity): bool
    {
        return $this->saveRelatedProducts($form, $entity)
            && $this->saveUpsellProducts($form, $entity);
    }

    private function saveRelatedProducts(FormInterface $form, Product $entity): bool
    {
        return $this->saveRelatedItems(
            $form,
            $entity,
            RelatedItemsHandler::RELATED_PRODUCTS,
            'appendRelated',
            'removeRelated'
        );
    }

    private function saveUpsellProducts(FormInterface $form, Product $entity): bool
    {
        return $this->saveRelatedItems(
            $form,
            $entity,
            RelatedItemsHandler::UPSELL_PRODUCTS,
            'appendUpsell',
            'removeUpsell'
        );
    }

    private function saveRelatedItems(
        FormInterface $form,
        Product $entity,
        string $assignerName,
        string $appendItemsFieldName,
        string $removeItemsFieldName
    ): bool {
        if (!$form->has($appendItemsFieldName) && !$form->has($removeItemsFieldName)) {
            return true;
        }

        return $this->relatedItemsHandler->process(
            $assignerName,
            $entity,
            $form->get($appendItemsFieldName),
            $form->get($removeItemsFieldName)
        );
    }
}
