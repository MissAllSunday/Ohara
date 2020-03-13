<?php

declare(strict_types=1);

namespace Ohara\Service;

use Ohara\Ohara;

class SettingsService extends BaseService
{
	public function getSetting(string $settingName, $fallBack = false)
	{
		$modSettings = $this->global('modSettings');

		if (empty($settingName))
			return $fallBack;

		return $this->enable($settingName) ? $modSettings[$this->config->getName() . $settingName] : $fallBack;
	}

	public function enable(string $settingName): bool
	{
		$modSettings = $this->global('modSettings');

		return !empty($modSettings[Breeze::PATTERN . $settingName]);
	}

	public function modSetting(string $settingName, $fallBack = false)
	{
		$modSettings = $this->global('modSettings');

		if (empty($settingName))
			return $fallBack;

		return !empty($modSettings[$settingName]) ? $modSettings[$settingName] : $fallBack;
	}

	public function isJson(string $string): bool
	{
		json_decode($string);

		return (JSON_ERROR_NONE === json_last_error());
	}
}
