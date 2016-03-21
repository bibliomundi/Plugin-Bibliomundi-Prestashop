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

// NEW INSTANCE OF THE CATALOG. EVERY NEW LIST MUST BE A NEW INSTANCE.
$catalog = new BBM\Catalog('0fde0aed1f14f224e9c5fc80cd18851f9ff463aa', 'ceea142ef2c051cfb02d2e0948520441b1312274', 'complete');

/////////////////////////////////////////////////
//                  NOTICE                     //
// SCOPE DEFAULT IS "complete"                 //
// accepted scopes : complete, updates         //
/////////////////////////////////////////////////

/*
 * Server environment that you want to use: sandbox or production.
 * Default: 'sandbox'
 */
$catalog->environment = 'sandbox';

/*
 * Verbose (true|false), enable this option and a full output will be shown.
 * Default: false
 */

// UNCOMMENT THIS CODE TO ACTIVATE THE VERBOSE MODE
// $catalog->verbose(true);

try
{
    $catalog->validate();
    $xml = $catalog->get(); // GET THE ONIX XML STRING, YOU CAN ECHO OR EXIT THIS STRING
                            // BUT IS RECOMMENDED THAT YOU USE SOME XML PARSER TO INSERT THIS
                            // INTO YOUR DATABASE.
}
catch(\BBM\Server\Exception $e)
{
    throw $e;
}



//header('Content-Type: application/xml; charset=utf-8');
echo $xml;
