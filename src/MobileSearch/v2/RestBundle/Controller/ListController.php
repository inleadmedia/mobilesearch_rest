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
     * @Route("/lists", name="list_collection")
     * @Method({ "GET" })
     * @ApiDoc(
     *     section="Lists",
     *     views={ "api" }
     * )
     */
    public function listCollectionAction() {

    }

    /**
     * @Route("/lists/{id}", name="list_specific")
     * @Method({ "GET" })
     * @ApiDoc(
     *     section="Lists",
     *     views={ "api" }
     * )
     */
    public function listAction() {

    }

    /**
     * @Route("/lists", name="list_create")
     * @Method({ "POST" })
     * @ApiDoc(
     *     section="Lists",
     *     views={ "api" }
     * )
     */
    public function listCreateAction() {

    }

    /**
     * @Route("/lists/{id}", name="list_update")
     * @Method({ "PUT" })
     * @ApiDoc(
     *     section="Lists",
     *     views={ "api" }
     * )
     */
    public function listUpdateAction() {

    }

    /**
     * @Route("/lists/{id}", name="list_patch")
     * @Method({ "PATCH" })
     * @ApiDoc(
     *     section="Lists",
     *     views={ "api" }
     * )
     */
    public function listPatchAction() {

    }

    /**
     * @Route("/lists/{id}", name="list_delete")
     * @Method({ "DELETE" })
     * @ApiDoc(
     *     section="Lists",
     *     views={ "api" }
     * )
     */
    public function listDeleteAction() {

    }
}
