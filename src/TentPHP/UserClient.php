<?php

namespace TentPHP;

use Guzzle\Http\Client as HttpClient;

/**
 * Client for user authorized operations
 */
class UserClient
{
    private $httpClient;
    private $serverUrl;
    private $user;

    public function __construct(HttpClient $client, $serverUrl, User $user = null)
    {
        $this->httpClient = $client;
        $this->serverUrl  = $serverUrl;
        $this->user       = $user;
    }

    public function getProfile()
    {
        return $this->request('GET', '/profile');
    }

    public function putProfileType($type, array $data)
    {
        return $this->request('PUT', '/profile/' . urlencode($type), $data);
    }

    /**
     * Follow an entity, optionally inside some groups
     *
     * @param string $entityUrl
     * @param array $groups Ids of groups
     * @return array
     */
    public function follow($entityUrl, array $groups = array())
    {
        return $this->request('POST', '/followings', array_filter(array(
            'entity' => $entityUrl,
            'groups' => $groups
        )));
    }

    /**
     * Update the Following groups of a follower
     *
     * @param string $remoteId
     * @param array $groups
     * @return array
     */
    public function updateFollowing($remoteId, array $groups)
    {
        return $this->request('PUT', '/followings/' . $id, array('groups' => $groups));
    }

    public function getFollowings()
    {
        return $this->request('GET', '/followings');
    }

    public function getFollowing($remoteId)
    {
        return $this->request('GET', '/followings/' . $remoteId);
    }

    /**
     * Get the count of followings
     *
     * @return int
     */
    public function getFollowingCount()
    {
        return $this->request('GET', '/followings/count');
    }

    public function unfollow($remoteId)
    {
        return $this->request('DELETE', '/followings/' . $remoteId);
    }

    public function getFollowers()
    {
        return $this->request('GET', '/followers');
    }

    public function getFollower($remoteId)
    {
        return $this->request('GET', '/followers/' . $remoteId);
    }

    /**
     * Get the count of followers
     *
     * @return int
     */
    public function getFollowerCount()
    {
        return $this->request('GET', '/followers/count');
    }

    public function blockFollower($remoteId)
    {
        return $this->request('DELETE', '/followers/' . $remoteId);
    }

    public function createPost(Post $post)
    {
        return $this->request('POST', '/posts', array_filter(array(
            'type'         => $post->getType(),
            'published_at' => $post->getPublishedAt() ?: time(),
            'permissions'  => $post->getPermissions(),
            'licenses'     => $post->getLicenses(),
            'content'      => $post->getContent(),
            'mentions'     => $post->getMentions(),
        )));
    }

    /**
     * Delete a post from the tent server
     *
     * @param string|Post $post The id or a post instance
     * @return array
     */
    public function deletePost($post)
    {
        if ($post instanceof Post) {
            $post = $post->getId();
        }

        if (!$post) {
            throw new \InvalidArgumentException("No post id given");
        }

        return $this->request('DELETE', '/posts/' . $post);
    }

    /**
     * Get the posts matching the criteria
     *
     * @param PostCriteria $criteria
     * @return int
     */
    public function getPosts(PostCriteria $criteria = null)
    {
        $query = $criteria ? http_build_query($criteria->toArray()) : '';
        return $this->request('GET', '/posts?' . $query);
    }

    /**
     * Get the total count of posts for a given set of criteria
     *
     * @param PostCriteria $criteria
     * @return int
     */
    public function getPostCount(PostCriteria $criteria = null)
    {
        $query = $criteria ? http_build_query($criteria->toArray()) : '';
        return $this->request('GET', '/posts/count?' . $query);
    }

    public function getPost($id)
    {
        return $this->request('GET', '/posts/' . $id);
    }

    public function getPostAttachment($id, $filename)
    {
        throw new \RuntimeException("Not yet implemented.");
    }

    /**
     * Return all groups of the current user.
     *
     * @return Group[]
     */
    public function getGroups()
    {
        $result = $this->request('GET', '/groups');
        $groups = array();

        foreach ($result as $row) {
            $groups[] = new Group(
                $row['id'],
                $row['name'],
                $row['created_at'] ? new \DateTime('@' . $row['created_at']) : null,
                $row['updated_at'] ? new \DateTime('@' . $row['updated_at']) : null
            );
        }

        return $groups;
    }

    /**
     * Create a new group
     *
     * @param string $name
     * @return
     */
    public function createGroup($name)
    {
        return $this->request('POST', '/groups', array('name' => $name));
    }

    /**
     * Get a group
     *
     * @param string $id
     */
    public function getGroup($id)
    {
        if ($id == 'count') {
            throw new \RuntimeException("Invalid access");
        }

        $row = $this->request('GET', '/groups/' . $id);
        return new Group(
            $row['id'],
            $row['name'],
            $row['created_at'] ? new \DateTime('@' . $row['created_at']) : null,
            $row['updated_at'] ? new \DateTime('@' . $row['updated_at']) : null
        );
    }

    /**
     * Delete group with given id
     *
     * @param string $id
     */
    public function deleteGroup($id)
    {
        if ($id == 'count') {
            throw new \RuntimeException("Invalid access");
        }

        return $this->request('DELETE', '/groups/' . $id);
    }

    /**
     * Update a group name
     *
     * @param string $id
     * @param string $name
     * @return
     */
    public function updateGroup($id, $name)
    {
        if ($id == 'count') {
            throw new \RuntimeException("Invalid access");
        }

        return $this->request('PUT', '/groups/' . $id, array('name' => $name));
    }

    protected function request($method, $url, $body = null)
    {
        $payload = $body ? json_encode($body) : null;
        $url     = $this->serverUrl . $url;

        $headers = array(
            'Content-Type'  => 'application/vnd.tent.v0+json',
            'Accept'        => 'application/vnd.tent.v0+json',
        );

        if ($this->user) {
            $headers['Authorization'] = HmacHelper::generateAuthorizationHeader(
                $method,
                $url,
                $this->user->macKey,
                $this->user->macSecret
            );
        }

        $response = $this->httpClient->createRequest($method, $url, $headers, $payload)->send();

        return json_decode($response->getBody(), true);
    }

    /**
     * Validate given the url of the current request that a MAC authorization
     * header was passed that matches to the current entity authorization details.
     *
     * @throws RuntimeException
     */
    public function validateMacAuthorizationHeader($url, $method = 'POST')
    {
        HmacHelper::validateMacAuthorizationHeader($this->user, $url, $method);
    }
}

