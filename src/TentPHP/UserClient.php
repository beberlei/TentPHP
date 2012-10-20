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
        return $this->request('POST', '/posts', array(
            'type'         => $post->getType(),
            'published_at' => $post->getPublishedAt() ?: time(),
            'permissions'  => $post->getPermissions(),
            'licenses'     => $post->getLicenses(),
            'content'      => $post->getContent(),
            'mentions'     => $post->getMentions(),
        ));
    }

    public function getPosts(PostCriteria $criteria = null)
    {
    }

    public function getPost($id)
    {
    }

    public function getPostAttachment($id, $filename)
    {
    }

    protected function request($method, $url, $body = null)
    {
        $payload = json_encode($body);
        $mac     = hash_hmac('sha256', self::base64UrlEncode($payload), $this->userAuthorization->getMacKey());

        $auth = sprintf(
            'Authorization: MAC id="%s", ts="%s", nonce="%s", mac="%s"',
            $this->userAuthorization->getAccessToken(),
            time(),
            uniqid('', true),
            $mac
        );

        $headers = array(
            'Content-Type: application/vnd.tent.v0+json',
            'Accept: application/vnd.tent.v0+json',
            $auth
        );

        $response = $this->httpClient->createRequest($method, $this->serverUrl . $url, $headers, $body)->send();

        return json_decode($response->getBody(), true);
    }

    private static function base64UrlEncode($input)
    {
        $str = strtr(base64_encode($input), '+/', '-_');
        $str = str_replace('=', '', $str);
        return $str;
    }
}

