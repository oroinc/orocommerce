<?php

namespace Oro\Bundle\ProductBundle\Form\Handler;

use Symfony\Bundle\FrameworkBundle\Routing\Router as SymfonyRouter;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\UIBundle\Route\Router;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductUpdateHandler extends UpdateHandler
{
    const ACTION_SAVE_AND_DUPLICATE = 'save_and_duplicate';

    /** @var ActionGroupRegistry */
    private $actionGroupRegistry;

    /** @var TranslatorInterface */
    private $translator;

    /** @var SymfonyRouter */
    private $symfonyRouter;

    /**
     * @param ActionGroupRegistry $actionGroupRegistry
     */
    public function setActionGroupRegistry(ActionGroupRegistry $actionGroupRegistry)
    {
        $this->actionGroupRegistry = $actionGroupRegistry;
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param SymfonyRouter $symfonyRouter
     */
    public function setRouter(SymfonyRouter $symfonyRouter)
    {
        $this->symfonyRouter = $symfonyRouter;
    }

    /**
     * @param FormInterface $form
     * @param Product $entity
     * @param array|callable $saveAndStayRoute
     * @param array|callable $saveAndCloseRoute
     * @param string $saveMessage
     * @param null $resultCallback
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
        return $this->request->get(Router::ACTION_PARAMETER) === self::ACTION_SAVE_AND_DUPLICATE;
    }
}
