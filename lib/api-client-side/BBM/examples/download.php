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

// NEW INSTANCE OF THE DOWNLOAD. EVERY NEW DOWNLOAD MUST BE A NEW INSTANCE.
$download = new BBM\Download('YOUR_APY_KEY', 'YOUR_API_SECRET');

/*
 * Server environment that you want to use: sandbox or production.
 * Default: 'sandbox'
 */
$download->environment = 'sandbox';

/*
 * Verbose (true|false), enable this option and a full output will be shown.
 * Default: false
 */

// UNCOMMENT THIS CODE TO ACTIVATE THE VERBOSE MODE
//$download->verbose(true);

$data = [
    'ebook_id' => $EBOOKID, // YOU MUST 1 TO TEST, ANYONE MORE WILL BE COUNT AS AN ACTUAL DOWNLOAD.
    'transaction_time' => time(), // IT`S HIGHLY RECOMMENDED THAT YOU CANNOT CHANGE THE TRANSACTION TIME.
    'transaction_key' => $TIMESTAMP // YOU MUST SET THIS TO TEST, ANY OTHER ACTIVE TRANSACTION KEY WILL BE COUNT AS AN ACTUAL DOWNLOAD.
];

/*
 * IF YOU WANT TO HANDLE THE ERRORS, USE TRY/CATCH AS BELOW:
 */

try
{
    $download->validate($data); // VALIDATE THE DOWNLOAD
    $download->download(); // TRY TO DOWNLOAD THE FILE
}
catch(\BBM\Server\Exception $e)
{
    // HERE YOU CAN HANDLE THE ERROR AS YOU WANT TO.
    // EXAMPLE:
    die($e->getMessage());
}

/*
 * EVERY ERROR ON THE REQUEST OR IN THE VALIDATIONS WILL THROW A EXCEPTION.
 */