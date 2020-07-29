<?php

namespace AppBundle\Rest;

use AppBundle\Document\Content;
use AppBundle\Document\Content as FSContent;
use AppBundle\Exception\RestException;
use AppBundle\Services\ImagePayloadConverter;
use AppBundle\Services\RestHelper;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry as MongoEM;

/**
 * Class RestContentRequest.
 *
 * TODO: Convert to a service.
 */
class RestContentRequest extends RestBaseRequest
{
    const STATUS_ALL = '-1';

    const STATUS_PUBLISHED = '1';

    const STATUS_UNPUBLISHED = '0';

    /**
     * Image payload converter service.
     *
     * @var \AppBundle\Services\ImagePayloadConverter
     */
    protected $imagePayloadConverter;

    /**
     * RestContentRequest constructor.
     *
     * @param MongoEM $em
     *   Mongo registry.
     * @param \AppBundle\Services\ImagePayloadConverter $imagePayloadConverter
     *   Image payload converter service.
     */
    public function __construct(MongoEM $em, ImagePayloadConverter $imagePayloadConverter)
    {
        parent::__construct($em);

        $this->primaryIdentifier = 'nid';
        $this->requiredFields = [
            $this->primaryIdentifier,
            'agency',
        ];

        $this->imagePayloadConverter = $imagePayloadConverter;
    }

