<?php

namespace Oro\Bundle\ProductBundle\Form\Handler;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Bundle\FrameworkBundle\Routing\Router as SymfonyRouter;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles the action of creating or editing a product. Allows to assign related or up-sell items to product.
 */
class ProductUpdateHandler extends UpdateHandler
{
    const ACTION_SAVE_AND_DUPLICATE = 'save_and_duplicate';

    /** @var ActionGroupRegistry */
    private $actionGroupRegistry;

    /** @var TranslatorInterface */
    private $translator;

    /** @var SymfonyRouter */
    private $symfonyRouter;

    /** @var RelatedItemsHandler */
    private $relatedItemsHandler;

    public function setActionGroupRegistry(ActionGroupRegistry $actionGroupRegistry)
    {
        $this->actionGroupRegistry = $actionGroupRegistry;
    }

    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function setRouter(SymfonyRouter $symfonyRouter)
    {
        $this->symfonyRouter = $symfonyRouter;
    }

    public function setRelatedItemsHandler(RelatedItemsHandler $relatedItemsHandler)
    {
        $this->relatedItemsHandler = $relatedItemsHandler;
    }

    /**
     * {@inheritDoc}
     */
    protected function saveForm(FormInterface $form, $data)
    {
        return parent::saveForm($form, $data) && $this->saveAllRelatedItems($form, $data);
    }

    /**
     * @param FormInterface  $form
     * @param Product        $entity
     * @param array|callable $saveAndStayRoute
     * @param array|callable $saveAndCloseRoute
     * @param string         $saveMessage
     * @param null           $resultCallback
     * @return array|RedirectResponse
     */
    protected function processSave(
        FormInterface $form,
        $entity,
        $saveAndStayRoute,
        $saveAndCloseRoute,
        $saveMessage,
        $resultCallback = null
    ) {
        $result = parent::processSave(
            $form,
            $entity,
            $saveAndStayRoute,
            $saveAndCloseRoute,
            $saveMessage,
            $resultCallback
        );

        if ($result instanceof RedirectResponse && $this->isSaveAndDuplicateAction()) {
            $saveMessage = $this->translator->trans('oro.product.controller.product.saved_and_duplicated.message');
            $this->session->getFlashBag()->set('success', $saveMessage);
            if ($actionGroup = $this->actionGroupRegistry->findByName('oro_product_duplicate')) {
                $actionData = $actionGroup->execute(new ActionData(['data' => $entity]));
                /** @var Product $productCopy */
                if ($productCopy = $actionData->offsetGet('productCopy')) {
                    return new RedirectResponse(
                        $this->symfonyRouter->generate('oro_product_view', ['id' => $productCopy->getId()])
                    );
                }
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    protected function isSaveAndDuplicateAction()
    {
        return $this->getCurrentRequest()->get(Router::ACTION_PARAMETER) === self::ACTION_SAVE_AND_DUPLICATE;
    }

    /**
     * @param FormInterface $form
     * @param Product $entity
     * @return bool
     */
    private function saveAllRelatedItems(FormInterface $form, Product $entity)
    {
        return $this->saveRelatedProducts($form, $entity)
            && $this->saveUpsellProducts($form, $entity);
    }

    /**
     * @param FormInterface $form
     * @param Product $entity
     * @return bool
     */
    private function saveRelatedProducts(FormInterface $form, Product $entity)
    {
        return $this->saveRelatedItems(
            $form,
            $entity,
            RelatedItemsHandler::RELATED_PRODUCTS,
            'appendRelated',
            'removeRelated'
        );
    }

    /**
     * @param FormInterface $form
     * @param Product $entity
     * @return bool
     */
    private function saveUpsellProducts(FormInterface $form, Product $entity)
    {
        return $this->saveRelatedItems(
            $form,
            $entity,
            RelatedItemsHandler::UPSELL_PRODUCTS,
            'appendUpsell',
            'removeUpsell'
        );
    }

    /**
     * @param FormInterface $form
     * @param Product $entity
     * @param string $assignerName
     * @param string $appendItemsFieldName
     * @param string $removeItemsFieldName
     * @return bool
     */
    private function saveRelatedItems(
        FormInterface $form,
        Product $entity,
        $assignerName,
        $appendItemsFieldName,
        $removeItemsFieldName
    ) {
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
