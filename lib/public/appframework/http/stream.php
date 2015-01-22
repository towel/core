<?php
/**
 * @author Bernhard Posselt
 * @copyright 2015 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

trait Stream {

    /**
     * Streams the file using readfile
     */
    public function stream () {
        @readfile($this->getFilePath());
    }

    /**
     *
     * @return string the file path to the streamed file
     */
    protected abstract function getFilePath ();

}