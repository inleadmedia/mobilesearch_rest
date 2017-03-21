<?php
/**
 * @file
 */

namespace AppBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Exception\RestException;
use AppBundle\Rest\RestBaseRequest;
use AppBundle\Rest\RestContentRequest;
use AppBundle\Rest\RestListsRequest;
use AppBundle\Rest\RestMenuRequest;
use AppBundle\Rest\RestTaxonomyRequest;

final class RestController extends Controller
{
    private $lastStatus;
    private $lastMessage;
    private $lastMethod;
    private $lastItems;
    private $rawContent;

    public function __construct()
    {
        $this->lastMessage = '';
        $this->lastStatus = FALSE;
        $this->lastItems = array();
    }

    /**
     * @Route("/content")
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
     *    },
     *    {
     *       "name"="key",
     *       "dataType"="string",
     *       "description"="Unique hash for authentication. Built by using sha1 hash on a string of agency and secret key.",
     *       "requirement"="[a-f0-9]+",
     *    }
     *  },
     *  filters={
     *    {
     *      "name"="node",
     *      "dataType"="string",
     *      "required"=false,
     *      "format"="\d+?(,\d+)",
     *      "description"="A single node id or a set of id's separated by comma. Use either this or 'amount' filter below, since they invalidate each other."
     *    },
     *    {
     *      "name"="amount",
     *      "dataType"="integer",
     *      "required"=false,
     *      "format"="\d+",
     *      "description"="Fetch a certain number of nodes. When this is set, the parameters below can be used."
     *    },
     *  },
     *  parameters={
     *    {
     *      "name"="sort",
     *      "dataType"="string",
     *      "required"=false,
     *      "format"="\w+?(\.\w+)",
     *      "description"="Sort the resulting set based on a certain field. The value should match the hierarchy that mongo object uses. For example, to sort by node title, use 'fields.title.value'. By default no sorting is applied, records are returned as they are stored."
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
     *      "format"="\w+",
     *      "description"="Only fetch a specific type of nodes. Node type is defined in 'type' key of each object in the result set."
     *    },
     *    {
     *      "name"="skip",
     *      "dataType"="integer",
     *      "required"=false,
     *      "format"="\d+",
     *      "description"="Fetch the result set starting from this record."
     *    },
     *  }
     * )
     * @Route("/content/fetch")
     * @Method({"GET"})
     */
    public function contentFetchAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        $fields = array(
            'agency' => null,
            'key' => null,
            'amount' => null,
            'sort' => null,
            'order' => null,
            'node' => null,
            'type' => null,
            'skip' => null,
        );

        foreach (array_keys($fields) as $field) {
            $fields[$field] = $request->query->get($field);
        }

        $em = $this->get('doctrine_mongodb');
        $rcr = new RestContentRequest($em);

        if (!$rcr->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        }
        elseif (!empty($fields['node']))
        {
            $nids = explode(',', $fields['node']);

            $items = $rcr->fetchContent($nids, $fields['agency']);
            if (!empty($items)) {
                foreach ($items as $item) {
                    $this->lastItems[] = array(
                      'id' => $item->getId(),
                      'nid' => $item->getNid(),
                      'agency' => $item->getAgency(),
                      'type' => $item->getType(),
                      'fields' => $item->getFields(),
                      'taxonomy' => $item->getTaxonomy(),
                      'list' => $item->getList(),
                    );
                }

                $this->lastStatus = true;
            }
            else {
                $this->lastMessage = "Entity with id {$fields['node']}, agency {$fields['agency']} does not exist.";
            }
        }
        elseif (!empty($fields['amount']))
        {
            $items = $rcr->fetchXAmount($fields['agency'], $fields['amount'], $fields['sort'], $fields['order'], $fields['type'], $fields['skip']);
            $this->lastItems = array();
            foreach ($items as $item) {
                $this->lastItems[] = array(
                    'id' => $item->getId(),
                    'nid' => $item->getNid(),
                    'agency' => $item->getAgency(),
                    'type' => $item->getType(),
                    'fields' => $item->getFields(),
                    'taxonomy' => $item->getTaxonomy(),
                    'list' => $item->getList(),
                );
            }

            $this->lastStatus = true;
        }
        else
        {
            $this->lastMessage = 'Failed validating request. No action specified.';
        }

