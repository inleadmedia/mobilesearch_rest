<?php

namespace MobileSearch\v2\RestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class MenuController.
 */
class MenuController extends Controller
{

    /**
     * @Route("/menu", name="menu_collection")
     * @Method({ "GET" })
     * @ApiDoc(
     *     section="Menu",
     *     views={ "api" }
     * )
     */
    public function menuCollectionAction()
    {

    }

    /**
     * @Route("/menu/{id}", name="menu_specific")
     * @Method({ "GET" })
     * @ApiDoc(
     *     section="Menu",
     *     views={ "api" }
     * )
     */
    public function menuAction()
    {

    }

    /**
     * @Route("/menu", name="menu_create")
     * @Method({ "POST" })
     * @ApiDoc(
     *     section="Menu",
     *     views={ "api" }
     * )
     */
    public function menuCreateAction()
    {

    }

    /**
     * @Route("/menu/{id}", name="menu_update")
     * @Method({ "PUT" })
     * @ApiDoc(
     *     section="Menu",
     *     views={ "api" }
     * )
     */
    public function menuUpdateAction()
    {

    }

    /**
     * @Route("/menu/{id}", name="menu_patch")
     * @Method({ "PATCH" })
     * @ApiDoc(
     *     section="Menu",
     *     views={ "api" }
     * )
     */
    public function menuPatchAction()
    {

    }

    /**
     * @Route("/menu/{id}", name="menu_delete")
     * @Method({ "DELETE" })
     * @ApiDoc(
     *     section="Menu",
     *     views={ "api" }
     * )
     */
    public function menuDeleteAction()
    {

    }
}
