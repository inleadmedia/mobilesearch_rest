<?php
/**
 * @file
 */

namespace AppBundle\Controller;

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
     * @todo
     * Re-factor.
     *
     * @Route("/content/fetch")
     */
    public function contentFetchAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        if ($this->lastMethod == 'GET') {
            $fields = array(
                'agency' => null,
                'key' => null,
                'amount' => null,
                'sort' => null,
                'order' => null,
                'node' => null,
                'property' => null,
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
        }

        return $this->setResponse($this->lastStatus, $this->lastMessage, $this->lastItems);
    }

    /**
     * @Route("/content/search")
     */
    function searchAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        if ($this->lastMethod == 'GET') {
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
     */
    public function taxonomyRelatedContentAction(Request $request)
    {
        $this->lastMethod = $request->getMethod();

        $fields = array(
            'agency' => null,
            'key' => null,
            'vocabulary' => null,
            'terms' => null
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
            $items = $rtr->fetchRelatedContent($fields['agency'], (array) $fields['vocabulary'], (array) $fields['terms']);
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
                        'list' => $item->getList()
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

        return $response;
    }
}
