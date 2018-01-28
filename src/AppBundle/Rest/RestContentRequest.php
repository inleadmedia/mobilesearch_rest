<?php
/**
 * @file
 */

namespace AppBundle\Rest;

use AppBundle\Document\Content;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;
use Symfony\Component\Filesystem\Filesystem as FSys;

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

    public function fetchFiltered($agency, $node = null, $amount = 10, $skip = 0, $sort = '', $dir = '', $type = null, array $vocabularies = null, array $terms = null, $upcoming = 0)
    {
        if (!empty($node)) {
            return $this->fetchContent(explode(',', $node), $agency);
        }

        $qb = $this->em
            ->getManager()
            ->createQueryBuilder(Content::class);

        $qb->field('agency')->equals($agency);

        if ($type) {
            $qb->field('type')->equals($type);

            if ($type == 'ding_event' && $upcoming) {
                $qb->field('fields.field_ding_event_date.value.to')->gte(date('Y-m-d H:i:s', time()));
            }
        }

        if ($sort && $dir) {
            $qb->sort($sort, $dir);
        }

        foreach ($vocabularies as $vocabulary) {
            $field = 'taxonomy.' . $vocabulary . '.terms';
            $qb->where(
            'function() {
                if (!this.taxonomy.'.$vocabulary.') {
                    return false;
                }

                var iterator = function(data, value) {
                    var regex = new RegExp(value, "g");

                    for (var field in data) {
                        if (field.match(regex) || (typeof data[field] === "string" && data[field].match(regex))) {
                            return true;
                        }

                        if (typeof data[field] === "object") {
                            var found = false;
                            found = iterator(data[field], value);
                            if (found) {
                                return true;
                            }
                        }
                    }

                    return false;
                }

                return iterator(this.'.$field.' || [], "^('.implode('|', $terms).')$");
            }'
            );
        }

        $qb->skip($skip)->limit($amount);

        return $qb->getQuery()->execute();
    }

    public function fetchSuggestions($agency, $query, $field = 'fields.title.value')
    {
        $content = $this->em->getRepository('AppBundle:Content')->findBy(
          array(
            $field => new \MongoRegex('/'.$query.'/i'),
            'agency' => $agency,
          )
        );

        return $content;
    }

    public function fetchContent(array $ids, $agency)
    {
        if (empty($ids)) {
            return array();
        }

        // Mongo has strict type check, and since 'nid' is stored as int
        // convert the value to int as well.
        array_walk($ids, function(&$v) {
            $v = (int)$v;
        });

        $criteria = array(
          'agency' => $agency,
          'nid' => array('$in' => $ids),
        );

        $entities = $this->em
          ->getRepository('AppBundle:Content')
          ->findBy($criteria);

        return $entities;
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
      $image_fields = array(
        'field_images',
        'field_background_image',
        'field_ding_event_title_image',
        'field_ding_event_list_image',
        'field_ding_library_title_image',
        'field_ding_library_list_image',
        'field_ding_news_title_image',
        'field_ding_news_list_image',
        'field_ding_page_title_image',
        'field_ding_page_list_image',
      );
      foreach ($fields as $field_name => &$field_value) {
        if (in_array($field_name, $image_fields)) {
          if (!is_array($field_value['value'])) {
            $field_value['value'] = array($field_value['value']);
          }

          foreach ($field_value['value'] as $k => $value) {
            if (!empty($value) && isset($field_value['attr'][$k]) && preg_match('/^image\/(jpg|jpeg|gif|png)$/', $field_value['attr'][$k])) {
              $file_ext = explode('/', $field_value['attr'][$k]);
              $extension = isset($file_ext[1]) ? $file_ext[1] : '';
              $file_contents = $field_value['value'][$k];
              $fields[$field_name]['value'][$k] = NULL;

              if (!empty($extension)) {
                $fs = new FSys();

                $dir = '../web/storage/images/' . $this->agencyId;
                if (!$fs->exists($dir))
                {
                  $fs->mkdir($dir);
                }

                $filename = sha1($value . $this->agencyId) . '.' . $extension;
                $path = $dir . '/' . $filename;

                $fs->dumpFile($path, base64_decode($file_contents));
                if ($fs->exists($path)) {
                  $field_value['value'][$k] = 'files/' . $this->agencyId . '/' . $filename;
                }
              }
            }
          }
        }
      }

      return $fields;
    }
}
