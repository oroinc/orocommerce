<?php

namespace OroB2B\Bundle\ProductBundle\Form\Handler;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorInterface;
use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorRegistry;

class QuickAddImportFromFileHandler
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
        $form = $this->formFactory->create(QuickAddImportFromFileType::NAME);

        if ($request->isMethod(Request::METHOD_POST)) {
            $form->submit($request);
        }

        $form->getErrors();

        return ['form' => $form];
    }
}