        return $this->setResponse($this->lastStatus, $this->lastMessage, $this->lastItems);
    }

    /**
     * @Route("/content/search")
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
     *       "name"="key",
     *       "dataType"="string",
     *       "description"="Unique hash for authentication. Built by using sha1 hash on a string of agency and secret key.",
     *       "requirement"="[a-f0-9]+",
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
     * )
     */
    function searchAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        $fields = array(
          'agency' => null,
          'key' => null,
          'field' => null,
          'query' => null,
        );

        foreach (array_keys($fields) as $field) {
            $fields[$field] = $request->query->get($field);
        }

        $em = $this->get('doctrine_mongodb');
        $rcr = new RestContentRequest($em);

        if (!$rcr->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        } elseif (!empty($fields['query'])) {
            $this->lastItems = array();

            $suggestions = $rcr->fetchSuggestions($fields['agency'], $fields['query'], $fields['field']);
            foreach ($suggestions as $suggestion) {
                $fields = $suggestion->getFields();
                $this->lastItems[] = array(
                  'id' => $suggestion->getId(),
                  'nid' => $suggestion->getNid(),
                  'title' => isset($fields['title']['value']) ? $fields['title']['value'] : '',
                  'changed' => isset($fields['changed']['value']) ? $fields['changed']['value'] : '',
                );
            }

            $this->lastStatus = true;
        }

        return $this->setResponse(
          $this->lastStatus,
          $this->lastMessage,
          $this->lastItems
        );
    }

    /**
     * @Route("/menu")
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
     * @Route("/list")
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
     * @Route("/taxonomy/vocabularies/{contentType}")
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
     *     },
     *     {
     *       "name"="key",
     *       "dataType"="string",
     *       "description"="Unique hash for authentication. Built by using sha1 hash on a string of agency and secret key.",
     *       "requirement"="[a-f0-9]+",
     *     },
     *   }
     * )
     */
    public function taxonomyAction(Request $request, $contentType)
    {
        $this->lastMethod = $request->getMethod();

        $fields = array(
            'agency' => null,
            'key' => null
        );

        foreach (array_keys($fields) as $field) {
            $fields[$field] = $request->query->get($field);
        }

        $em = $this->get('doctrine_mongodb');
        $rtr = new RestTaxonomyRequest($em);

        if (!$rtr->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        }
        else {
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
     * @Route("/taxonomy/terms/{vocabulary}/{contentType}/{query}")
     * @Method({"GET"})
     * @ApiDoc(
     *   description="Fetches term suggestions from a certain vocabulary that is related to a specific content (node) type.",
     *   section="Vocabulary (taxonomy) related",
     *   requirements={
     *     {
     *       "name"="agency",
     *       "dataType"="integer",
     *       "requirement"="\d+",
     *       "description"="Agency number, owner of the content."
     *     },
     *     {
     *       "name"="key",
     *       "dataType"="string",
     *       "description"="Unique hash for authentication. Built by using sha1 hash on a string of agency and secret key.",
     *       "requirement"="[a-f0-9]+",
     *     },
     *   }
     * )
     */
    public function taxonomySearchAction(Request $request, $vocabulary, $contentType, $query)
    {
        $this->lastMethod = $request->getMethod();

        $fields = array(
            'agency' => null,
            'key' => null
        );

        foreach (array_keys($fields) as $field) {
            $fields[$field] = $request->query->get($field);
        }

        $em = $this->get('doctrine_mongodb');
        $rtr = new RestTaxonomyRequest($em);

        if (!$rtr->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        }
        else {
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
     * @Route("/content/related")
     * @Method({"GET"})
     * @ApiDoc(
     *   description="Fetches term suggestions from a certain vocabulary that is related to a specific content (node) type. The content is fetched based on 'AND' logic across vocabularies and 'OR' across terms of the same vocabulary.",
     *   section="Content (node) related",
     *   requirements={
     *     {
     *       "name"="agency",
     *       "dataType"="integer",
     *       "requirement"="\d+",
     *       "description"="Agency number, owner of the content."
     *     },
     *     {
     *       "name"="key",
     *       "dataType"="string",
     *       "description"="Unique hash for authentication. Built by using sha1 hash on a string of agency and secret key.",
     *       "requirement"="[a-f0-9]+",
     *     },
     *   },
     *   parameters={
     *     {
     *       "name"="vocabulary[]",
     *       "dataType"="string",
     *       "description"="Vocabulary name. Can be multiple, e.g.: vocabulary[]='a'&vocabulary[]='b'.",
     *       "required"=true,
     *       "format"="\w+?(_\w+)",
     *     },
     *     {
     *       "name"="terms[]",
     *       "dataType"="string",
     *       "description"="Term name. Can be multiple, e.g.: terms[]='a'&terms[]='b'. The count of 'terms' key in the query string MUST match the count of 'vocabulary' key.",
     *       "required"=true,
     *       "format"="\w+?(_\w+)",
     *     },
     *     {
     *      "name"="sort",
     *      "dataType"="string",
     *      "required"=false,
     *      "format"="\w+?(\.\w+)",
     *      "description"="Sort the resulting set based on a certain field. The value should match the hierarchy that mongo object uses. For example, to sort by node title, use 'fields.title.value'. By default no sorting is applied, records are returned as they are stored."
     *    },
     *    {
     *      "name"="order",
     *      "dataType"="string",
     *      "required"=false,
     *      "format"="ASC|DESC",
     *      "description"="Order of sorting. Either ascending - 'ASC', or descending - 'DESC'. Defaults to descending."
     *    },
     *    {
     *      "name"="skip",
     *      "dataType"="integer",
     *      "required"=false,
     *      "format"="\d+",
     *      "description"="Fetch the result set starting from this record."
     *    },
     *   }
     * )
     */
    public function taxonomyRelatedContentAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        // Defaults.
        $fields = array(
          'agency' => null,
          'key' => null,
          'vocabulary' => null,
          'terms' => null,
          'sort' => null,
          'order' => 'DESC',
          'amount' => 10,
          'skip' => 0,
        );

        foreach (array_keys($fields) as $field) {
            $fields[$field] = !empty($request->query->get($field)) ? $request->query->get($field) : $fields[$field];
        }

        $em = $this->get('doctrine_mongodb');
        $rtr = new RestTaxonomyRequest($em);

        if (!$rtr->isSignatureValid($fields['agency'], $fields['key'])) {
            $this->lastMessage = 'Failed validating request. Check your credentials (agency & key).';
        }
        elseif (is_array($fields['vocabulary']) && is_array($fields['terms'])) {
            unset($fields['key']);
            $items = call_user_func_array(array($rtr, 'fetchRelatedContent'), $fields);

            $this->lastItems = array();

            if (!empty($items)) {
                foreach ($items as $item) {
                    $this->lastItems[] = array(
                      'id' => $item->getId(),
                      'nid' => $item->getNid(),
                      'agency' => $item->getAgency(),
                      'type' => $item->getType(),
                      'fields' => $item->getFields(),
                      'taxonomy' => $item->getTaxonomy(),
                      'list' => $item->getList(),
                    );
                }
            }

            $this->lastStatus = true;
        }


        return $this->setResponse(
            $this->lastStatus,
            $this->lastMessage,
            $this->lastItems
        );
    }

    private function relay(RestBaseRequest $rbr)
    {
        try
        {
            $rbr->setRequestBody($this->rawContent);
            $result = $rbr->handleRequest($this->lastMethod);
            $this->lastMessage = $result;
            $this->lastStatus = true;
        }
        catch (RestException $exc)
        {
            $this->lastMessage = 'Request fault: ' . $exc->getMessage();
        }
        catch (\Exception $exc)
        {
            $this->lastMessage = 'Generic fault: ' . $exc->getMessage();
        }

        $response = $this->setResponse($this->lastStatus, $this->lastMessage);

        return $response;
    }

    private function setResponse($status = true, $message = '', $items = array())
    {
        $responseContent = array(
            'status' => $status,
            'message' => $message,
            'items' => $items,
        );

        $response = new Response(json_encode($responseContent));
        $response->headers->set('Content-Type', 'application/json');
        $response->setSharedMaxAge(600);
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }
}
