<?php

namespace GuiBranco\Pancake;

/**
 * GitHubClient is a client for interacting with the GitHub API using the Pancake Request class.
 * 
 * It supports authentication using either a user token or app credentials (app_id and app_secret).
 * Depending on the type of credentials provided, the client dynamically configures the request headers
 * or URL parameters.
 * 
 * The class also offers a configurable error management strategy that allows logging errors via the
 * Pancake Logger or throwing exceptions for unhandled errors. Specific HTTP status codes can be
 * ignored as per configuration.
 *
 * Example API endpoints implemented include getting repository details, creating issues, and retrieving
 * user information.
 */
class GitHubClient
{
    private Request $request;
    private ?Logger $logger;
    private array $credentials;
    private array $ignoredStatusCodes;
    private bool $throwExceptions;

    /**
     * Constructor.
     * 
     * @param array $credentials Array of credentials. Use 'user_token' for personal token authentication,
     *                           or 'app_id' and 'app_secret' for app authentication.
     * @param Logger|null $logger Optional Logger instance for error logging.
     * @param bool $throwExceptions Whether to throw exceptions on API errors.
     * @param array $ignoredStatusCodes HTTP status codes to ignore when handling errors.
     */
    public function __construct(array $credentials = [], ?Logger $logger = null, bool $throwExceptions = false, array $ignoredStatusCodes = [])
    {
        $this->request = new Request();
        $this->logger = $logger;
        $this->credentials = $credentials;
        $this->throwExceptions = $throwExceptions;
        $this->ignoredStatusCodes = $ignoredStatusCodes;
    }

    /**
     * Prepares the authentication headers for API requests.
     * 
     * @return array An array of authentication headers.
     */
    private function getAuthHeaders(): array
    {
        $headers = [];
        if (isset($this->credentials['user_token'])) {
            $headers[] = "Authorization: token " . $this->credentials['user_token'];
        }
        return $headers;
    }

    /**
     * Appends app credentials as query parameters if provided.
     * 
     * @param string $url The original URL.
     * @return string The URL with appended app credentials if available.
     */
    private function appendAppCredentials(string $url): string
    {
        if (isset($this->credentials['app_id']) && isset($this->credentials['app_secret'])) {
            $separator = (strpos($url, '?') === false) ? '?' : '&';
            $url .= $separator . "client_id=" . urlencode($this->credentials['app_id'])
                      . "&client_secret=" . urlencode($this->credentials['app_secret']);
        }
        return $url;
    }

    /**
     * Handles the API response by logging or throwing errors as configured.
     * 
     * @param Response $response The API response object.
     * @return Response The processed response.
     * @throws RequestException
     */
    private function handleApiResponse(Response $response): Response
    {
        $status = $response->getStatusCode();
        if (!$response->isSuccessStatusCode() && !in_array($status, $this->ignoredStatusCodes, true)) {
            if ($this->logger !== null) {
                $this->logger->log("GitHub API error", (object)[
                    "url" => $response->getUrl(),
                    "status" => $status,
                    "message" => $response->getMessage()
                ]);
            }
            if ($this->throwExceptions) {
                throw new RequestException("GitHub API error: " . $response->getMessage(), $status);
            }
        }
        return $response;
    }

    /**
     * Retrieves repository details from GitHub.
     * 
     * @param string $owner The repository owner's username.
     * @param string $repo The repository name.
     * @return Response The API response.
     */
    public function getRepository(string $owner, string $repo): Response
    {
        $url = "https://api.github.com/repos/$owner/$repo";
        $url = $this->appendAppCredentials($url);
        $headers = array_merge($this->getAuthHeaders(), ["Accept: application/vnd.github.v3+json"]);
        $response = $this->request->get($url, $headers);
        return $this->handleApiResponse($response);
    }

    /**
     * Creates an issue in a GitHub repository.
     * 
     * @param string $owner The repository owner's username.
     * @param string $repo The repository name.
     * @param string $title The title of the issue.
     * @param string $body The body content of the issue.
     * @param array $additionalFields Additional fields to include in the issue payload.
     * @return Response The API response.
     */
    public function createIssue(string $owner, string $repo, string $title, string $body, array $additionalFields = []): Response
    {
        $url = "https://api.github.com/repos/$owner/$repo/issues";
        $url = $this->appendAppCredentials($url);
        $headers = array_merge($this->getAuthHeaders(), [
            "Accept: application/vnd.github.v3+json",
            "Content-Type: application/json"
        ]);
        $payloadArr = array_merge(["title" => $title, "body" => $body], $additionalFields);
        $payload = json_encode($payloadArr);
        $response = $this->request->post($url, $headers, $payload);
        return $this->handleApiResponse($response);
    }

    /**
     * Retrieves user details from GitHub.
     * 
     * @param string $username The GitHub username.
     * @return Response The API response.
     */
    public function getUser(string $username): Response
    {
        $url = "https://api.github.com/users/$username";
        $url = $this->appendAppCredentials($url);
        $headers = array_merge($this->getAuthHeaders(), ["Accept: application/vnd.github.v3+json"]);
        $response = $this->request->get($url, $headers);
        return $this->handleApiResponse($response);
    }
}
