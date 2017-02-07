<?php

namespace Oro\Bundle\RedirectBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Symfony\Component\HttpFoundation\Response;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @RouteResource("slug")
 * @NamePrefix("oro_api_")
 */
class RedirectController extends FOSRestController
{
    /**
     * Get slug for string
     *
     * @Get("/redirect/slugify/{string}", requirements={
     *     "string": ".+"
     * }))
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
