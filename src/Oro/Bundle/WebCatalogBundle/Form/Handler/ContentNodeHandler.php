<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Handler;

use Oro\Bundle\FormBundle\Form\Handler\FormHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handler for Content Node form, uses $clearMissing=false to avoid losing the content variant
 */
class ContentNodeHandler extends FormHandler
{
    protected function submitPostPutRequest(FormInterface $form, Request $request, bool $clearMissing = true)
    {
        parent::submitPostPutRequest($form, $request, false);
    }
}
