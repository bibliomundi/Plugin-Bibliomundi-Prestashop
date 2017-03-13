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
$catalog = new BBM\Catalog('YOUR_APY_KEY', 'YOUR_API_SECRET', 'SCOPE');

/////////////////////////////////////////////////
//                  NOTICE                     //
// SCOPE DEFAULT IS "complete"                 //
// accepted scopes : complete, updates         //
/////////////////////////////////////////////////

/*
 * ENVIRONMENT
 * Server environment that you want to use: sandbox or production.
 * Default: 'sandbox'
 */
$catalog->environment = 'sandbox';

/*
 * VERBOSE
 * Verbose (true|false), enable this option and a full output will be shown.
 * Default: false
 */

// UNCOMMENT THIS CODE TO ACTIVATE THE VERBOSE MODE
// $catalog->verbose(true);

/*
 * FILTERS
 * This function will add filters to your search, you will be able to send only
 * the filters that we expect to:
 *  - (enum) drm:
 *      "yes": Will return only DRM protected ebooks.
 *      "no": Will return only unprotected ebooks.
 *
 *  - (int) image_width: The width that you want your covers come. We recommend that you
 *                       search in your store, the maximum image size and set it here.
 *
 *  - (int) image_height: The height that you want your covers come. We recommend that you
 *                        search in your store, the maximum image size and set it here.
 *
 * IMPORTANT:
 *  WE HIGHLY RECOMMEND THAT YOU ONLY SET THE HEIGHT *OR* THE WIDTH OF THE IMAGE, TO KEEP THE ASPECT
 *  RATIO IN PROPORTION OF THE ORIGINAL COVER. SETTING BOOTH, HEIGHT AND WIDTH CAN MESS AROUND
 *  WITH THE RATIO AND DISTORT THE IMAGE.
 *
 * EXAMPLES:
 *  To get only UNPROTECTED ebooks:
 *      $catalog->filters( array('drm' => 'no') );
 *
 *  Setting the max height size (IN PIXELS):
 *      $catalog->filters( array('image_height' => 1100) );
 *
 *  This functions accepts more than one filters:
 *      $catalog->filters( array('image_height' => 1100, 'drm' => 'no') ); // THIS CODE WILL PRODUCE: ONLY UNPROTECTED EBOOKS AND WITH 1100px OF HEIGHT ON THE COVER
 */
// UNCOMMENT TO USE (USE IT CAREFULLY!):
// $catalog->filters(['image_height' => 1100]);

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



header('Content-Type: application/xml; charset=utf-8');
echo $xml;