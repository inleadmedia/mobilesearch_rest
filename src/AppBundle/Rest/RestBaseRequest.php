<?php
/**
 * @file
 */

namespace AppBundle\Rest;

use AppBundle\Exception\RestException;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;

abstract class RestBaseRequest
{
    protected $agencyId = NULL;
    protected $signature = NULL;
    protected $requestBody = NULL;
    protected $requiredFields = array();
    protected $em = NULL;
    private $primaryIdentifier = '';

    public function __construct(MongoEM $em)
    {
        $this->em = $em;
    }

    public function setRequestBody($requestBody)
    {
        $this->requestBody = json_decode($requestBody, TRUE);
        $this->validateRequest();
    }

    private function validateRequest()
    {
        $exceptionMessage = '';

        if (!$this->requestBody)
        {
            $exceptionMessage = 'Failed parsing request.';
        }
        elseif (!$this->isRequestValid())
        {
            $exceptionMessage = 'Failed validating request. Check your credentials (agency & key).';
        }
        elseif (empty($this->getParsedBody()))
        {
            $exceptionMessage = 'Empty request body.';
        }

        if (!empty($exceptionMessage)) {
            throw new RestException($exceptionMessage);
        }

        $this->agencyId = $this->getParsedCredentials()['agencyId'];
        $this->signature = $this->getParsedCredentials()['key'];
    }

    private function isRequestValid()
    {
        $isValid = TRUE;

        $requiredFields = array(
            'agencyId',
            'key',
        );

        $requestCredentials = $this->getParsedCredentials();

        foreach ($requiredFields as $field)
        {
            if (empty($requestCredentials[$field]))
            {
                $isValid = FALSE;
            }
            elseif ($field == 'agencyId' && !$this->isAgencyValid($requestCredentials[$field]))
            {
                $isValid = FALSE;
            }
            elseif ($field == 'key' && !$this->isSignatureValid($requestCredentials['agencyId'], $requestCredentials['key']))
            {
                $isValid = FALSE;
            }
        }

        return $isValid;
    }

    public function isAgencyValid($agencyId)
    {
        $agency = $this->getAgencyById($agencyId);

        return !is_null($agency);
    }

    public function getAgencyById($agencyId)
    {
        $agency = $this->em
            ->getRepository('AppBundle:Agency')
            ->findOneBy(array('agencyId' => $agencyId));

        return $agency;
    }

    public function isSignatureValid($agencyId, $signature)
    {
        $agency = $this->getAgencyById($agencyId);

        if ($agency) {
            $key = $agency->getKey();
            $targetSignature = sha1($agencyId . $key);

            if ($signature == $targetSignature) {
                return TRUE;
            }
        }

        return FALSE;
    }

    public function getParsedBody()
    {
        return $this->requestBody['body'];
    }

    public function getParsedCredentials()
    {
        return $this->requestBody['credentials'];
    }
}
