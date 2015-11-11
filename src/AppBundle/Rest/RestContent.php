<?php
/**
 * @file
 */

namespace AppBundle\Rest;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;

use AppBundle\Rest\RestBaseRequest;
use AppBundle\Document\Content as FSContent;
use AppBundle\Exception\RestException;

class RestContent extends RestBaseRequest
{
    public function __construct(MongoEM $em)
    {
        parent::__construct($em);

        $this->requiredFields = array(
            'nid',
            'agency',
        );
    }

    protected function validateContent()
    {
        foreach ($this->requiredFields as $field)
        {
            if (empty($this->requestBody['body'][$field]))
            {
                throw new RestException('Required field "' . $field . '" has no value.');
            }
            elseif ($field == 'agency' && !$this->isAgencyValid($this->requestBody['body'][$field]))
            {
                throw new RestException("Tried to assign content to agency {$this->requestBody['body'][$field]} which does not exist.");
            }
        }

        if ($this->contentExists($this->requestBody['body']['nid'], $this->requestBody['body']['agency']))
        {
            throw new RestException("Content with nid {$this->requestBody['body']['nid']}, agency {$this->requestBody['body']['agency']} already exists.");
        }
    }

    public function contentExists($nid, $agencyId)
    {
        $agency = $this->getContent($nid, $agencyId);

        return !is_null($agency);
    }

    public function getContent($nid, $agencyId)
    {
        $criteria = array(
            'nid' => $nid,
            'agency' => $agencyId,
        );

        $content = $this->em
            ->getRepository('AppBundle:Content')
            ->findOneBy($criteria);

        return $content;
    }

    public function handleRequest($method)
    {
        $this->validateContent();
        $requestResult = '';

        switch ($method)
        {
            case 'POST':
                break;
            case 'PUT':
                $insertedContent = $this->insertContent();
                $requestResult = 'Created content with id: ' . $insertedContent->getId();
                break;
            case 'DELETE':
                break;
        }

        return $requestResult;
    }

    private function insertContent()
    {
        $contentObject = $this->prepareContentObject();

        $dm = $this->em->getManager();
        $dm->persist($contentObject);
        $dm->flush();

        return $contentObject;
    }

    public function prepareContentObject()
    {
        $content = new FSContent();
        $body = $this->requestBody['body'];

        $nid = !empty($body['nid']) ? $body['nid'] : 0;
        $content->setNid($nid);

        $agency = !empty($body['agency']) ? $body['agency'] : '000000';
        $content->setAgency($agency);

        $type = !empty($body['type']) ? $body['type'] : 'undefined';
        $content->setType($type);

        $fields = !empty($body['fields']) ? $body['fields'] : array();
        $content->setFields($fields);

        $taxonomy = !empty($body['taxonomy']) ? $body['taxonomy'] : array();
        $content->setTaxonomy($taxonomy);

        $list = !empty($body['list']) ? $body['list'] : array();
        $content->setList($list);

        return $content;
    }
}
