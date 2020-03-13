<?php

declare(strict_types=1);

namespace Ohara\Service;

use Breeze\Repository\RepositoryInterface;
use Breeze\Traits\TextTrait;

abstract class BaseService implements ServiceInterface
{
	/**
	 * @var array
	 */
	protected $config;

	public function __construct(array $modConfig)
	{
		$this->config = $modConfig;
	}

	public function global(string $variableName)
	{
		return $GLOBALS[$variableName] ?? false;
	}

	public function setGlobal($globalName, $globalValue): void
	{
		$GLOBALS[$globalName] = $globalValue;
	}

	public function requireOnce(string $fileName, string $dir = ''): void
	{
		if (empty($fileName))
			return;

		$sourceDir = !empty($dir) ? $dir : $this->global('sourcedir');

		require_once($sourceDir . '/' . $fileName . '.php');
	}

	public function setTemplate(string $templateName): void
	{
		loadtemplate($templateName);
	}

	public function redirect(string $urlName): void
	{
		if(!empty($urlName))
			redirectexit($urlName);
	}
}
