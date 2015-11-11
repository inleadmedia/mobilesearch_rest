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

    protected function validate()
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
    }

    public function exists($nid, $agencyId)
    {
        $agency = $this->get($nid, $agencyId);

        return !is_null($agency);
    }

    public function get($nid, $agencyId)
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
        $this->validate();
        $requestResult = '';

        $nid = $this->requestBody['body']['nid'];
        $agency = $this->requestBody['body']['agency'];

        switch ($method)
        {
            case 'POST':
                if (!$this->exists($nid, $agency))
                {
                    throw new RestException("Content with nid {$nid}, agency {$agency} does not exist.");
                }
                else {
                    $updatedContent = $this->update($nid, $agency);
                    $requestResult = 'Updated content with id: ' . $updatedContent->getId();
                }
                break;
            case 'PUT':
                if ($this->exists($nid, $agency))
                {
                    throw new RestException("Content with nid {$nid}, agency {$agency} already exists.");
                }
                else {
                    $insertedContent = $this->insert();
                    $requestResult = 'Created content with id: ' . $insertedContent->getId();
                }
                break;
            case 'DELETE':
                if (!$this->exists($nid, $agency))
                {
                    throw new RestException("Content with nid {$nid}, agency {$agency} does not exist.");
                }
                else {
                    $deletedContent = $this->delete($nid, $agency);
                    $requestResult = 'Deleted content with id: ' . $deletedContent->getId();
                }
                break;
        }

        return $requestResult;
    }

    private function insert()
    {
        $contentObject = $this->prepare(new FSContent());

        $dm = $this->em->getManager();
        $dm->persist($contentObject);
        $dm->flush();

        return $contentObject;
    }

    private function update($nid, $agencyId)
    {
        $contentObject = $this->get($nid, $agencyId);
        $updatedObject = $this->prepare($contentObject);

        $dm = $this->em->getManager();
        $dm->flush();

        return $updatedObject;
    }

    private function delete($nid, $agencyId)
    {
        $contentObject = $this->get($nid, $agencyId);

        $dm = $this->em->getManager();
        $dm->remove($contentObject);
        $dm->flush();

        return $contentObject;
    }

    public function prepare(FSContent $content)
    {
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
