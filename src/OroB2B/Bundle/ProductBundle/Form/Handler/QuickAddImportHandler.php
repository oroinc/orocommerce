<?php

namespace OroB2B\Bundle\ProductBundle\Form\Handler;

use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use OroB2B\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddOrderType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;

class QuickAddImportHandler
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var ComponentProcessorRegistry
     */
    protected $componentRegistry;

    /**
     * @param TranslatorInterface $translator
     * @param FormFactoryInterface $formFactory
     * @param ComponentProcessorRegistry $componentRegistry
     */
    public function __construct(
        TranslatorInterface $translator,
        FormFactoryInterface $formFactory,
        ComponentProcessorRegistry $componentRegistry
    ) {
        $this->translator = $translator;
        $this->formFactory = $formFactory;
        $this->componentRegistry = $componentRegistry;
    }

    /**
     * @param Request $request
     * @return array ['form' => FormInterface, 'response' => Response|null]
     */
    public function process(Request $request)
    {
        $response = null;
        $processor = $this->getProcessor($this->getComponentName($request));

        $products = $request->get(
            sprintf('%s[%s]', QuickAddOrderType::NAME, QuickAddType::PRODUCTS_FIELD_NAME),
            [],
            true
        );
        $products = is_array($products) ? $products : [];

        $shoppingListId = (int) $request->get(
            sprintf('%s[%s]', QuickAddOrderType::NAME, QuickAddType::ADDITIONAL_FIELD_NAME),
            0,
            true
        );

        if ($products) {
            if ($processor && $processor->isAllowed()) {
                    $response = $processor->process(
                        [
                            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => $products,
                            ProductDataStorage::ADDITIONAL_DATA_KEY => [
                                ProductDataStorage::SHOPPING_LIST_ID_KEY => $shoppingListId
                            ]
                        ],
                        $request
                    );
                    if (!$response) {
                        // reset form
                        $form = $this->createQuickAddOrderForm();
                    }
            } else {
                /** @var Session $session */
                $session = $request->getSession();
                $session->getFlashBag()->add(
                    'error',
                    $this->translator->trans('orob2b.product.frontend.component_not_accessible.message')
                );
            }
        }

        return ['form' => $form, 'response' => $response];
    }

    /**
     * @return FormInterface
     */
    protected function createQuickAddOrderForm()
    {
        return $this->formFactory->create(QuickAddOrderType::NAME);
    }

    /**
     * @param Request $request
     * @return null|string
     */
    protected function getComponentName(Request $request)
    {
        $formData = $request->get(QuickAddOrderType::NAME, []);

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
}
