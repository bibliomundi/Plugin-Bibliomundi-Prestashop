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
     * The filters that we will interpret.
     * @see filter() function
     * @var Mixed
     */
    private $filter;

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
     * This function will add filters to your search, you will be able to send only
     * the filters that we expect to:
     *  - (enum) drm:
     *      "yes": Will return only DRM protected ebooks.
     *      "no": Will return only unprotected ebooks.
     *
     *  - (int) image_width: The width that you want your covers came. We recommend that you
     *                       search in your store, the maximum image size and set it here.
     *
     *  - (int) image_height: The height that you want your covers came. We recommend that you
     *                        search in your store, the maximum image size and set it here.
     *
     * IMPORTANT:
     *  WE HIGHLY RECOMMEND THAT YOU ONLY SET THE HEIGHT OF THE IMAGE, TO KEEP THE RATIO
     *  IN PROPORTION OF THE ORIGINAL COVER. SETTING BOOTH, HEIGHT AND WIDTH CAN MESS AROUND
     *  WITH THE RATIO.
     *
     * @param array $filters
     * @throws Exception
     */
    public function filters(Array $filters)
    {
        if($this->verbose)
            var_dump("FILTER VALIDATION: ", $filters);

        // VALIDATE EACH FILTER VALUE
        foreach($filters as $filter => $value)
        {
            // VERIFY IF THAT SPECIFIC FILTER IS IN THE CONFIGURATIONS
            if(!in_array($filter, Server\Config\SysConfig::$ACCEPTED_FILTERS))
                throw new Exception('The applied filter is not acceptable');

            // VERIFY THE VALUES ACCEPTANCE, THIS WILL BE RE-VALIDATED IN THE BACKEND.
            switch($filter)
            {
                // IF YES, ONLY WILL RETURN DRM PROTECTED EBOOKS
                // IF FALSE, ONLY WILL RETURN UNPROTECTED EBOOKS
                case 'drm':
                    if(!in_array($value, ['yes', 'no']))
                        throw new Exception('The DRM filter must be "yes" or "no"');
                    break;

                // IT'S HIGHLY RECOMMENDED THAT YOU ONLY SET ONE OF THIS
                // TO KEEP THE SAME RATIO IN THE IMAGE.
                case 'image_width':
                case 'image_height':
                    if(!is_int($value))
                        throw new Exception('The image size must be an integer');
                    break;
            }
        }

        if($this->verbose)
            var_dump("FILTER ADDED: ", $filters);

        $this->filter = $filters;
    }

    /**
     * Get the XML STRING from our server, we do not handle the XML data, only output this
     * string, YOU MUST HANDLE THIS AND INSERT INTO YOUR DATABASE, see the manual if needed.
     *
     * @return String // ONIX XML STRING
     * @throws Exception
     */
    public function get()
    {
        // GENERATE THE CURL HANDLER
        $request = new Server\Request(Server\Config\SysConfig::$BASE_CONNECT_URI . Server\Config\SysConfig::$BASE_DOWNLOAD . 'list.php', $this->verbose);
        $request->authenticate(false);
        $request->create();

        // IF HAS FILTERS, ADD THEN TO THE POST
        if(isset($this->filter))
            $this->data['filter']['items'] = $this->filter;

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