    /**
     * {@inheritdoc}
     */
    protected function exists($id, $agency)
    {
        $entity = $this->get($id, $agency);

        return !is_null($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function get($id, $agency)
    {
        $criteria = [
            $this->primaryIdentifier => (int)$id,
            'agency' => $agency,
        ];

        $content = $this->em
            ->getRepository('AppBundle:Content')
            ->findOneBy($criteria);

        return $content;
    }

    /**
     * Fetches content that fulfills certain criteria.
     *
     * @param string $agency
     * @param int $node
     * @param int $amount
     * @param int $skip
     * @param string $sort
     * @param string $dir
     * @param string $type
     * @param array $vocabularies
     * @param array $terms
     * @param int $upcoming
     * @param array $libraries
     * @param string $status
     * @param bool $countOnly
     * @param string $language
     *
     * @return int|Content[]
     */
    public function fetchFiltered(
        $agency,
        $node = null,
        $amount = 10,
        $skip = 0,
        $sort = '',
        $dir = '',
        $type = null,
        array $vocabularies = null,
        array $terms = null,
        $upcoming = 0,
        array $libraries = null,
        $status = self::STATUS_PUBLISHED,
        $language = null,
        $countOnly = FALSE
    ) {
        if (!empty($node)) {
            $nids = explode(',', $node);
            return $this->fetchContent($nids, $agency, $countOnly);
        }

        $qb = $this->em
            ->getManager()
            ->createQueryBuilder(Content::class);

        if ($countOnly) {
            $qb->count();
        }
        else {
            $qb->skip($skip)->limit($amount);
        }

        $qb->field('agency')->in([(int)$agency, (string)$agency]);

        if ($type) {
            $qb->field('type')->equals($type);

            if ($type == 'ding_event' && $upcoming) {
                $qb
                    ->field('fields.field_ding_event_date.value.to')
                    ->gte(date(RestHelper::ISO8601));
            }
        }

        if ($sort && $dir) {
            $qb->sort($sort, $dir);
        }

        foreach ($vocabularies as $vocabulary) {
            $field = 'taxonomy.'.$vocabulary.'.terms';
            $qb->where(
                'function() {
                    if (!this.taxonomy || !this.taxonomy.'.$vocabulary.') {
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

        if (!empty($libraries)) {
            $qb->field('fields.og_group_ref.value')->in($libraries);
        }

        $possibleStatuses = [
            self::STATUS_ALL,
            self::STATUS_PUBLISHED,
            self::STATUS_UNPUBLISHED,
        ];
        if (self::STATUS_ALL != $status && in_array($status, $possibleStatuses)) {
            $qb->field('fields.status.value')->in([(int)$status, (string)$status]);
        }

        if ($language) {
            $qb->field('fields.language.value')->equals($language);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Searches for content that match a certain query string.
     *
     * @param string $agency
     * @param string $query
     * @param string $field
     * @param int $amount
     * @param int $skip
     * @param string $status
     * @param boolean $upcoming
     * @param boolean $countOnly
     *
     * @return int|Content[]
     */
    public function fetchSuggestions(
        $agency,
        $query,
        $field = 'fields.title.value',
        $amount = 10,
        $skip = 0,
        $status = self::STATUS_PUBLISHED,
        $upcoming = false,
        $countOnly = false
    ) {
        $qb = $this->em
            ->getManager()
            ->createQueryBuilder(Content::class);

        if ($countOnly) {
            $qb->count();
        }
        else {
            $qb->skip($skip)->limit($amount);
        }

        $qb
            ->field('agency')->in([(int)$agency, (string)$agency])
            ->field($field)->equals(new \MongoRegex('/'.$query.'/i'));

        $possibleStatuses = [
            self::STATUS_ALL,
            self::STATUS_PUBLISHED,
            self::STATUS_UNPUBLISHED,
        ];
        if (self::STATUS_ALL != $status && in_array($status, $possibleStatuses)) {
            $qb->field('fields.status.value')->in([(int)$status, (string)$status]);
        }

        if ('type' == $field && 'ding_event' == $query && $upcoming) {
            $qb
                ->field('fields.field_ding_event_date.value.to')
                ->gte(date(RestHelper::ISO8601));
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Fetches content by id.
     *
     * @param array $ids
     * @param string $agency
     * @param bool $countOnly
     *
     * @return Content[]
     */
    public function fetchContent(array $ids, $agency, $countOnly = false)
    {
        if (empty($ids)) {
            return [];
        }

        // Mongo has strict type check, and since 'nid' is stored as int
        // convert the value to int as well.
        array_walk($ids, function (&$v) {
            $v = (int)$v;
        });

        $qb = $this->em->getManager()
            ->createQueryBuilder(Content::class);

        if ($countOnly) {
            $qb->count();
        }

        $qb->field('agency')->equals($agency);
        $qb->field('nid')->in($ids);

        /** @var \Doctrine\ODM\MongoDB\Cursor $result */
        $result = $qb->getQuery()->execute();

        if ($countOnly) {
            return $result;
        }

        $nodes = [];
        if ($result->count() > 0) {
            /** @var \AppBundle\Document\Content[] $_nodes */
            $_nodes = $result->toArray();

            foreach ($ids as $id) {
                foreach ($_nodes as $k => $node) {
                    if ($node->getNid() == $id) {
                        $nodes[] = $node;
                        unset($_nodes[$k]);
                        break;
                    }
                }
            }

            unset($_nodes);
        }

        return $nodes;
    }

    /**
     * {@inheritdoc}
     */
    protected function insert()
    {
        $entity = $this->prepare(new FSContent());

        $dm = $this->em->getManager();
        $dm->persist($entity);
        $dm->flush();

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    protected function update($id, $agency)
    {
        $loadedEntity = $this->get($id, $agency);
        $updatedEntity = $this->prepare($loadedEntity);

        $dm = $this->em->getManager();
        $dm->flush();

        return $updatedEntity;
    }

    /**
     * {@inheritdoc}
     */
    protected function delete($id, $agency)
    {
        $entity = $this->get($id, $agency);

        $dm = $this->em->getManager();
        $dm->remove($entity);
        $dm->flush();

        return $entity;
    }

    /**
     * Prepares payload data before store to ensure data consistency.
     *
     * @param FSContent $content
     * @return FSContent
     */
    public function prepare(FSContent $content)
    {
        $body = $this->getParsedBody();

        $nid = !empty($body[$this->primaryIdentifier]) ? $body[$this->primaryIdentifier] : 0;
        $content->setNid($nid);

        $agency = !empty($body['agency']) ? $body['agency'] : '000000';
        $content->setAgency($agency);

        $type = !empty($body['type']) ? $body['type'] : 'undefined';
        $content->setType($type);

        $fields = !empty($body['fields']) ? $body['fields'] : [];
        $fields = $this->parseFields($fields);
        $content->setFields($fields);

        $taxonomy = !empty($body['taxonomy']) ? $body['taxonomy'] : [];
        $content->setTaxonomy($taxonomy);

        $list = !empty($body['list']) ? $body['list'] : [];
        $content->setList($list);

        return $content;
    }

    /**
     * Processes image fields by converting base64 image content into physical file.
     *
     * @param array $fields
     *
     * @return array
     */
    private function parseFields(array $fields)
    {
        foreach ($fields as $fieldName => $fieldPayload) {
            $field = &$fields[$fieldName];

            foreach ($field['attr'] as $k => $fieldAttribute) {
                // Wrong mime, skip this.
                if (!preg_match('/^image\/(jpg|jpeg|gif|png)$/', $fieldAttribute)) {
                    continue;
                }

                // No contents, nothing to do.
                if (empty($field['value'][$k])) {
                    continue;
                }

                $fileContents = $field['value'][$k];
                $field['value'][$k] = null;

                list(, $extension) = explode('/', $fieldAttribute);

                $filename = sha1($fileContents.$this->agencyId).'.'.$extension;
                $filePath = $this->agencyId.'/'.$filename;

                if ($this->imagePayloadConverter->writeImage($fileContents, $filePath)) {
                    $field['value'][$k] = 'files/'.$this->agencyId.'/original/'.$filename;
                }
            }
        }

        return $fields;
    }
}
