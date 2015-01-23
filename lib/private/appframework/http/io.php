<?php
/**
 * @author Bernhard Posselt
 * @copyright 2015 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\AppFramework\Http;

/**
 * Very thin wrapper class to make output testable
 */
class IO {

    public function setOutput($out) {
        print($out);
    }

    public function setHeader($header) {
        header($header);
    }

    public function setCookie($name, $value, $expire, $path, $domain, $secure, $httponly) {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

}