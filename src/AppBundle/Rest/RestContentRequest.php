<?php
/**
 * @file
 */

namespace AppBundle\Rest;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;
use Symfony\Component\Filesystem\Filesystem as FSys;

use AppBundle\Rest\RestBaseRequest;
use AppBundle\Document\Content as FSContent;

class RestContentRequest extends RestBaseRequest
{
    public function __construct(MongoEM $em)
    {
        parent::__construct($em);

        $this->primaryIdentifier = 'nid';
        $this->requiredFields = array(
            $this->primaryIdentifier,
            'agency',
        );
    }

    protected function exists($id, $agency)
    {
        $entity = $this->get($id, $agency);

        return !is_null($entity);
    }

    protected function get($id, $agency)
    {
        $criteria = array(
            $this->primaryIdentifier => (int) $id,
            'agency' => $agency,
        );

        $content = $this->em
            ->getRepository('AppBundle:Content')
            ->findOneBy($criteria);

        return $content;
    }

    public function fetchXAmount($agency, $amount = 10, $sort = 'nid', $dir = 'DESC')
    {
        $criteria = array(
            'agency' => $agency,
        );

        $order = array(
            $sort => $dir,
        );

        $content = $this->em
            ->getRepository('AppBundle:Content')
            ->findBy($criteria, $order, $amount);

        return $content;
    }

    protected function insert()
    {
        $entity = $this->prepare(new FSContent());

        $dm = $this->em->getManager();
        $dm->persist($entity);
        $dm->flush();

        return $entity;
    }

    protected function update($id, $agency)
    {
        $loadedEntity = $this->get($id, $agency);
        $updatedEntity = $this->prepare($loadedEntity);

        $dm = $this->em->getManager();
        $dm->flush();

        return $updatedEntity;
    }

    protected function delete($id, $agency)
    {
        $entity = $this->get($id, $agency);

        $dm = $this->em->getManager();
        $dm->remove($entity);
        $dm->flush();

        return $entity;
    }

    public function fetchContent($id, $agency)
    {
        $entity = $this->get($id, $agency);

        return $entity;
    }

    public function prepare(FSContent $content)
    {
        $body = $this->getParsedBody();

        $nid = !empty($body[$this->primaryIdentifier]) ? $body[$this->primaryIdentifier] : 0;
        $content->setNid($nid);

        $agency = !empty($body['agency']) ? $body['agency'] : '000000';
        $content->setAgency($agency);

        $type = !empty($body['type']) ? $body['type'] : 'undefined';
        $content->setType($type);

        $fields = !empty($body['fields']) ? $body['fields'] : array();
        $fields = $this->parseFields($fields);
        $content->setFields($fields);

        $taxonomy = !empty($body['taxonomy']) ? $body['taxonomy'] : array();
        $content->setTaxonomy($taxonomy);

        $list = !empty($body['list']) ? $body['list'] : array();
        $content->setList($list);

        return $content;
    }

    /**
     * @todo
     * Quick'n'dirty.
     */
    private function parseFields(array $fields)
    {
      foreach ($fields as $field_name => $field_value) {
        if (!empty($field_value['value']) && isset($field_value['attr']['filemime']) && preg_match('/^image\/(jpg|jpeg|gif|png)$/', $field_value['attr']['filemime'])) {
          $file_ext = explode('/', $field_value['attr']['filemime']);
          $extension = isset($file_ext[1]) ? $file_ext[1] : '';
          $file_contents = $field_value['value'];
          $fields[$field_name]['value'] = NULL;

          if (!empty($extension)) {
            $fs = new FSys();

            $dir = '../web/files/' . $this->agencyId;
            if (!$fs->exists($dir))
            {
              $fs->mkdir($dir);
            }

            $filename = md5($field_value['value'] . $this->agencyId) . '.' . $extension;
            $path = $dir . '/' . $filename;

            $fs->dumpFile($path, base64_decode($file_contents));
            if ($fs->exists($path)) {
              $fields[$field_name]['value'] = 'files/' . $this->agencyId . '/' . $filename;
            }
          }
        }
      }

      return $fields;
    }
}
