<?php

namespace MobileSearch\v2\RestBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ImageController.
 */
class ImageController extends Controller {

    /**
     * @Route("/image/{name}")
     * @Method({"GET"})
     * @ApiDoc(
     *   section="Images",
     *   views={"api"}
     * )
     */
    public function imageAction() {
        return new Response('true');
    }
}
