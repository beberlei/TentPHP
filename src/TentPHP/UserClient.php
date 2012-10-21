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
    private $userAuthorization;

    public function __construct(HttpClient $client, $serverUrl, UserAuthorization $userAuthorization)
    {
        $this->httpClient        = $client;
        $this->serverUrl         = $serverUrl;
        $this->userAuthorization = $userAuthorization;
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

    public function getPosts(PostCriteria $criteria = null)
    {
        return $this->request('GET', '/posts', $criteria->toArray());
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

        $auth = HmacHelper::generateAuthorizationHeader(
            $method,
            $url,
            $this->userAuthorization->getAccessToken(),
            $this->userAuthorization->getMacKey()
        );

        $headers = array(
            'Content-Type'  => 'application/vnd.tent.v0+json',
            'Accept'        => 'application/vnd.tent.v0+json',
            'Authorization' => $auth,
        );

        $response = $this->httpClient->createRequest($method, $url, $headers, $payload)->send();

        return json_decode($response->getBody(), true);
    }
}

