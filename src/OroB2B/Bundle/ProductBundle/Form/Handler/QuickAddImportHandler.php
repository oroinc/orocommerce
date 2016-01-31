<?php

namespace OroB2B\Bundle\ProductBundle\Form\Handler;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddImportHandler
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @var ComponentProcessorRegistry
     */
    protected $componentRegistry;

    /**
     * @param TranslatorInterface $translator
     * @param UrlGeneratorInterface $urlGenerator
     * @param ComponentProcessorRegistry $componentRegistry
     */
    public function __construct(
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        ComponentProcessorRegistry $componentRegistry
    ) {
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->componentRegistry = $componentRegistry;
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function process(Request $request)
    {
        if (!$products = $this->getProducts($request)) {
            $this->setFlashError($request, 'orob2b.product.frontend.messages.invalid_request');

            return $this->redirectToQuickAddPage();
        }

        $processor = $this->getProcessor($this->getComponentName($request));

        if ($processor && $processor->isAllowed()) {
            $response = $processor->process(
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => $products,
                    ProductDataStorage::ADDITIONAL_DATA_KEY => [
                        ProductDataStorage::SHOPPING_LIST_ID_KEY => $this->getShoppingListId($request)
                    ]
                ],
                $request
            );
        } else {
            $this->setFlashError($request, 'orob2b.product.frontend.messages.component_not_accessible');
            $response = null;
        }

        return $response instanceof RedirectResponse ? $response : $this->redirectToQuickAddPage();
    }

    /**
     * @param Request $request
     * @return null|string
     */
    protected function getComponentName(Request $request)
    {
        $formData = $request->get(QuickAddType::NAME, []);

        $name = null;
        if (array_key_exists(QuickAddType::COMPONENT_FIELD_NAME, $formData)) {
            $name = $formData[QuickAddType::COMPONENT_FIELD_NAME];
        }

        return $name;
    }

    /**
     * @param string $name
     * @return null|ComponentProcessorInterface
     */
    protected function getProcessor($name)
    {
        return $this->componentRegistry->getProcessorByName($name);
    }

    /**
     * @param Request $request
     * @return array
     */
    private function getProducts(Request $request)
    {
        $products = $request->get(
            sprintf('%s[%s]', QuickAddType::NAME, QuickAddType::PRODUCTS_FIELD_NAME),
            [],
            true
        );

        return is_array($products) ? $products : [];
    }

    /**
     * @param Request $request
     * @return int
     */
    private function getShoppingListId(Request $request)
    {
        return (int) $request->get(
            sprintf('%s[%s]', QuickAddType::NAME, QuickAddType::ADDITIONAL_FIELD_NAME),
            0,
            true
        );
    }

    /**
     * @param Request $request
     * @param string $message
     */
    private function setFlashError(Request $request, $message)
    {
        /** @var Session $session */
        $session = $request->getSession();
        $session->getFlashBag()->add('error', $this->translator->trans($message));
    }

    /**
     * @return RedirectResponse
     */
    private function redirectToQuickAddPage()
    {
        return new RedirectResponse($this->urlGenerator->generate('orob2b_product_frontend_quick_add'));
    }
}
