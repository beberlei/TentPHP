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

    public function follow($entityUrl)
    {
        return $this->request('POST', '/followings', array('entity' => $entityUrl));
    }

    public function getFollowings()
    {
        return $this->request('GET', '/followings');
    }

    public function getFollowing($remoteId)
    {
        return $this->request('GET', '/followings/' . $remoteId);
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

    public function getPosts(PostCriteria $criteria = null)
    {
        return $this->request('GET', '/posts?' . http_build_query($criteria->toArray()));
    }

    public function getPost($id)
    {
        return $this->request('GET', '/posts/' . $id);
    }

    public function getPostAttachment($id, $filename)
    {
        throw new \RuntimeException("Not yet implemented.");
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
}

