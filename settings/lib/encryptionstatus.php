<?php
/**
 * @author Morris Jobke
 * @copyright 2015 Morris Jobke hey@morrisjobke.de
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Settings\Lib;

use OCP\IConfig;
use OCP\App;

/**
 * @package OC\Settings\Lib
 */
class EncryptionStatus {

	/** @var IConfig */
	private $config;

	/** @var bool contains the state of the encryption app */
	private $isAppEnabled;
	/** @var bool contains the state of the admin recovery setting */
	private $isRestoreEnabled = false;

	/**
	 * @param IConfig $config
	 * @param App $app
	 */
	public function __construct(IConfig $config, App $app) {
		$this->config = $config;

		$this->isAppEnabled = $app->isEnabled('files_encryption');
		if ($this->isAppEnabled) {
			// putting this directly in empty is possible in PHP 5.5+
			$result = $config->getAppValue('files_encryption', 'recoveryAdminEnabled', 0);
			$this->isRestoreEnabled = !empty($result);
		}
	}

	public function isEncryptionAppEnabled(){
		return $this->isAppEnabled;
	}
	public function isRestoreEnabled(){
		return $this->isRestoreEnabled;
	}
}
