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
}

