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

use BBM\Server\Connect;
use BBM\Server\Exception;

/**
 * Class Download
 * Used to execute the download of the desired ebook from the Bibliomundi Server.
 * Uses cURL as method to send and recive the information, you can use the example to
 * learn how to use.
 *
 * @see https://github.com/bibliomundi/client-side-api/blob/master/lib/BBM/examples/download.php
 * @package BBM
 */
class Download extends Connect
{

    /**
     * Data that the API send to the Bibliomundi Server.
     * @var Mixed
     */
    private $data;

    /**
     * Validate the data and get the OAuth2 access_token for this request.
     * @param array $data
     *
     * @return bool
     * @throws Exception
     */
    public function validate(Array $data)
    {
        $this->data = $data;

        try
        {
            // VALIDATE THE DATA BEFORE SEND IT, ONLY TO AVOID UNNECESSARY REQUESTS.
            $this->validateData();

            // IF NO EXCEPTION IS THROWN BEFORE, THE REQUEST CAN BE SENT, SO
            // HERE WE GET THE ACCESS TOKEN FOR THIS DOWNLOAD REQUEST.
            $request = new Server\Request(Server\Config\SysConfig::$BASE_CONNECT_URI . 'token.php', $this->verbose);
            $request->authenticate(true, $this->clientId, $this->clientSecret);
            $request->create();
            $request->setPost(['grant_type' => Server\Config\SysConfig::$GRANT_TYPE, 'environment' => $this->environment]);
            $response = json_decode($request->execute());

            // SET THE ACCESS TOKEN TO THE NEXT REQUEST DATA.
            $this->data['access_token'] = $response->access_token;
            $this->data['environment'] = $this->environment;
        }
        catch(Exception $e)
        {
            throw $e;
        }

        return true;
    }

    /**
     * Validate everything inside the $data Array to check if all the information
     * that we need will be sent.
     * @throws Exception
     */
    private function validateData()
    {
        // REQUIRED DATA
        if(!isset($this->data['ebook_id'], $this->data['transaction_time'], $this->data['transaction_key']))
            throw new Exception('Data array invalid', 500);

        // TRANSACTION TIME CANNOT BE MORE THAN 1 HOUR BEFORE
        if(time() - $this->data['transaction_time'] > 3600)
            throw new Exception('Download expired', 403);

        // EBOOK ID MUST BE A NUMBER
        if(!is_int($this->data['ebook_id']))
            throw new Exception('Ebook_id must be a number', 400);

        // TRANSACTION KEY CANNOT HAVE MORE THAN 200 DIGITS
        if(strlen($this->data['transaction_key']) > 200)
            throw new Exception('Invalid transaction_key', 400);

        // SET THE CLIENT_ID TO THE REQUEST
        $this->data['client_id'] = $this->clientId;
    }

    /**
     * Execute the request to get the ebook binary string, in case of success, the file
     * will be outputed and forced to download.
     * @throws Exception
     */
    public function download()
    {
        // GENERATE THE CURL HANDLER
        $request = new Server\Request(Server\Config\SysConfig::$BASE_CONNECT_URI . Server\Config\SysConfig::$BASE_DOWNLOAD . 'get.php', $this->verbose);
        $request->authenticate(false);
        $request->create();
        $request->setPost($this->data);

        try
        {
            // SEND IT, IF OKAY, THE __TOSTRING WILL BE THE EBOOK BINARY STRING
            $request->execute();
        }
        catch(Exception $e)
        {
            throw new Exception($e->getMessage(), $e->getCode());
        }

        // SET THE HEADERS, IF YOU WANT TO PUT ANOTHER NAME TO THE FILE,
        // HERE IS THE PLACE

        if(!$this->verbose)
        {
            if(!strpos($request->__toString(), "urn:uuid:"))
            {
                header('Content-Type: application/epub+zip');
                header('Content-Disposition: attachment; filename="'.md5(time()).'.epub"');
            }
            else
            {
                header('Content-Type: application/vnd.adobe.adept+xml');
                header('Content-Disposition: attachment; filename="'.md5(time()).'.acsm"');
            }

            header("Content-Transfer-Encoding: binary");
            header('Expires: 0');
            header('Pragma: no-cache');
            header("Content-Length: ".strlen($request));

            // EXIT THE PROGRAM WITH THE BINARY REQUEST.
            $request = utf8_decode($request->__toString());
            exit($request);
        }
    }

}