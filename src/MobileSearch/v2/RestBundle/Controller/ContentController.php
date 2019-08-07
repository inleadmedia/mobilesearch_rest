<?php

namespace MobileSearch\v2\RestBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController.
 */
class ContentController extends Controller
{
    /**
     * @Route("/content", name="content_collection")
     * @Method({"GET"})
     * @ApiDoc(
     *     section = "Content",
     *     views = { "api" }
     * )
     */
    public function contentCollectionAction()
    {
        return new Response('true');
    }

    /**
     * @Route("/content/{id}", name="content_get")
     * @Method({"GET"})
     * @ApiDoc(
     *     section = "Content",
     *     views = { "api" }
     * )
     */
    public function contentAction()
    {
        return new Response('true');
    }

    /**
     * @Route("/content/{id}", name="content_update")
     * @Method({"PUT"})
     * @ApiDoc(
     *     section = "Content",
     *     views = { "api" }
     * )
     */
    public function contentUpdateAction()
    {
        return new Response('true');
    }

    /**
     * @Route("/content", name="content_create")
     * @Method({"POST"})
     * @ApiDoc(
     *     section = "Content",
     *     views = { "api" }
     * )
     */
    public function contentCreateAction()
    {
        return new Response('true');
    }

    /**
     * @Route("/content/{id}", name="content_delete")
     * @Method({"DELETE"})
     * @ApiDoc(
     *     section = "Content",
     *     views = { "api" }
     * )
     */
    public function contentDeleteAction()
    {
        return new Response('true');
    }

    /**
     * @Route("/content/{id}", name="content_patch")
     * @Method({"PATCH"})
     * @ApiDoc(
     *     section = "Content",
     *     views = { "api" }
     * )
     */
    public function contentPatchAction()
    {
        return new Response('true');
    }
}
