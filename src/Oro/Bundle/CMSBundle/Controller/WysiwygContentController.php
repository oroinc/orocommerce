<?php

namespace Oro\Bundle\CMSBundle\Controller;

use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller class with action to resolve wysiwyg content.
 */
class WysiwygContentController extends AbstractController
{
    /**
     * @Route("/", name="oro_cms_wysiwyg_content_resolve", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
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
            DigitalAssetTwigTagsConverter::class,
        ];
    }
}
