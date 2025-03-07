<?php

/**
 * OAuth class for Azure Active Directory.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-09-09
 */

namespace phpMyFAQ\Auth\Azure;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Session;
use stdClass;

/**
 * Class OAuth
 *
 * @package phpMyFAQ\Auth\Azure
 */
class OAuth
{
    /** @var Configuration */
    private Configuration $config;

    /** @var Session */
    private Session $session;

    /** @var stdClass|null JWT */
    private ?stdClass $token = null;

    /** @var string|null */
    private ?string $refreshToken = null;

    /** @var string|null */
    private ?string $accessToken = null;

    /**
     * Constructor.
     *
     * @param Configuration $config
     * @param Session       $session
     */
    public function __construct(Configuration $config, Session $session)
    {
        $this->config = $config;
        $this->session = $session;
    }

    /**
     * Returns the error message.
     *
     * @param string $message
     * @return string
     */
    public function errorMessage(string $message): string
    {
        return $message;
    }

    /**
     * Returns the Authorization Code from Azure AD.
     *
     * @param string $code
     * @return stdClass
     * @throws GuzzleException
     */
    public function getOAuthToken(string $code): stdClass
    {
        $client = new Client([
            'base_uri' => 'https://login.microsoftonline.com/' . AAD_OAUTH_TENANTID . '/oauth2/v2.0/',
        ]);

        $response = $client->request('POST', 'token', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => AAD_OAUTH_CLIENTID,
                'redirect_uri' => $this->config->getDefaultUrl() . 'services/azure/callback.php',
                'code' => $code,
                'code_verifier' => $this->session->get(Session::PMF_AZURE_AD_OAUTH_VERIFIER),
                'client_secret' => AAD_OAUTH_SECRET
            ]
        ]);

        return json_decode($response->getBody());
    }

    /**
     * @throws GuzzleException
     */
    public function refreshToken()
    {
        $client = new Client([
            'base_uri' => 'https://login.microsoftonline.com/' . AAD_OAUTH_TENANTID . '/oauth2/v2.0/',
        ]);

        $response = $client->request('POST', 'token', [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->getRefreshToken(),
                'client_id' => AAD_OAUTH_CLIENTID,
                'scope' => AAD_OAUTH_SCOPE
            ]
        ]);

        return json_decode($response->getBody());
    }

    /**
     * @return stdClass
     */
    public function getToken(): stdClass
    {
        return $this->token;
    }

    /**
     * @param stdClass $token
     * @return OAuth
     */
    public function setToken(stdClass $token): OAuth
    {
        $idToken = base64_decode(explode('.', $token->id_token)[1]);
        $this->token = json_decode($idToken);
        $this->session->set(Session::PMF_AZURE_AD_JWT, json_encode($this->token));
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    /**
     * @param string|null $refreshToken
     * @return OAuth
     */
    public function setRefreshToken(?string $refreshToken): OAuth
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    /**
     * @param string|null $accessToken
     * @return OAuth
     */
    public function setAccessToken(?string $accessToken): OAuth
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->token->name;
    }

    /**
     * @return string
     */
    public function getMail(): string
    {
        return $this->token->preferred_username;
    }
}
