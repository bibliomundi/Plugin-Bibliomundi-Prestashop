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

namespace BBM\Server\Config;

/**
 * Class SysConfig
 * Just a config file, nothing to do here.
 * @package BBM\Server\Config
 */
/**
 * Class SysConfig
 * @package BBM\Server\Config
 */
class SysConfig {

    /**
     * @var string
     */
    public static $BASE_CONNECT_URI = 'http://connect.bibliomundi.com/';

    /**
     * @var string
     */
    public static $BASE_CATALOG = 'ebook/';

    /**
     * @var string
     */
    public static $BASE_DOWNLOAD = 'ebook/';

    /**
     * @var string
     */
    public static $BASE_PURCHASE = 'ebook/';

    /**
     * @var string
     */
    public static $GRANT_TYPE = 'client_credentials';

    /**
     * @var array
     */
    public static $ACCEPTED_SCOPES = ['complete', 'updates'];

    /**
     * @var array
     */
    public static $ACCEPTED_FILTERS = ['drm', 'image_width', 'image_height'];
}