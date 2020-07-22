<?php

namespace AppBundle\Controller;

use AppBundle\Document\Content;
use AppBundle\Services\RestHelper;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Exception\RestException;
use AppBundle\Rest\RestBaseRequest;
use AppBundle\Rest\RestContentRequest;
use AppBundle\Rest\RestListsRequest;
use AppBundle\Rest\RestMenuRequest;
use AppBundle\Rest\RestTaxonomyRequest;

/**
 * Class RestController.
 */
final class RestController extends Controller
{

    private $lastStatus;

    private $lastMessage;

    private $lastMethod;

    private $lastItems;

    private $rawContent;

    /**
     * RestController constructor.
     */
    public function __construct()
    {
        $this->lastMessage = '';
        $this->lastStatus = false;
        $this->lastItems = [];
    }

    /**
     * @Route("/content", name="content")
     */
    public function contentAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();
        $this->rawContent = $request->getContent();

        $em = $this->get('doctrine_mongodb');
        $rcr = new RestContentRequest($em);

        return $this->relay($rcr);
    }

    /**
     * @ApiDoc(
     *  description="Returns a list of content.",
     *  section="Content (node) related",
     *  requirements={
     *    {
     *       "name"="agency",
     *       "dataType"="integer",
     *       "requirement"="\d+",
     *       "description"="Agency number, owner of the content."
     *    }
     *  },
     *  filters={
     *    {
     *      "name"="node",
     *      "dataType"="string",
     *      "required"=false,
     *      "description"="A single node id or a set of id's separated by comma. Using this parameter ignores any parameters below."
     *    },
     *    {
     *      "name"="amount",
     *      "dataType"="integer",
     *      "required"=false,
     *      "description"="'Hard' limit of nodes returned. Default: 10.",
     *    },
     *    {
     *      "name"="skip",
     *      "dataType"="integer",
     *      "required"=false,
     *      "description"="Fetch the result set starting from this record. Default: 0."
     *    },
     *    {
     *      "name"="sort",
     *      "dataType"="string",
     *      "required"=false,
     *      "description"="Sort the resulting set based on a certain field. The value should match the hierarchy that mongo object uses. For example, to sort by node title, use 'fields.title.value'. Default: ''."
     *    },
     *    {
     *      "name"="order",
     *      "dataType"="string",
     *      "required"=false,
     *      "format"="ASC|DESC",
     *      "description"="Order of sorting. Either ascending - 'ASC', or descending - 'DESC'. Defaults to descending."
     *    },
     *    {
     *      "name"="type",
     *      "dataType"="string",
     *      "required"=false,
     *      "description"="Only fetch specific node types."
     *    },
     *    {
     *       "name"="vocabulary[]",
     *       "dataType"="string",
     *       "description"="Vocabulary name. Can be multiple, e.g.: vocabulary[]='a'&vocabulary[]='b'.",
     *       "required"=false
     *     },
     *     {
     *       "name"="terms[]",
     *       "dataType"="string",
     *       "description"="Term name. Can be multiple, e.g.: terms[]='a'&terms[]='b'. The count of 'terms' key in the query string MUST match the count of 'vocabulary' keys.",
     *       "required"=false
     *     },
     *     {
     *       "name"="upcoming",
     *       "dataType"="boolean",
     *       "description"="Fetch only upcoming events. Viable when 'type=ding_event'.",
     *       "required"=false
     *     },
     *     {
     *       "name"="library[]",
     *       "dataType"="string",
     *       "description"="Library name. Filters the nodes only attached to this library. Can be multiple, e.g. library[]='Alpha'&library[]='Beta'",
     *       "required"=false
     *     },
     *     {
     *       "name"="status",
     *       "dataType"="string",
     *       "required"=false,
     *       "description"="Filter results by status. `0` - unpublished, `1` - published, `-1` - all. Default: 1."
     *     },
     *     {
     *       "name"="language",
     *       "dataType"="string",
     *       "required"=false,
     *       "description"="Filter results by langcode. A two character language code value."
     *     }
     *  }
     * )
     * @Route("/content/fetch", name="content_fetch")
     * @Method({"GET"})
     */
    public function contentFetchAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        // Defaults.
        $fields = [
            'agency' => null,
            'node' => null,
            'amount' => 10,
            'skip' => 0,
            'sort' => 'fields.created.value',
            'order' => 'DESC',
            'type' => null,
            'vocabulary' => [],
            'terms' => [],
            'upcoming' => 0,
            'library' => [],
            'status' => RestContentRequest::STATUS_PUBLISHED,
            'language' => null,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = $request->query->get($field) ?? $fields[$field];
        }

        $hits = 0;

        if (empty($fields['agency'])) {
            $this->lastMessage = 'Failed validating request. Check if agency is set.';
        } else {
            $em = $this->get('doctrine_mongodb');
            $rcr = new RestContentRequest($em);

            $items = call_user_func_array([$rcr, 'fetchFiltered'], $fields);

            /** @var RestHelper $restHelper */
            $restHelper = $this->container->get('rest.helper');
            if (!empty($items)) {
                /** @var Content $item */
                foreach ($items as $item) {
                    $itemFields = $item->getFields();
                    // Make sure the date is in valid format.
                    try {
                        if (!empty($itemFields['field_ding_event_date']['value']['from'])) {
                            $itemFields['field_ding_event_date']['value']['from'] = $restHelper->adjustDate(
                                $itemFields['field_ding_event_date']['value']['from']
                            );
                        }

                        if (!empty($itemFields['field_ding_event_date']['value']['to'])) {
                            $itemFields['field_ding_event_date']['value']['to'] = $restHelper->adjustDate(
                                $itemFields['field_ding_event_date']['value']['to']
                            );
                        }

                        if (!empty($itemFields['created']['value'])) {
                            $itemFields['created']['value'] = $restHelper->adjustDate($itemFields['created']['value']);
                        }

                        if (!empty($itemFields['changed']['value'])) {
                            $itemFields['changed']['value'] = $restHelper->adjustDate($itemFields['changed']['value']);
                        }
                    } catch (RestException $e) {
                        // Do nothing.
                    }

                    $this->lastItems[] = [
                        'id' => $item->getId(),
                        'nid' => $item->getNid(),
                        'agency' => $item->getAgency(),
                        'type' => $item->getType(),
                        'fields' => $itemFields,
                        'taxonomy' => $item->getTaxonomy(),
                        'list' => $item->getList(),
                    ];
                }

                $this->lastStatus = true;
            }

            // Fetch the actual hit count.
            $fields['countOnly'] = true;
            $hits = call_user_func_array([$rcr, 'fetchFiltered'], $fields);
        }

        return $this->setResponse(
            $this->lastStatus,
            $this->lastMessage,
            $this->lastItems,
            $hits
        );
    }

    /**
     * @Route("/content/search", name="content_search")
     * @Method({"GET"})
     * @ApiDoc(
     *   description="Search for content by querying certain field.",
     *   section="Content (node) related",
     *   requirements={
     *     {
     *       "name"="agency",
     *       "dataType"="integer",
     *       "requirement"="\d+",
     *       "description"="Agency number, owner of the content."
     *     },
     *     {
     *       "name"="field",
     *       "dataType"="string",
     *       "description"="Specific field where to search for. The value should match the hierarchy that mongo object uses. For example, to search within node title, use 'fields.title.value'."
     *     },
     *     {
     *       "name"="query",
     *       "dataType"="string",
     *       "description"="The search query."
     *     }
     *   },
     *   filters={
     *     {
     *       "name"="amount",
     *       "dataType"="integer",
     *       "required"=false,
     *       "description"="'Hard' limit of results returned. Default: 10."
     *     },
     *     {
     *       "name"="skip",
     *       "dataType"="integer",
     *       "required"=false,
     *       "description"="Fetch the result set starting from this record. Default: 0."
     *     },
     *     {
     *       "name"="status",
     *       "dataType"="string",
     *       "required"=false,
     *       "description"="Filter results by status. `0` - unpublished, `1` - published, `-1` - all. Default: 1."
     *     },
     *     {
     *       "name"="upcoming",
     *       "dataType"="boolean",
     *       "required"=false,
     *       "description"="Fetch only upcoming events. Viable when 'field=type & query=ding_event'. Default: 1."
     *     }
     *   }
     * )
     */
    public function searchAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
            'query' => null,
            'field' => null,
            'amount' => 10,
            'skip' => 0,
            'status' => RestContentRequest::STATUS_PUBLISHED,
            'upcoming' => 1,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = $request->query->get($field) ?? $fields[$field];
        }

        $em = $this->get('doctrine_mongodb');
        $rcr = new RestContentRequest($em);
        $hits = 0;

        if (empty($fields['agency'])) {
            $this->lastMessage = 'Failed validating request. Check if agency is set.';
        } elseif (!empty($fields['query'])) {
            $this->lastItems = [];

            $suggestions = call_user_func_array([$rcr, 'fetchSuggestions'], $fields);

            /** @var Content $suggestion */
            foreach ($suggestions as $suggestion) {
                $_fields = $suggestion->getFields();
                $item = [
                    'id' => $suggestion->getId(),
                    'nid' => $suggestion->getNid(),
                    'title' => isset($_fields['title']['value']) ? $_fields['title']['value'] : '',
                    'changed' => isset($_fields['changed']['value']) ? $_fields['changed']['value'] : '',
                    'type' => $suggestion->getType(),
                    'status' => $_fields['status']['value'],
                ];

                if ('ding_event' == $suggestion->getType()) {
                    $item['event_date'] = [
                        'from' => $_fields['field_ding_event_date']['value']['from'],
                        'to' => $_fields['field_ding_event_date']['value']['to'],
                        'all_day' => $_fields['field_ding_event_date']['attr']['all_day'] ?? false,
                    ];
                }

                $this->lastItems[] = $item;
            }

            // Fetch the actual hit count.
            $fields['countOnly'] = true;
            $hits = call_user_func_array([$rcr, 'fetchSuggestions'], $fields);

            $this->lastStatus = true;
        }

        return $this->setResponse(
            $this->lastStatus,
            $this->lastMessage,
            $this->lastItems,
            $hits
        );
    }

    /**
     * @Route("/menu", name="menu")
     */
    public function menuAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();
        $this->rawContent = $request->getContent();

        $em = $this->get('doctrine_mongodb');
        $rmr = new RestMenuRequest($em);

        return $this->relay($rmr);
    }

    /**
     * @Route("/list", name="list")
     */
    public function listsAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();
        $this->rawContent = $request->getContent();

        $em = $this->get('doctrine_mongodb');
        $rlr = new RestListsRequest($em);

        return $this->relay($rlr);
    }

    /**
     * @Route("/taxonomy/vocabularies/{contentType}", name="vocabularies")
     * @Method({"GET"})
     * @ApiDoc(
     *   description="Fetches vocabularies for a specific node type.",
     *   section="Vocabulary (taxonomy) related",
     *   requirements={
     *     {
     *       "name"="agency",
     *       "dataType"="integer",
     *       "requirement"="\d+",
     *       "description"="Agency number, owner of the content."
     *     }
     *   }
     * )
     */
    public function taxonomyAction(Request $request, $contentType)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = $request->query->get($field);
        }

        $em = $this->get('doctrine_mongodb');
        $rtr = new RestTaxonomyRequest($em);

        if (empty($fields['agency'])) {
            $this->lastMessage = 'Failed validating request. Check if agency is set.';
        } else {
            $vocabularies = $rtr->fetchVocabularies($fields['agency'], $contentType);

            $this->lastItems = $vocabularies;
            $this->lastStatus = true;
        }

        return $this->setResponse(
            $this->lastStatus,
            $this->lastMessage,
            $this->lastItems
        );
    }

    /**
     * @Route("/taxonomy/terms/{vocabulary}/{contentType}/{query}", name="terms")
     * @Method({"GET"})
     * @ApiDoc(
     *   description="Fetches term suggestions from a certain vocabulary that is related to a specific content (node) type.", section="Vocabulary (taxonomy) related", requirements={
     *     {
     *       "name"="agency",
     *       "dataType"="integer",
     *       "requirement"="\d+",
     *       "description"="Agency number, owner of the content."
     *     }
     *   }
     * )
     */
    public function taxonomySearchAction(Request $request, $vocabulary, $contentType, $query)
    {
        $this->lastMethod = $request->getMethod();

        $fields = [
            'agency' => null,
        ];

        foreach (array_keys($fields) as $field) {
            $fields[$field] = $request->query->get($field);
        }

        $em = $this->get('doctrine_mongodb');
        $rtr = new RestTaxonomyRequest($em);

        if (empty($fields['agency'])) {
            $this->lastMessage = 'Failed validating request. Check if agency is set.';
        } else {
            $suggestions = $rtr->fetchTermSuggestions($fields['agency'], $vocabulary, $contentType, $query);

            $this->lastItems = $suggestions;
            $this->lastStatus = true;
        }

        return $this->setResponse(
            $this->lastStatus,
            $this->lastMessage,
            $this->lastItems
        );
    }

    /**
     * Relays request for further processing.
     *
     * This method is used whenever records are altered, i.e. everywhere
     * except GET requests.
     *
     * @param RestBaseRequest $rbr
     *   Base request object instance.
     *
     * @return Response
     *   Response object.
     */
    private function relay(RestBaseRequest $rbr)
    {
        try {
            $rbr->setRequestBody($this->rawContent);
            $result = $rbr->handleRequest($this->lastMethod);
            $this->lastMessage = $result;
            $this->lastStatus = true;
        } catch (RestException $exc) {
            $this->lastMessage = 'Request fault: '.$exc->getMessage();
        } catch (\Exception $exc) {
            $this->lastMessage = 'Generic fault: '.$exc->getMessage();
        }

        return $this->setResponse($this->lastStatus, $this->lastMessage);
    }

    /**
     * Assembles the response object.
     *
     * @param bool $status
     *   Request status.
     * @param string $message
     *   Request service message, if any.
     * @param array $items
     *   Response items, uf any.
     * @param null $hits
     *   Number of hits, if any.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Response object.
     */
    private function setResponse($status = true, $message = '', $items = [], $hits = NULL)
    {
        $responseContent = [
            'status' => $status,
            'message' => $message,
            'items' => $items,
        ];

        // Only include hit count when there are actual items.
        if (NULL !== $hits) {
            $responseContent['hits'] = $hits;
        }

        $response = new JsonResponse($responseContent);
        $response->headers->set('Content-Type', 'application/json');
        $response->setSharedMaxAge(600);
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }
}
