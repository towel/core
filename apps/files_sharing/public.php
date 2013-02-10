<?php
$RUNTIME_NOSETUPFS = true;
// Load other apps for file previews
OC_App::loadApps();

if (isset($_GET['t'])) {
	$token = $_GET['t'];
	$linkItem = OCP\Share::getShareByToken($token);
	if (is_array($linkItem) && isset($linkItem['uid_owner'])) {
		// seems to be a valid share
		$type = $linkItem['item_type'];
		$fileSource = $linkItem['file_source'];
		$shareOwner = $linkItem['uid_owner'];
		if (OCP\User::userExists($shareOwner) && $fileSource != -1) {
			OC_Util::setupFS($shareOwner);
			$path = $linkItem['file_target'];
		} else {
			header('HTTP/1.0 404 Not Found');
			$tmpl = new OCP\Template('', '404', 'guest');
			$tmpl->printPage();
			exit();
		}
	}
} else {
	if (isset($_GET['file']) || isset($_GET['dir'])) {
		OCP\Util::writeLog('share', 'Missing token, trying fallback file/dir links', \OCP\Util::DEBUG);
		if (isset($_GET['dir'])) {
			$type = 'folder';
			$path = $_GET['dir'];
			if (strlen($path) > 1 and substr($path, -1, 1) === '/') {
				$path = substr($path, 0, -1);
			}
			$baseDir = $path;
			$dir = $baseDir;
		} else {
			$type = 'file';
			$path = $_GET['file'];
			if (strlen($path) > 1 and substr($path, -1, 1) === '/') {
				$path = substr($path, 0, -1);
			}
		}
		$shareOwner = substr($path, 1, strpos($path, '/', 1) - 1);

		if (OCP\User::userExists($shareOwner)) {
			OC_Util::setupFS($shareOwner);
			$fileSource = getId($path);
			if ($fileSource != -1) {
				$linkItem = OCP\Share::getItemSharedWithByLink($type, $fileSource, $shareOwner);
				$pathAndUser['path'] = $path;
				$path_parts = explode('/', $path, 5);
				$pathAndUser['user'] = $path_parts[1];
				$fileOwner = $path_parts[1];
			}
		}
	}
}

