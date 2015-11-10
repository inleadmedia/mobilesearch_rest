<?php
/**
 * @file
 */

namespace AppBundle\Rest;

use AppBundle\Exception\RestException;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;


interface RestRequestValidateInterface
{
    public function handleRequest($method);
}

abstract class RestBaseRequest implements RestRequestValidateInterface
{
    protected $agencyId = NULL;
    protected $signature = NULL;
    protected $requestBody = NULL;
    protected $requiredFields = array();
    protected $em = NULL;

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
            $exceptionMessage = 'Failed parsing request body.';
        }
        elseif (!$this->isRequestValid())
        {
            $exceptionMessage = 'Failed validating request. Check your credentials (agency & key).';
        }
        elseif (empty($this->requestBody['body']))
        {
            $exceptionMessage = 'Empty request.';
        }

        if (!empty($exceptionMessage)) {
            throw new RestException($exceptionMessage);
        }

        $this->agencyId = $this->requestBody['credentials']['agencyId'];
        $this->signature = $this->requestBody['credentials']['key'];
    }

    private function isRequestValid()
    {
        $isValid = TRUE;

        $requiredFields = array(
            'agencyId',
            'key',
        );

        foreach ($requiredFields as $field)
        {
            if (empty($this->requestBody['credentials'][$field]))
            {
                $isValid = FALSE;
            }
            elseif ($field == 'agencyId' && !$this->isAgencyValid($this->requestBody['credentials'][$field]))
            {
                $isValid = FALSE;
            }
            elseif ($field == 'key' && !$this->isSignatureValid($this->requestBody['credentials']['agencyId'], $this->requestBody['credentials']['key']))
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
        return $this->requestBody;
    }
}
