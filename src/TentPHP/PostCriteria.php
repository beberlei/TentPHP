<?php

namespace TentPHP;

/**
 * Criteria object for filtering posts
 */
class PostCriteria extends DataObject
{
    protected $data = array(
        'since_id'         => null,
        'before_id'        => null,
        'since_id_entity'  => null,
        'before_id_entity' => null,
        'since_time'       => null,
        'before_time'      => null,
        'entity'           => null,
        'mentioned_entity' => null,
        'post_types'       => null,
        'limit'            => null,
    );

    public function __construct(array $data = array())
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_filter($this->data);
    }
}

