<?php
/**
 * Created by Bibliomundi.
 * User: Victor Martins
 * Contact: victor.martins@bibliomundi.com.br
 * Site: http://bibliomundi.com.br
 *
 * The MIT License (MIT)
 * Copyright (c) 2015 bibliomundi
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace BBM;

use BBM\Server\Config\SysConfig;
use BBM\Server\Connect;
use BBM\Server\Exception;

/**
 * Class Catalog
 * Used to list the ebooks inside the Bibliomundi server.
 * @package BBM
 */
class Catalog extends Connect
{
    /**
     * Data that the API send to the Bibliomundi Server.
     * @var Mixed
     */
    private $data;

    /**
     * Scope that you want to get from the API
     * @property
     */
    private $scope;

    /**
     * @param        $clientId
     * @param        $clientSecret
     * @param string $scope
     *
     * @throws Exception
     */
    public function __construct($clientId, $clientSecret, $scope = 'complete')
    {
        if(!in_array($scope, SysConfig::$ACCEPTED_SCOPES))
            throw new Exception('Invalid Scope', 400);

        $this->scope = $scope;
        parent::__construct($clientId, $clientSecret);
    }

    /**
     * Validate the data and get the OAuth2 access_token for this request.
     *
     * @return bool
     * @throws Exception
     */
    public function validate()
    {
        $this->data = ['transaction_time' => time()];

        try
        {
            // IF NO EXCEPTION IS THROWN BEFORE, THE REQUEST CAN BE SENT, SO
            // HERE WE GET THE ACCESS TOKEN FOR THIS DOWNLOAD REQUEST.
            $request = new Server\Request(Server\Config\SysConfig::$BASE_CONNECT_URI . 'token.php', $this->verbose);
            $request->authenticate(true, $this->clientId, $this->clientSecret);
            $request->create();
            $request->setPost(['grant_type' => Server\Config\SysConfig::$GRANT_TYPE, 'environment' => $this->environment]);
            $response = json_decode($request->execute());

            if(!isset($response->access_token))
                throw new Exception('Cannot get the access token');

            // SET THE ACCESS TOKEN TO THE NEXT REQUEST DATA.
            $this->data['access_token'] = $response->access_token;
            $this->data['client_id'] = $this->clientId;
            $this->data['scope'] = $this->scope;
            $this->data['environment'] = $this->environment;
        }
        catch(Exception $e)
        {
            throw $e;
        }

        return true;
    }

    /**
     * Get the XML STRING from our server, we do not handle the XML data, only output this
     * string, YOU MUST HANDLE THIS AND INSERT INTO YOUR DATABASE, see the manual if needed.
     * @return String // ONIX XML STRING
     * @throws Exception
     */
    public function get()
    {
        // GENERATE THE CURL HANDLER
        $request = new Server\Request(Server\Config\SysConfig::$BASE_CONNECT_URI . Server\Config\SysConfig::$BASE_DOWNLOAD . 'list.php', $this->verbose);
        $request->authenticate(false);
        $request->create();
        $request->setPost($this->data);

        try
        {
            // SEND IT, IF OKAY, THE __TOSTRING WILL BE THE ONIX XML RESPONSE
            $request->execute();
        }
        catch(Exception $e)
        {
            throw new Exception($e->getMessage(), $e->getCode());
        }

        return $request->__toString();
    }

}