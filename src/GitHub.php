<?php

namespace GuiBranco\Pancake;

use GuiBranco\Pancake\Constants;
use GuiBranco\Pancake\GitHubAppCredentials;
use GuiBranco\Pancake\ILogger;
use GuiBranco\Pancake\Request;
use GuiBranco\Pancake\RequestException;
use GuiBranco\Pancake\Response;

/**
 * GitHub REST API client.
 *
 * Wraps the {@see Request} class to provide a typed, error-aware interface for
 * the most common GitHub v3 API operations. Authentication is flexible: supply
 * a personal access token, an OAuth token, a pre-generated GitHub App
 * installation token, or both. When both are present the client automatically
 * picks the right credential for each request category.
 *
 * Error handling is configurable per instance:
 * - **Silent mode** (default): failed requests return `null`. If a logger is
 *   supplied the error is recorded before returning.
 * - **Throw mode** (`$throwOnError = true`): failed requests throw a
 *   {@see RequestException} carrying the HTTP status code.
 * - **Ignored codes** (`$ignoredStatusCodes`): specific HTTP (or cURL) status
 *   codes are silently swallowed and `null` is returned without logging.
 *
 * @package GuiBranco\Pancake
 *
 * @example Unauthenticated — public resources
 * ```php
 * $client = new GitHub();
 * $response = $client->getRateLimit();
 * echo $response->getBody();
 * ```
 *
 * @example User token authentication
 * ```php
 * $client = new GitHub('ghp_yourPersonalAccessToken');
 * $response = $client->getAuthenticatedUser();
 * $user = $response->getBodyAsJson();
 * echo $user->login;
 * ```
 *
 * @example GitHub App authentication
 * ```php
 * $creds  = new GitHubAppCredentials(123456, 'ghs_installationToken');
 * $client = new GitHub(null, $creds);
 * $response = $client->getRepository('owner', 'repo');
 * ```
 *
 * @example Throw on any API error
 * ```php
 * $client = new GitHub('ghp_token', null, null, true);
 * try {
 *     $response = $client->getIssue('owner', 'repo', 1);
 * } catch (RequestException $e) {
 *     echo $e->getMessage();
 * }
 * ```
 *
 * @example Ignore 404 responses
 * ```php
 * $client = new GitHub('ghp_token', null, null, false, [404]);
 * $response = $client->getIssue('owner', 'repo', 99999);
 * // $response is null when the issue does not exist, no exception thrown
 * ```
 *
 * @see https://docs.github.com/en/rest GitHub REST API documentation
 */
class GitHub
{
    /**
     * Default GitHub REST API base URL.
     */
    private const BASE_URL = 'https://api.github.com/';

    /**
     * Media type header required by the GitHub API.
     */
    private const ACCEPT_HEADER = 'Accept: application/vnd.github+json';

    /**
     * Pinned API version header to ensure stable behaviour.
     */
    private const API_VERSION_HEADER = 'X-GitHub-Api-Version: 2022-11-28';

    /**
     * HTTP client used for all requests.
     *
     * @var Request
     */
    private $request;

    /**
     * Optional logger for recording API errors.
     *
     * @var ILogger|null
     */
    private $logger;

    /**
     * Headers sent with every request (excludes the auth header).
     *
     * @var array
     */
    private $baseHeaders;

    /**
     * Optional user personal access token / OAuth token.
     *
     * @var string|null
     */
    private $userToken;

    /**
     * Optional GitHub App installation credentials.
     *
     * @var GitHubAppCredentials|null
     */
    private $appCredentials;

    /**
     * Base URL for all API requests.
     *
     * @var string
     */
    private $endpoint;

    /**
     * Whether to throw a {@see RequestException} on non-2xx responses.
     *
     * @var bool
     */
    private $throwOnError;

    /**
     * HTTP (or cURL) status codes that are silently ignored.
     *
     * @var array
     */
    private $ignoredStatusCodes;

