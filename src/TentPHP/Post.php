<?php

namespace TentPHP;

class Post extends DataObject
{
    protected $data = array(
        'id'           => null,
        'entity'       => null,
        'published_at' => null,
        'received_at'  => null,
        'mentions'     => array(),
        'licenses'     => array(),
        'type'         => null,
        'content'      => '',
        'attachments'  => array(),
        'app'          => array(),
        'views'        => array(),
        'permissions'  => array(),
    );

    static public function create($type)
    {
        return new self(array('type' => $type));
    }

    public function __construct(array $data)
    {
        if (!isset($data['published_at'])) {
            $data['published_at'] = time();
        }

        $this->data = array_merge($this->data, $data);
    }

    public function addMention($entityUrl, $post = null)
    {
        $data = array('entity' => $entityUrl);

        if ($post) {
            $data['post'] = $post;
        }

        $this->data['mentions'][] = $data;
        return $this;
    }

    public function getMentions()
    {
        return $this->data['mentions'];
    }

    public function addLicense($licenseUrl)
    {
        $this->data['licenses'][] = $licenseUrl;
        return $this;
    }

    public function markPublic()
    {
        $this->data['permissions']['public'] = true;
        return $this;
    }

    public function markPrivate()
    {
        $this->data['permissions']['public'] = false;
        return $this;
    }

    public function markVisibleEntity($entityUrl)
    {
        $this->data['permissions']['entities'][$entityUrl] = true;
        return $this;
    }

    public function markVisibleGroup($groupId)
    {
        $this->data['permissions']['groups'][] = array('id' => $groupId);
        return $this;
    }

    public function getPublishedAt()
    {
        return $this->data['published_at'];
    }
}

