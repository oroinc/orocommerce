<?php

namespace OroB2B\Bundle\ProductBundle\Form\Handler;

use OroB2B\Bundle\ProductBundle\Entity\ProductVariantLink;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\UIBundle\Route\Router;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductUpdateHandler extends UpdateHandler
{
    const ACTION_SAVE_AND_DUPLICATE = 'save_and_duplicate';

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
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

        $this->processProductVariants($form, $entity);

        if ($result instanceof RedirectResponse && $this->isSaveAndDuplicateAction()) {
            $saveMessage = $this->translator->trans('orob2b.product.controller.product.saved_and_duplicated.message');
            $this->session->getFlashBag()->set('success', $saveMessage);

            return new RedirectResponse($this->urlGenerator->generate(
                'orob2b_product_duplicate',
                ['id' => $entity->getId()]
            ));
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

    protected function processProductVariants(FormInterface $form, Product $product)
    {
        $appendVariants = $form->get('appendVariants')->getData();
        $removeVariants = $form->get('removeVariants')->getData();

        foreach ($appendVariants as $appendVariant) {
            $product->addVariantLink(
                new ProductVariantLink($product, $appendVariant)
            );
        }

        foreach ($product->getVariantLinks() as $variantLink) {
            if (in_array($variantLink->getVariant(), $removeVariants)) {
                $product->getVariantLinks()->removeElement($variantLink);
            }
        }

        $this->doctrineHelper->getEntityManager($product)->flush($product);

    }
}
