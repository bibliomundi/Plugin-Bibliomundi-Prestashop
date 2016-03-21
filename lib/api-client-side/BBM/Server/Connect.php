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

namespace BBM\Server;

/**
 * Class Connect
 * Nothing to do here for while.
 * @package BBM\Server
 */
class Connect
{
    /**
     * CLIENT ID, this string is sent to you by the Bibliomundi, and must be
     * the same in all areas of this API.
     * @property String
     */
    public $clientId;

    /**
     * CLIENT SECRET, this string is sent to you by the Bibliomundi, and must be
     * the same in all areas of this API.
     * @property String
     */
    public $clientSecret;

    /**
     * Server environment that you want to use: sandbox or production.
     * @default 'sandbox'
     * @property String
     */
    public $environment = 'sandbox';

    /**
     * Verbose, enable that option and a full log will be output.
     * @default false
     * @property boolean
     */
    public $verbose = false;

    /**
     * ClientID and ClientSecret is first validated here, if it do not fit, will be thrown
     * an exception.
     * @param $clientId
     * @param $clientSecret
     *
     * @throws Exception
     */
    public function __construct($clientId, $clientSecret)
    {
        // FIRST CHECK, WILL BE DOUBLE CHECKED IN SERVER-SIDE.
        if(strlen($clientId) > 40 || strlen($clientSecret) > 40)
            throw new Exception('Invalid Credentials', 400);

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function verbose($status = false)
    {
        $this->verbose = $status;

        if($status)
        {
            echo "<pre>";
            var_dump("VERBOSE ACTIVATED!");
        }
    }

}