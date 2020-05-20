<?php

namespace Katu\Controllers;

class Images extends \Katu\Controller
{
	public static function getVersionSrcFile($fileId, $fileSecret, $version, $name)
	{
		try {
			$app = \Katu\App::get();

			$fileClass = \Katu\App::getExtendedClass('\\App\\Models\\File', '\\Katu\\Models\\File');
			$file = $fileClass::getOneBy([
				'id' => $fileId,
				'secret' => $fileSecret,
			]);

			if (!$file) {
				throw new \Katu\Exceptions\ModelNotFoundException;
			}

			try {
				$version = \Katu\Image\Version::createFromConfig($version);
			} catch (\Katu\Exceptions\MissingConfigException $e) {
				throw new \Katu\Exceptions\NotFoundException;
			}

			try {
				$image = new \Katu\Image($file);
				$imageVersion = $image->getImageVersion($version);
				$imageVersion->getImage();
			} catch (\Exception $e) {
				throw new \Katu\Exceptions\NotFoundException;
			}

			$app->response->headers->set('Content-Type', $imageVersion->getFile()->getMime());
			$app->response->headers->set('Cache-Control', 'max-age=604800, public');
			$app->response->setBody($imageVersion->getFile()->get());

			return true;
		} catch (\Exception $e) {
			throw new \Katu\Exceptions\Exception;
		}
	}

	public static function getVersionSrcUrl($version)
	{
		try {
			$app = \Katu\App::get();

			try {
				$url = new \Katu\Types\TUrl(trim($app->request->params('url')));
			} catch (\Exception $e) {
				throw new \Katu\Exceptions\NotFoundException;
			}

			try {
				$version = \Katu\Image\Version::createFromConfig($version);
			} catch (\Katu\Exceptions\MissingConfigException $e) {
				throw new \Katu\Exceptions\NotFoundException;
			}

			try {
				$image = new \Katu\Image($url);
				$imageVersion = $image->getImageVersion($version);
				$imageVersion->getImage();
			} catch (\Exception $e) {
				throw new \Katu\Exceptions\NotFoundException;
			}

			$app->response->headers->set('Content-Type', $imageVersion->getFile()->getMime());
			$app->response->headers->set('Cache-Control', 'max-age=604800, public');
			$app->response->setBody($imageVersion->getFile()->get());

			return true;
		} catch (\Exception $e) {
			\App\Extensions\ErrorHandler::log($e);
			return false;
		}
	}
}