    /**
     * Initialise the GitHub API client.
     *
     * @param string|null              $userToken          Personal access token or OAuth token for user-level requests.
     * @param GitHubAppCredentials|null $appCredentials    GitHub App installation credentials for app-level requests.
     * @param ILogger|null             $logger             Optional logger; receives error details on failed requests.
     * @param bool                     $throwOnError       When `true`, non-2xx responses throw a {@see RequestException}.
     * @param array                    $ignoredStatusCodes HTTP/cURL status codes to swallow silently (no log, no throw).
     * @param string|null              $customEndpoint     Override the default GitHub API base URL (useful for GHES).
     * @param string|null              $customUserAgent    Override the default Pancake user-agent string.
     */
    public function __construct(
        $userToken = null,
        $appCredentials = null,
        $logger = null,
        $throwOnError = false,
        $ignoredStatusCodes = [],
        $customEndpoint = null,
        $customUserAgent = null
    ) {
        $this->userToken = $userToken;
        $this->appCredentials = $appCredentials;
        $this->logger = $logger;
        $this->throwOnError = $throwOnError;
        $this->ignoredStatusCodes = $ignoredStatusCodes;
        $this->endpoint = $customEndpoint ?? self::BASE_URL;
        $this->request = new Request();

        $this->baseHeaders = [
            'User-Agent: ' . ($customUserAgent ?? Constants::USER_AGENT_VENDOR),
            self::ACCEPT_HEADER,
            self::API_VERSION_HEADER,
            Constants::CONTENT_TYPE_JSON_HEADER,
        ];
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Builds an absolute URL by appending a path to the configured endpoint.
     *
     * @param string $path API path, e.g. `repos/owner/repo`.
     * @return string
     */
    private function buildUrl($path)
    {
        return rtrim($this->endpoint, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Assembles request headers, optionally forcing app-credential auth.
     *
     * Selection logic:
     * 1. If `$preferAppAuth` is true **and** app credentials are present → use app token.
     * 2. Else if a user token is present → use user token.
     * 3. Else if app credentials are present → use app token (fallback).
     * 4. Otherwise → no `Authorization` header (unauthenticated request).
     *
     * @param bool $preferAppAuth Prefer GitHub App credentials when both are available.
     * @return array
     */
    private function getHeaders($preferAppAuth = false)
    {
        $headers = $this->baseHeaders;

        if ($preferAppAuth && $this->appCredentials !== null) {
            $headers[] = $this->appCredentials->getAuthorizationHeader();
        } elseif ($this->userToken !== null) {
            $headers[] = 'Authorization: Bearer ' . $this->userToken;
        } elseif ($this->appCredentials !== null) {
            $headers[] = $this->appCredentials->getAuthorizationHeader();
        }

        return $headers;
    }

    /**
     * Inspects a response and either returns it, returns null, or throws.
     *
     * The rules applied, in order:
     * 1. If the status code is in `$ignoredStatusCodes` → return `null` silently.
     * 2. If the response is not a 2xx success:
     *    - If `$throwOnError` → throw {@see RequestException}.
     *    - If `$logger` is set → log the error details.
     *    - Return `null`.
     * 3. Otherwise → return the response unchanged.
     *
     * @param Response $response The raw response from the HTTP client.
     * @param string   $context  Human-readable method name for error messages.
     * @return Response|null
     * @throws RequestException When `$throwOnError` is true and the request failed.
     */
    private function handleError($response, $context)
    {
        if (in_array($response->getStatusCode(), $this->ignoredStatusCodes, true)) {
            return null;
        }

        if (!$response->isSuccessStatusCode()) {
            $errorDetail = $response->getMessage() !== ''
                ? $response->getMessage()
                : ($response->getBody() ?? 'Unknown error');

            $message = sprintf(
                'GitHub API error [%d] on %s: %s',
                $response->getStatusCode(),
                $context,
                $errorDetail
            );

            if ($this->throwOnError) {
                throw new RequestException($message, $response->getStatusCode());
            }

            if ($this->logger !== null) {
                $this->logger->log(
                    'GitHub API error on ' . $context,
                    (object) [
                        'statusCode' => $response->getStatusCode(),
                        'message' => $errorDetail,
                        'url' => $response->getUrl(),
                        'body' => $response->getBody(),
                    ]
                );
            }

            return null;
        }

        return $response;
    }

    // -------------------------------------------------------------------------
    // Meta / rate-limit
    // -------------------------------------------------------------------------

    /**
     * Returns the current rate-limit status for the authenticated (or anonymous) caller.
     *
     * This endpoint never counts against the rate limit itself.
     *
     * @return Response|null `null` on error (subject to error-handling configuration).
     * @see https://docs.github.com/en/rest/rate-limit/rate-limit#get-rate-limit-status-for-the-authenticated-user
     */
    public function getRateLimit()
    {
        $response = $this->request->get($this->buildUrl('rate_limit'), $this->getHeaders());
        return $this->handleError($response, 'getRateLimit');
    }

    // -------------------------------------------------------------------------
    // Users
    // -------------------------------------------------------------------------

    /**
     * Fetches a public GitHub user profile.
     *
     * @param string $username GitHub username.
     * @return Response|null `null` on error.
     * @see https://docs.github.com/en/rest/users/users#get-a-user
     */
    public function getUser($username)
    {
        $response = $this->request->get(
            $this->buildUrl('users/' . urlencode($username)),
            $this->getHeaders()
        );
        return $this->handleError($response, 'getUser');
    }

    /**
     * Fetches the profile of the currently authenticated user.
     *
     * Requires a valid user token or installation token with `read:user` scope.
     *
     * @return Response|null `null` on error.
     * @see https://docs.github.com/en/rest/users/users#get-the-authenticated-user
     */
    public function getAuthenticatedUser()
    {
        $response = $this->request->get($this->buildUrl('user'), $this->getHeaders());
        return $this->handleError($response, 'getAuthenticatedUser');
    }

    // -------------------------------------------------------------------------
    // Repositories
    // -------------------------------------------------------------------------

    /**
     * Fetches a single repository.
     *
     * @param string $owner Repository owner (user or organisation login).
     * @param string $repo  Repository name.
     * @return Response|null `null` on error.
     * @see https://docs.github.com/en/rest/repos/repos#get-a-repository
     */
    public function getRepository($owner, $repo)
    {
        $response = $this->request->get(
            $this->buildUrl('repos/' . urlencode($owner) . '/' . urlencode($repo)),
            $this->getHeaders()
        );
        return $this->handleError($response, 'getRepository');
    }

    /**
     * Lists public repositories for a given user.
     *
     * @param string $username GitHub username.
     * @param array  $params   Optional query parameters (e.g. `['type' => 'owner', 'per_page' => 30]`).
     * @return Response|null `null` on error.
     * @see https://docs.github.com/en/rest/repos/repos#list-repositories-for-a-user
     */
    public function listUserRepositories($username, $params = [])
    {
        $url = $this->buildUrl('users/' . urlencode($username) . '/repos');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        $response = $this->request->get($url, $this->getHeaders());
        return $this->handleError($response, 'listUserRepositories');
    }

    /**
     * Lists repositories accessible to the authenticated user.
     *
     * @param array $params Optional query parameters (e.g. `['visibility' => 'private', 'per_page' => 50]`).
     * @return Response|null `null` on error.
     * @see https://docs.github.com/en/rest/repos/repos#list-repositories-for-the-authenticated-user
     */
    public function listAuthenticatedUserRepositories($params = [])
    {
        $url = $this->buildUrl('user/repos');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        $response = $this->request->get($url, $this->getHeaders());
        return $this->handleError($response, 'listAuthenticatedUserRepositories');
    }

    // -------------------------------------------------------------------------
    // Issues
    // -------------------------------------------------------------------------

    /**
     * Fetches a single issue.
     *
     * @param string $owner  Repository owner.
     * @param string $repo   Repository name.
     * @param int    $number Issue number.
     * @return Response|null `null` on error.
     * @see https://docs.github.com/en/rest/issues/issues#get-an-issue
     */
    public function getIssue($owner, $repo, $number)
    {
        $response = $this->request->get(
            $this->buildUrl('repos/' . urlencode($owner) . '/' . urlencode($repo) . '/issues/' . $number),
            $this->getHeaders()
        );
        return $this->handleError($response, 'getIssue');
    }

    /**
     * Lists issues for a repository.
     *
     * @param string $owner  Repository owner.
     * @param string $repo   Repository name.
     * @param array  $params Optional query parameters (e.g. `['state' => 'open', 'per_page' => 50]`).
     * @return Response|null `null` on error.
     * @see https://docs.github.com/en/rest/issues/issues#list-repository-issues
     */
    public function listIssues($owner, $repo, $params = [])
    {
        $url = $this->buildUrl('repos/' . urlencode($owner) . '/' . urlencode($repo) . '/issues');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        $response = $this->request->get($url, $this->getHeaders());
        return $this->handleError($response, 'listIssues');
    }

    /**
     * Creates a new issue.
     *
     * @param string $owner Repository owner.
     * @param string $repo  Repository name.
     * @param array  $data  Issue fields. Required: `title`. Optional: `body`, `labels`, `assignees`, `milestone`.
     * @return Response|null `null` on error.
     * @see https://docs.github.com/en/rest/issues/issues#create-an-issue
     */
    public function createIssue($owner, $repo, $data)
    {
        $response = $this->request->post(
            $this->buildUrl('repos/' . urlencode($owner) . '/' . urlencode($repo) . '/issues'),
            $this->getHeaders(),
            json_encode($data)
        );
        return $this->handleError($response, 'createIssue');
    }

    /**
     * Updates an existing issue.
     *
     * @param string $owner  Repository owner.
     * @param string $repo   Repository name.
     * @param int    $number Issue number.
     * @param array  $data   Fields to update (e.g. `['state' => 'closed']`).
     * @return Response|null `null` on error.
     * @see https://docs.github.com/en/rest/issues/issues#update-an-issue
     */
    public function updateIssue($owner, $repo, $number, $data)
    {
        $response = $this->request->patch(
            $this->buildUrl('repos/' . urlencode($owner) . '/' . urlencode($repo) . '/issues/' . $number),
            $this->getHeaders(),
            json_encode($data)
        );
        return $this->handleError($response, 'updateIssue');
    }

    // -------------------------------------------------------------------------
    // Pull requests
    // -------------------------------------------------------------------------

    /**
     * Fetches a single pull request.
     *
     * @param string $owner  Repository owner.
     * @param string $repo   Repository name.
     * @param int    $number Pull request number.
     * @return Response|null `null` on error.
     * @see https://docs.github.com/en/rest/pulls/pulls#get-a-pull-request
     */
    public function getPullRequest($owner, $repo, $number)
    {
        $response = $this->request->get(
            $this->buildUrl('repos/' . urlencode($owner) . '/' . urlencode($repo) . '/pulls/' . $number),
            $this->getHeaders()
        );
        return $this->handleError($response, 'getPullRequest');
    }

    /**
     * Lists pull requests for a repository.
     *
     * @param string $owner  Repository owner.
     * @param string $repo   Repository name.
     * @param array  $params Optional query parameters (e.g. `['state' => 'open', 'per_page' => 30]`).
     * @return Response|null `null` on error.
     * @see https://docs.github.com/en/rest/pulls/pulls#list-pull-requests
     */
    public function listPullRequests($owner, $repo, $params = [])
    {
        $url = $this->buildUrl('repos/' . urlencode($owner) . '/' . urlencode($repo) . '/pulls');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        $response = $this->request->get($url, $this->getHeaders());
        return $this->handleError($response, 'listPullRequests');
    }

    // -------------------------------------------------------------------------
    // Releases
    // -------------------------------------------------------------------------

    /**
     * Lists releases for a repository.
     *
     * @param string $owner  Repository owner.
     * @param string $repo   Repository name.
     * @param array  $params Optional query parameters (e.g. `['per_page' => 10]`).
     * @return Response|null `null` on error.
     * @see https://docs.github.com/en/rest/releases/releases#list-releases
     */
    public function listReleases($owner, $repo, $params = [])
    {
        $url = $this->buildUrl('repos/' . urlencode($owner) . '/' . urlencode($repo) . '/releases');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        $response = $this->request->get($url, $this->getHeaders());
        return $this->handleError($response, 'listReleases');
    }

    /**
     * Fetches the latest published release for a repository.
     *
     * Draft and pre-release releases are excluded.
     *
     * @param string $owner Repository owner.
     * @param string $repo  Repository name.
     * @return Response|null `null` on error or when no release exists.
     * @see https://docs.github.com/en/rest/releases/releases#get-the-latest-release
     */
    public function getLatestRelease($owner, $repo)
    {
        $response = $this->request->get(
            $this->buildUrl('repos/' . urlencode($owner) . '/' . urlencode($repo) . '/releases/latest'),
            $this->getHeaders()
        );
        return $this->handleError($response, 'getLatestRelease');
    }

    // -------------------------------------------------------------------------
    // Repository contents
    // -------------------------------------------------------------------------

    /**
     * Fetches the contents of a file or directory from a repository.
     *
     * @param string      $owner Repository owner.
     * @param string      $repo  Repository name.
     * @param string      $path  Path to the file or directory within the repository.
     * @param string|null $ref   Optional branch, tag, or commit SHA. Defaults to the repository's default branch.
     * @return Response|null `null` on error.
     * @see https://docs.github.com/en/rest/repos/contents#get-repository-content
     */
    public function getContents($owner, $repo, $path, $ref = null)
    {
        $url = $this->buildUrl(
            'repos/' . urlencode($owner) . '/' . urlencode($repo) . '/contents/' . ltrim($path, '/')
        );
        if ($ref !== null) {
            $url .= '?ref=' . urlencode($ref);
        }
        $response = $this->request->get($url, $this->getHeaders());
        return $this->handleError($response, 'getContents');
    }
}
