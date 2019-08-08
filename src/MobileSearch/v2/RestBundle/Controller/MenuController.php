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
     * @Route("/menus", name="menu_collection")
     * @Method({ "GET" })
     * @ApiDoc(
     *     section="Menus",
     *     views={ "api" }
     * )
     */
    public function menuCollectionAction()
    {

    }

    /**
     * @Route("/menus/{id}", name="menu_specific")
     * @Method({ "GET" })
     * @ApiDoc(
     *     section="Menus",
     *     views={ "api" }
     * )
     */
    public function menuAction()
    {

    }

    /**
     * @Route("/menus", name="menu_create")
     * @Method({ "POST" })
     * @ApiDoc(
     *     section="Menus",
     *     views={ "api" }
     * )
     */
    public function menuCreateAction()
    {

    }

    /**
     * @Route("/menus/{id}", name="menu_update")
     * @Method({ "PUT" })
     * @ApiDoc(
     *     section="Menus",
     *     views={ "api" }
     * )
     */
    public function menuUpdateAction()
    {

    }

    /**
     * @Route("/menus/{id}", name="menu_patch")
     * @Method({ "PATCH" })
     * @ApiDoc(
     *     section="Menus",
     *     views={ "api" }
     * )
     */
    public function menuPatchAction()
    {

    }

    /**
     * @Route("/menus/{id}", name="menu_delete")
     * @Method({ "DELETE" })
     * @ApiDoc(
     *     section="Menus",
     *     views={ "api" }
     * )
     */
    public function menuDeleteAction()
    {

    }
}
