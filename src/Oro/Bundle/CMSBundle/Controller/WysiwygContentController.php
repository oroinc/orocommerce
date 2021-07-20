<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Bundle\CMSBundle\Tools\WYSIWYGContentChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller class with action to work with wysiwyg content.
 */
class WysiwygContentController extends AbstractController
{
    /**
     * @Route("/validate", name="oro_cms_wysiwyg_content_validate", methods={"POST"})
     */
    public function validateAction(Request $request): Response
    {
        $className = $request->get('className');
        if (!$className) {
            throw new BadRequestHttpException('ClassName field is required.');
        }

        $fieldName = $request->get('fieldName');
        if (!$fieldName) {
            throw new BadRequestHttpException('FieldName field is required.');
        }

        $errors = $this->get(WYSIWYGContentChecker::class)
            ->check((string)$request->get('content'), $className, $fieldName);

        return new JsonResponse(['success' => !$errors, 'errors' => $errors]);
    }

    /**
     * @Route("/resolve", name="oro_cms_wysiwyg_content_resolve", methods={"POST"})
     */
    public function resolveAction(Request $request): Response
    {
        $success = true;
        $content = (string) $request->get('content');
        $code = Response::HTTP_OK;

        try {
            $content = $this->get(DigitalAssetTwigTagsConverter::class)
                ->convertToUrls($content);
        } catch (\Exception $e) {
            $success = false;
            $code = Response::HTTP_BAD_REQUEST;
        }

        return new JsonResponse(['success' => $success, 'content' => $content], $code);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            WYSIWYGContentChecker::class,
            DigitalAssetTwigTagsConverter::class,
        ];
    }
}