if ($linkItem) {
	if (!isset($linkItem['item_type'])) {
		OCP\Util::writeLog('share', 'No item type set for share id: ' . $linkItem['id'], \OCP\Util::ERROR);
		header('HTTP/1.0 404 Not Found');
		$tmpl = new OCP\Template('', '404', 'guest');
		$tmpl->printPage();
		exit();
	}
	if (isset($linkItem['share_with'])) {
		// Authenticate share_with
		$url = OCP\Util::linkToPublic('files') . '&t=' . $token;
		if (isset($_GET['file'])) {
			$url .= '&file=' . urlencode($_GET['file']);
		} else {
			if (isset($_GET['dir'])) {
				$url .= '&dir=' . urlencode($_GET['dir']);
			}
		}
		if (isset($_POST['password'])) {
			$password = $_POST['password'];
			if ($linkItem['share_type'] == OCP\Share::SHARE_TYPE_LINK) {
				// Check Password
				$forcePortable = (CRYPT_BLOWFISH != 1);
				$hasher = new PasswordHash(8, $forcePortable);
				if (!($hasher->CheckPassword($password.OC_Config::getValue('passwordsalt', ''),
											 $linkItem['share_with']))) {
					$tmpl = new OCP\Template('files_sharing', 'authenticate', 'guest');
					$tmpl->assign('URL', $url);
					$tmpl->assign('error', true);
					$tmpl->printPage();
					exit();
				} else {
					// Save item id in session for future requests
					$_SESSION['public_link_authenticated'] = $linkItem['id'];
				}
			} else {
				OCP\Util::writeLog('share', 'Unknown share type '.$linkItem['share_type']
										   .' for share id '.$linkItem['id'], \OCP\Util::ERROR);
				header('HTTP/1.0 404 Not Found');
				$tmpl = new OCP\Template('', '404', 'guest');
				$tmpl->printPage();
				exit();
			}

		} else {
			// Check if item id is set in session
			if (!isset($_SESSION['public_link_authenticated'])
				|| $_SESSION['public_link_authenticated'] !== $linkItem['id']
			) {
				// Prompt for password
				$tmpl = new OCP\Template('files_sharing', 'authenticate', 'guest');
				$tmpl->assign('URL', $url);
				$tmpl->printPage();
				exit();
			}
		}
	}
	$basePath = $path;
	if (isset($_GET['path']) && \OC\Files\Filesystem::isReadable($_GET['path'])) {
		$getPath = \OC\Files\Filesystem::normalizePath($_GET['path']);
		$path .= $getPath;
	} else {
		$getPath = '';
	}
	$dir = dirname($path);
	$file = basename($path);
	// Download the file
	if (isset($_GET['download'])) {
		if (isset($_GET['path']) && $_GET['path'] !== '') {
			if (isset($_GET['files'])) { // download selected files
				OC_Files::get($path, $_GET['files'], $_SERVER['REQUEST_METHOD'] == 'HEAD' ? true : false);
			} else {
				if (isset($_GET['path']) && $_GET['path'] != '') { // download a file from a shared directory
					OC_Files::get($dir, $file, $_SERVER['REQUEST_METHOD'] == 'HEAD' ? true : false);
				} else { // download the whole shared directory
					OC_Files::get($dir, $file, $_SERVER['REQUEST_METHOD'] == 'HEAD' ? true : false);
				}
			}
		} else { // download a single shared file
			OC_Files::get($dir, $file, $_SERVER['REQUEST_METHOD'] == 'HEAD' ? true : false);
		}

	} else {
		OCP\Util::addStyle('files_sharing', 'public');
		OCP\Util::addScript('files_sharing', 'public');
		OCP\Util::addScript('files', 'fileactions');
		$tmpl = new OCP\Template('files_sharing', 'public', 'base');
		$tmpl->assign('uidOwner', $shareOwner);
		$tmpl->assign('displayName', \OCP\User::getDisplayName($shareOwner));
		$tmpl->assign('dir', $dir);
		$tmpl->assign('filename', $file);
		$tmpl->assign('mimetype', \OC\Files\Filesystem::getMimeType($path));
		$urlLinkIdentifiers= (isset($token)?'&t='.$token:'')
							.(isset($_GET['dir'])?'&dir='.$_GET['dir']:'')
							.(isset($_GET['file'])?'&file='.$_GET['file']:'');
		// Show file list
		if (\OC\Files\Filesystem::is_dir($path)) {
			OCP\Util::addStyle('files', 'files');
			OCP\Util::addScript('files', 'files');
			OCP\Util::addScript('files', 'filelist');
			OCP\Util::addscript('files', 'keyboardshortcuts');
			$files = array();
			$rootLength = strlen($basePath) + 1;
			foreach (\OC\Files\Filesystem::getDirectoryContent($path) as $i) {
				$i['date'] = OCP\Util::formatDate($i['mtime']);
				if ($i['type'] == 'file') {
					$fileinfo = pathinfo($i['name']);
					$i['basename'] = $fileinfo['filename'];
					if (!empty($fileinfo['extension'])) {
						$i['extension'] = '.' . $fileinfo['extension'];
					} else {
						$i['extension'] = '';
					}
				}
				$i['directory'] = $dir;
				$files[] = $i;
			}
			// Make breadcrumb
			$breadcrumb = array();
			$pathtohere = '';
			foreach (explode('/', $dir) as $i) {
				if ($i != '') {
					$pathtohere .= '/' . $i;
					$breadcrumb[] = array('dir' => $pathtohere, 'name' => $i);
				}
			}
			$list = new OCP\Template('files', 'part.list', '');
			$list->assign('files', $files, false);
			$list->assign('disableSharing', true);
			$list->assign('baseURL', OCP\Util::linkToPublic('files') . $urlLinkIdentifiers . '&path=', false);
			$list->assign('downloadURL', OCP\Util::linkToPublic('files') . $urlLinkIdentifiers . '&download&path=', false);
			$breadcrumbNav = new OCP\Template('files', 'part.breadcrumb', '');
			$breadcrumbNav->assign('breadcrumb', $breadcrumb, false);
			$breadcrumbNav->assign('baseURL', OCP\Util::linkToPublic('files') . $urlLinkIdentifiers . '&path=', false);
			$folder = new OCP\Template('files', 'index', '');
			$folder->assign('fileList', $list->fetchPage(), false);
			$folder->assign('breadcrumb', $breadcrumbNav->fetchPage(), false);
			$folder->assign('dir', basename($dir));
			$folder->assign('isCreatable', false);
			$folder->assign('permissions', 0);
			$folder->assign('files', $files);
			$folder->assign('uploadMaxFilesize', 0);
			$folder->assign('uploadMaxHumanFilesize', 0);
			$folder->assign('allowZipDownload', intval(OCP\Config::getSystemValue('allowZipDownload', true)));
			$folder->assign('usedSpacePercent', 0);
			$tmpl->assign('folder', $folder->fetchPage(), false);
			$tmpl->assign('allowZipDownload', intval(OCP\Config::getSystemValue('allowZipDownload', true)));
			$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files') . $urlLinkIdentifiers . '&download&path=' . urlencode($getPath));
		} else {
			// Show file preview if viewer is available
			if ($type == 'file') {
				$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files') . $urlLinkIdentifiers . '&download');
			} else {
				$tmpl->assign('downloadURL', OCP\Util::linkToPublic('files')
										.$urlLinkIdentifiers.'&download&path='.urlencode($getPath));
			}
		}
		$tmpl->printPage();
	}
	exit();
} else {
	OCP\Util::writeLog('share', 'could not resolve linkItem', \OCP\Util::DEBUG);
}
header('HTTP/1.0 404 Not Found');
$tmpl = new OCP\Template('', '404', 'guest');
$tmpl->printPage();

