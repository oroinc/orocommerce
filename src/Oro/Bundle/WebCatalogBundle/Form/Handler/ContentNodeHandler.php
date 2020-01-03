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
    /**
     * {@inheritDoc}
     */
    public function process($data, FormInterface $form, Request $request)
    {
        $form->setData($data);

        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($form, $request, false);

            if ($form->isValid()) {
                $manager = $this->doctrineHelper->getEntityManager($data);
                $manager->beginTransaction();
                try {
                    $this->saveData($data, $form);
                    $manager->commit();
                } catch (\Exception $exception) {
                    $manager->rollback();
                    throw $exception;
                }

                return true;
            }
        }

        return false;
    }
}
