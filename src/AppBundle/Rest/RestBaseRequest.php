<?php
/**
 * @file
 */

namespace AppBundle\Rest;

use AppBundle\Document\Agency;
use AppBundle\Exception\RestException;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;

abstract class RestBaseRequest
{

    protected $agencyId = null;

    protected $signature = null;

    protected $requestBody = null;

    protected $requiredFields = [];

    protected $em = null;

    protected $primaryIdentifier = '';

    /**
     * Fetches a record with a certain id attached to a specific agency.
     *
     * @param $id
     * @param $agency
     *
     * @return mixed
     */
    abstract protected function get($id, $agency);

    /**
     * Checks whether a record with a certain id and agency exists.
     *
     * @param $id
     * @param $agency
     *
     * @return mixed
     */
    abstract protected function exists($id, $agency);

    /**
     * Stores a record.
     *
     * @return mixed
     */
    abstract protected function insert();

    /**
     * Updates a record.
     *
     * @param $id
     * @param $agency
     *
     * @return mixed
     */
    abstract protected function update($id, $agency);

    /**
     * Deletes a record.
     *
     * @param $id
     * @param $agency
     *
     * @return mixed
     */
    abstract protected function delete($id, $agency);

    /**
     * RestBaseRequest constructor.
     *
     * @param MongoEM $em
     */
    public function __construct(MongoEM $em)
    {
        $this->em = $em;
    }

    /**
     * Processes http requests.
     *
     * @param $method
     * @return string
     *
     * @throws RestException
     */
    public function handleRequest($method)
    {
        $this->validate();
        $requestResult = '';
        $requestBody = $this->getParsedBody();

        $id = $requestBody[$this->primaryIdentifier];
        $agency = $requestBody['agency'];

        switch ($method) {
            case 'POST':
                if (!$this->exists($id, $agency)) {
                    throw new RestException("Entity with id {$id}, agency {$agency} does not exist.");
                } else {
                    $updatedContent = $this->update($id, $agency);
                    $requestResult = 'Updated entity with id: '.$updatedContent->getId();
                }
                break;
            case 'PUT':
                if ($this->exists($id, $agency)) {
                    throw new RestException("Entity with id {$id}, agency {$agency} already exists.");
                } else {
                    $insertedContent = $this->insert();
                    $requestResult = 'Created entity with id: '.$insertedContent->getId();
                }
                break;
            case 'DELETE':
                if (!$this->exists($id, $agency)) {
                    throw new RestException("Entity with id {$id}, agency {$agency} does not exist.");
                } else {
                    $deletedContent = $this->delete($id, $agency);
                    $requestResult = 'Deleted entity with id: '.$deletedContent->getId();
                }
                break;
        }

        return $requestResult;
    }

    /**
     * Validates request by checking required field values.
     *
     * @throws RestException
     */
    protected function validate()
    {
        $body = $this->getParsedBody();
        foreach ($this->requiredFields as $field) {
            if (empty($body[$field])) {
                throw new RestException('Required field "'.$field.'" has no value.');
            } elseif ($field == 'agency' && !$this->isChildAgencyValid($body[$field])) {
                throw new RestException("Tried to modify entity using agency {$body[$field]} which does not exist.");
            }
        }
    }

    /**
     * Checks whether an agency has a parent.
     *
     * @param $childAgency
     *
     * @return bool
     */
    public function isChildAgencyValid($childAgency)
    {
        $agencyEntity = $this->getAgencyById($this->agencyId);

        if ($agencyEntity) {
            $children = $agencyEntity->getChildren();
            if ((is_array($children) && in_array($childAgency, $children)) || $childAgency == $this->agencyId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets http request body.
     *
     * @param $requestBody
     *
     * @throws RestException
     */
    public function setRequestBody($requestBody)
    {
        $this->requestBody = json_decode($requestBody, true);
        $this->validateRequest();
    }

    /**
     * Validates request by checking sanity of the http payload.
     *
     * @throws RestException
     */
    private function validateRequest()
    {
        $exceptionMessage = '';

        if (!$this->requestBody) {
            $exceptionMessage = 'Failed parsing request.';
        } elseif (!$this->isRequestValid()) {
            $exceptionMessage = 'Failed validating request. Check your credentials (agency & key).';
        } elseif (empty($this->getParsedBody())) {
            $exceptionMessage = 'Empty request body.';
        }

        if (!empty($exceptionMessage)) {
            throw new RestException($exceptionMessage);
        }

        $this->agencyId = $this->getParsedCredentials()['agencyId'];
        $this->signature = $this->getParsedCredentials()['key'];
    }

    /**
     * Checks whether authorisation values are valid.
     *
     * @return bool
     */
    private function isRequestValid()
    {
        $isValid = true;

        $requiredFields = [
            'agencyId',
            'key',
        ];

        $requestCredentials = $this->getParsedCredentials();
        foreach ($requiredFields as $field) {
            if (empty($requestCredentials[$field])) {
                $isValid = false;
            } elseif ($field == 'agencyId' && !$this->isAgencyValid($requestCredentials[$field])) {
                $isValid = false;
            } elseif ($field == 'key' && !$this->isSignatureValid($requestCredentials['agencyId'], $requestCredentials['key'])) {
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * Checks whether agency is valid.
     *
     * @param $agencyId
     *
     * @return bool
     */
    public function isAgencyValid($agencyId)
    {
        $agency = $this->getAgencyById($agencyId);

        return !is_null($agency);
    }

    /**
     * Fetches an agency by id.
     *
     * @param $agencyId
     *
     * @return Agency
     */
    public function getAgencyById($agencyId)
    {
        $agency = $this->em
            ->getRepository('AppBundle:Agency')
            ->findOneBy(['agencyId' => $agencyId]);

        return $agency;
    }

    /**
     * Validates signature value.
     *
     * @param $agencyId
     * @param $signature
     *
     * @return bool
     */
    public function isSignatureValid($agencyId, $signature)
    {
        $agency = $this->getAgencyById($agencyId);

        if ($agency) {
            $key = $agency->getKey();
            $targetSignature = sha1($agencyId.$key);

            if ($signature == $targetSignature) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets http payload body.
     *
     * @return array
     */
    public function getParsedBody()
    {
        return $this->requestBody['body'];
    }

    /**
     * Gets http payload security credentials.
     *
     * @return array
     */
    public function getParsedCredentials()
    {
        return $this->requestBody['credentials'];
    }
}
