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
use BBM\Server\Request;

/**
 * Class Purchase
 * Used to execute and validate new purchases in the bibliomundi, this class support
 * multi purchases and single purchase.
 * @package BBM
 */
class Purchase extends Connect
{
    /**
     * Customer that brought the ebook
     * [“customerIdentificationNumber”],
     * [“customerFullname”],
     * [“customerEmail”],
     * [“customerGender”],
     * [“customerBirthday”],
     * [“customerZipcode”],
     * [“customerCountry”], <- non required
     * [“customerState”]
     *
     * @property array
     */
    private $customer;

    /**
     * The items that must be registered.
     * @property
     */
    private $items;

    /**
     * Only a handler to be used in the post.
     * @property array
     */
    private $data = array();

    private function autenticate()
    {
        // VERIFY IF THE ACCESS TOKEN HAS BEEN ALREADY SET
        if(!isset($this->data['access_token']))
        {
            // GET THE ACCESS TOKEN ON THE OAUTH SERVER
            $request = new Server\Request(Server\Config\SysConfig::$BASE_CONNECT_URI . 'token.php', $this->verbose);
            $request->authenticate(true, $this->clientId, $this->clientSecret);
            $request->create();
            $request->setPost(['grant_type' => Server\Config\SysConfig::$GRANT_TYPE, 'environment' => $this->environment]);
            $response = json_decode($request->execute());

            // SET THE ACCESS TOKEN TO THE NEXT REQUEST DATA.
            $this->data['access_token'] = $response->access_token;
            $this->data['clientID'] = $this->clientId;
            $this->data['environment'] = $this->environment;
        }
    }

    /**
     * Validate the data and get the OAuth2 access_token for this request.
     *
     * @param $bypass boolean Set if you want to bypass
     * @return bool
     * @throws Exception
     */
    public function validate()
    {
        $this->data = $this->customer;
        $this->data['items'] = $this->items;

        try
        {
            // VALIDATE THE DATA BEFORE SEND IT, ONLY TO AVOID UNNECESSARY REQUESTS.
            $this->validateData();

            // LOGIN ON THE OAUTH SERVER
            $this->autenticate();

            // SEND THE REQUEST TO VALIDATE
            $request = new Server\Request(Server\Config\SysConfig::$BASE_CONNECT_URI . Server\Config\SysConfig::$BASE_PURCHASE . 'validate.php', $this->verbose);
            $request->authenticate(false);
            $request->create();
            $request->setPost($this->data);

            // SET THE DATA TO VALIDATE.
            $response = $request->execute();

            // VERIFY THE RESPONSE CODE
            if(!in_array($request->getHttpStatus(), [200, 201]))
                throw new Exception($response, $request->getHttpStatus());

            return $response;

        }
        catch(Exception $e)
        {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        return false;
    }

    /**
     * Validate some data, it`s like a front-end validation, all this will be re-validated by the
     * backend environment.
     * @return bool
     * @throws Exception
     */
    private function validateData()
    {
        if(!isset(
            $this->data['customerIdentificationNumber'],
            $this->data['customerFullname'],
            $this->data['customerEmail'],
            $this->data['customerGender'],
            $this->data['customerBirthday'],
            $this->data['customerZipcode'],
            $this->data['customerState']
        ))
            throw new Exception('Invalid Request, check the mandatory fields', 400);

        if(empty($this->data['items']))
            throw new Exception('No ebooks added', 400);

        return true;
    }

    /**
     * Set the active customer that is buying the ebooks.
     * @param array $data
     */
    public function setCustomer(Array $data)
    {
        $this->customer = $data;
    }

    /**
     * Add new itens to the bundle that will be saved.
     * @param $bibliomundiEbookID
     * @param $price
     * @param $currency array['BRL', 'USD', 'EUR', 'GBP']
     *
     * @throws Exception
     */
    public function addItem($bibliomundiEbookID, $price, $currency)
    {
        $this->items[] = ['bibliomundiEbookID' => $bibliomundiEbookID, 'price' => $price, 'currency' => $currency];
    }

    /**
     * Execute the checkout of the purchase, indicate to us that you have already recived the "okay"
     * from the payment gateway and already had inserted this same transaction_key to your database.
     * @param $transactionKey
     * @param $transactionTime
     *
     * @return mixed
     * @throws Exception
     */
    public function checkout($transactionKey, $transactionTime)
    {
        // SET THE DATA TO BE SENT
        $this->data = $this->customer;
        $this->data['transactionKey'] = $transactionKey;
        $this->data['saleDate'] = date('Y-m-d H:i:s', $transactionTime);
        $this->data['items'] = $this->items;

        // LOGIN ON OAUTH SERVER
        $this->autenticate();

        // SEND THE REQUEST TO THE CHECKOUT
        $request = new Server\Request(Server\Config\SysConfig::$BASE_CONNECT_URI . Server\Config\SysConfig::$BASE_PURCHASE . 'purchase.php', $this->verbose);
        $request->authenticate(false);
        $request->create();
        $request->setPost($this->data);
        return $request->execute();
    }

}
