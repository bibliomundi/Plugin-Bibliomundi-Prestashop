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

// NEW INSTANCE OF THE PURCHASE. YOU CAN SEND MORE THEN ONE ITEM ON THE PURCHASE.
$purchase = new BBM\Purchase('YOUR_API_KEY', 'YOUR_API_SECRET');

/*
 * Server environment that you want to use: sandbox or production.
 * Default: 'sandbox'
 */
$purchase->environment = 'sandbox';

/*
 * Verbose (true|false), enable this option and a full output will be shown.
 * Default: false
 */

// UNCOMMENT THIS CODE TO ACTIVATE THE VERBOSE MODE
// $purchase->verbose(true);

// CUSTOMER DATA ARRAY
$customer = [
    'customerIdentificationNumber' => 1, // INT, YOUR STORE CUSTOMER ID
    'customerFullname' => 'CUSTOMER NAME', // STRING, CUSTOMER FULL NAME
    'customerEmail' => 'customer@email.com', // STRING, CUSTOMER EMAIL
    'customerGender' => 'm', // ENUM, CUSTOMER GENDER, USE m OR f (LOWERCASE!! male or female)
    'customerBirthday' => '1991/11/03', // STRING, CUSTOMER BIRTH DATE, USE Y/m/d (XXXX/XX/XX)
    'customerCountry' => 'BR', // STRING, 2 CHAR STRING THAT INDICATE THE CUSTOMER COUNTRY (BR, US, ES, etc)
    'customerZipcode' => '31231223', // STRING, POSTAL CODE, ONLY NUMBERS
    'customerState' => 'RJ' // STRING, 2 CHAR STRING THAT INDICATE THE CUSTOMER STATE (RJ, SP, NY, etc)
];

// SET THE CUSTOMER OF THIS SALE, BASED ON THE PREVIOUS CUSTOMER
$purchase->setCustomer($customer);

// ADD A NEW EBOOK, EVERY NEW EBOOK MUST BE ADDED AGAIN.
// IF YOU WANT TO ADD A SINGLE BOOK, YOU CAN USE.
// addItem(EBOOK_ID, PRICE, CURRENCY);
$purchase->addItem(3, 1, 'BRL');

// OTHERWISE, IF YOU WANT A MULTIPLE SALE, YOU CAN SET LIKE THIS
//$purchasedEbooks = [
//    1 => ['id' => 48, 'price' => 50.00, 'currency' => 'BRL']
//];

// FOREACH PURCHASED EBOOKS, ADD A NEW EBOOK.
//foreach($purchasedEbooks as $ebook)
//{
//    $purchase->addItem($ebook['id'], $ebook['price'], $ebook['currency']);
//}

// CHECK IF YOU CAN COMPLETE THIS PURCHASE BEFORE YOU PROCEED TO CHECKOUT.
try
{
    $purchase->validate();

    // IF THE PURCHASE IS VALID, YOU CAN PROCEED TO YOUR CHECKOUT. DO NOT EXECUTE
    // THE SELL BEFORE CHECK IF YOU REALLY CAN DO THIS. HAVE SOME CONDITIONS THAT
    // YOUR STORE CANNOT SELL THE EBOOK, IS BETTER TO YOU TO CHECK IT BEFORE.

    /*
     * HERE YOU CAN SET ANYTHING YOU NEED TO CONTINUE YOUR CHECKOUT METHOD.
     */

    // ONCE THE CHECKOUT IS COMPLETE, WE NEED TO KNOW THIS, ONLY THING YOU NEED
    // TO DO IS SEND TO US THE TRANSACTION KEY AND TRANSACTION TIME THAT YOU
    // ARE ACTUALLY USING ON YOUR DATABASE.
    //
    // TRANSACTION_KEY = YOUR PURCHASE ID, SO WE CAN LINK YOUR PURCHASE TO OUR SALE.
    // TRANSACTION_TIME = THE MOMENT THAT YOU RECORD ON THE DATABASE YOUR PURCHASE.
    // NOTE: CAN'T BE MORE THE ONE HOUR OF DIFFERENCE BETWEEN THE PURCHASE AND THE REQUEST.
    //
    // THIS FIELDS ARE REQUIRED TO EXECUTE THE TRANSACTION SUCCESSFULLY
    // IN THIS MOMENT, NO EXCEPTIONS OR ERRORS WILL BE RETURNED, AND WE WILL SAVE
    // WHATEVER YOU SEND TO US, BUT, STAY ON THE PATTERNS AND FOLLOW THE GUIDE
    // AND WE WILL NOT HAVE FURTHER PROBLEMS.

    echo $purchase->checkout('TRANSACTION_KEY', time());
}
catch(\BBM\Server\Exception $e)
{
    echo $e;
}