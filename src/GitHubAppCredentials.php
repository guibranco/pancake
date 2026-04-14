<?php

namespace GuiBranco\Pancake;

/**
 * Holds GitHub App authentication credentials.
 *
 * A GitHub App authenticates as an installation by presenting an installation
 * access token. Generate the token via the GitHub Apps API (using a signed JWT
 * derived from your app's private key) and pass it here together with the
 * numeric app ID so that the {@see GitHub} client can identify the caller and
 * attach the correct `Authorization` header to every request.
 *
 * @package GuiBranco\Pancake
 *
 * @example Basic usage
 * ```php
 * $creds = new GitHubAppCredentials(123456, 'ghs_installationTokenHere');
 * $client = new GitHub(null, $creds);
 * ```
 */
class GitHubAppCredentials
{
    /**
     * The numeric GitHub App ID.
     *
     * @var int
     */
    private $appId;

    /**
     * The installation access token issued for this app installation.
     *
     * @var string
     */
    private $installationToken;

    /**
     * Initialise app credentials.
     *
     * @param int    $appId             Numeric GitHub App ID (visible in the app settings page).
     * @param string $installationToken Installation access token obtained from the GitHub Apps API.
     */
    public function __construct($appId, $installationToken)
    {
        $this->appId = $appId;
        $this->installationToken = $installationToken;
    }

    /**
     * Returns the numeric GitHub App ID.
     *
     * @return int
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * Returns the installation access token.
     *
     * @return string
     */
    public function getInstallationToken()
    {
        return $this->installationToken;
    }

    /**
     * Builds the `Authorization` header value for this installation token.
     *
     * @return string A ready-to-use HTTP header string, e.g. `Authorization: Bearer ghs_xxx`.
     */
    public function getAuthorizationHeader()
    {
        return 'Authorization: Bearer ' . $this->installationToken;
    }
}
