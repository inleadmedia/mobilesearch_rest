<?php

namespace MobileSearch\v2\RestBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController.
 */
class ContentController extends Controller
{
    use PaginatedResourcesTrait;

    use NavigatedResourcesTrait;

    /**
     * @Route("/content", name="content_collection")
     * @Method({"GET"})
     * @ApiDoc(
     *     section = "Content",
     *     views = { "api" },
     *     requirements = {
     *         {
     *             "name" = "agency",
     *             "dataType" = "integer",
     *             "requirement" = "\d+",
     *             "description" = "Agency number, owner of the content."
     *         }
     *     },
     *     parameters = {
     *         {
     *             "name"="amount",
     *             "dataType"="string",
     *             "required"=false,
     *             "description"="'Hard' limit of nodes returned. Default: 10.",
     *         },
     *         {
     *             "name"="skip",
     *             "dataType"="string",
     *             "required"=false,
     *             "description"="Fetch the result set starting from this record. Default: 0."
     *         },
     *         {
     *             "name"="sort",
     *             "dataType"="string",
     *             "required"=false,
     *             "description"="Sort the resulting set based on a certain field. The value should match the hierarchy that mongo object uses. For example, to sort by node title, use 'fields.title.value'. Default: ''."
     *         },
     *         {
     *             "name"="order",
     *             "dataType"="string",
     *             "required"=false,
     *             "format"="ASC|DESC",
     *             "description"="Order of sorting. Either ascending - 'ASC', or descending - 'DESC'. Defaults to descending."
     *         }
     *     },
     *     filters = {
     *         {
     *             "name"="filter[node][]",
     *             "dataType"="string",
     *             "required"=false,
     *             "description"="Node id. Can be multiple, e.g.: filter[node][]=203&filter[node][]=9067."
     *         },
     *         {
     *             "name"="filter[type]",
     *             "dataType"="string",
     *             "required"=false,
     *             "description"="Only fetch specific node types."
     *         },
     *         {
     *             "name"="filter[vocabulary][]",
     *             "dataType"="string",
     *             "description"="Vocabulary name. Can be multiple, e.g.: filter[vocabulary][]='a'&filter[vocabulary][]='b'.",
     *             "required"=false
     *         },
     *         {
     *             "name"="filter[terms][]",
     *             "dataType"="string",
     *             "description"="Term name. Can be multiple, e.g.: filter[terms][]='a'&filter[terms][]='b'. The count of 'terms' key in the query string MUST match the count of 'vocabulary' keys.",
     *             "required"=false
     *         },
     *         {
     *             "name"="filter[upcoming]",
     *             "dataType"="boolean",
     *             "description"="Fetch only upcoming events. Viable when 'type=ding_event'.",
     *             "required"=false
     *         },
     *         {
     *             "name"="filter[library][]",
     *             "dataType"="string",
     *             "description"="Library name. Filters the nodes only attached to this library. Can be multiple, e.g. filter[library][]='Alpha'&filter[library][]='Beta'",
     *             "required"=false
     *         },
     *         {
     *             "name"="filter[status]",
     *             "dataType"="string",
     *             "required"=false,
     *             "description"="Filter results by status. `0` - unpublished, `1` - published, `-1` - all. Default: 1."
     *         },
     *         {
     *             "name"="filter[language]",
     *             "dataType"="string",
     *             "required"=false,
     *             "description"="Filter results by langcode. A two character language code value."
     *         }
     *     }
     * )
     */
    public function contentCollectionAction(Request $request)
    {
        $filters = [];
        foreach ($request->query->get('filter') ?? [] as $filterName => $filterValue) {
            $filters[$filterName] = $filterValue;
        }

        $payload = $request->query->all();
        unset($payload['filters']);

        $result = $this->forward(
            'AppBundle:Rest:contentFetch',
            [],
            array_merge($payload, $filters)
        );

        $rawResult = json_decode($result->getContent(), true);
        $items = $rawResult['items'];
        $message = $rawResult['message'];
        $status = $rawResult['status'];
        $hits = $rawResult['hits'];

        $result = [
            'data' => [
                'items' => $items,
            ],
        ];

        $pagination = $this->generatePaginationLinks(
            $request->query->get('amount') ?? $this->getParameter('mobile_search_v2_rest.items_limit'),
            $request->query->get('skip') ?? $this->getParameter('mobile_search_v2_rest.items_offset'),
            $hits
        );

        $navigation = $this->generateNavigationLinks(
            $request->getUri(),
            $request->query->get('amount') ?? $this->getParameter('mobile_search_v2_rest.items_limit'),
            $request->query->get('skip') ?? $this->getParameter('mobile_search_v2_rest.items_offset'),
            $hits
        );

        $result = array_merge($result, $pagination, $navigation);

        return new JsonResponse($result);
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

    }
}
