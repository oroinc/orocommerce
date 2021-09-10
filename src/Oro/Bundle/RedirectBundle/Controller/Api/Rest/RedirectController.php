<?php

namespace Oro\Bundle\RedirectBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller to get a slug.
 */
class RedirectController extends AbstractFOSRestController
{
    /**
     * Get slug for string
     *
     * @ApiDoc(
     *      description="Get slug for string",
     *      resource=true
     * )
     *
     * @param string $string
     * @return Response
     */
    public function slugifyAction($string)
    {
        $slug = ['slug' => $this->get('oro_entity_config.slug.generator')->slugify($string)];
        return new Response(json_encode($slug), Response::HTTP_OK);
    }
}
