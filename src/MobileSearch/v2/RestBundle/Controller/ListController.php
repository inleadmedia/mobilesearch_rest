<?php

namespace MobileSearch\v2\RestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class ListController.
 */
class ListController extends Controller {

    /**
     * @Route("/list", name="list_collection")
     * @Method({ "GET" })
     * @ApiDoc(
     *     section="List",
     *     views={ "api" }
     * )
     */
    public function listCollectionAction() {

    }

    /**
     * @Route("/list/{id}", name="list_specific")
     * @Method({ "GET" })
     * @ApiDoc(
     *     section="List",
     *     views={ "api" }
     * )
     */
    public function listAction() {

    }

    /**
     * @Route("/list", name="list_create")
     * @Method({ "POST" })
     * @ApiDoc(
     *     section="List",
     *     views={ "api" }
     * )
     */
    public function listCreateAction() {

    }

    /**
     * @Route("/list/{id}", name="list_update")
     * @Method({ "PUT" })
     * @ApiDoc(
     *     section="List",
     *     views={ "api" }
     * )
     */
    public function listUpdateAction() {

    }

    /**
     * @Route("/list/{id}", name="list_patch")
     * @Method({ "PATCH" })
     * @ApiDoc(
     *     section="List",
     *     views={ "api" }
     * )
     */
    public function listPatchAction() {

    }

    /**
     * @Route("/list/{id}", name="list_delete")
     * @Method({ "DELETE" })
     * @ApiDoc(
     *     section="List",
     *     views={ "api" }
     * )
     */
    public function listDeleteAction() {

    }
}
