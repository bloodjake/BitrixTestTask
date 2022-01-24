<?php
namespace Bitrix\Main\Filter;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class UserDataProvider extends EntityDataProvider
{
	/** @var UserSettings|null */
	protected $settings = null;

	function __construct(UserSettings $settings)
	{
		$this->settings = $settings;
	}

	public static function extranetSite()
	{
		static $result = null;

		if ($result === null)
		{
			$result = (
				Loader::includeModule('extranet')
				&& \CExtranet::isExtranetSite()
			);
		}

		return $result;
	}

	public static function getFiredAvailability()
	{
		global $USER;

		static $result = null;

		if ($result === null)
		{
			$result = (
				(
					Option::get("bitrix24", "show_fired_employees", "Y") == "Y"
					|| $USER->canDoOperation('edit_all_users')
				)
				&& !self::extranetSite()
			);
		}

		return $result;
	}

	public static function getExtranetAvailability()
	{
		static $result = null;

		if ($result === null)
		{
			$result = (
				ModuleManager::isModuleInstalled('extranet')
				&& strlen(Option::get("extranet", "extranet_site")) > 0
			);
		}

		return $result;
	}

	public static function getInvitedAvailability()
	{
		global $USER;

		static $result = null;

		if ($result === null)
		{
			$result = (
				$USER->canDoOperation('edit_all_users')
				&& (
					!ModuleManager::isModuleInstalled('extranet')
					|| strlen(Option::get("extranet", "extranet_site")) <= 0 // master hasn't been run
					|| !self::extranetSite()
				)
			);
		}

		return $result;
	}

	public static function getIntegratorAvailability()
	{
		global $USER;

		static $result = null;

		if ($result === null)
		{
			$result = (
				$USER->canDoOperation('edit_all_users')
				&& ModuleManager::isModuleInstalled('bitrix24')
				&& (
					!ModuleManager::isModuleInstalled('extranet')
					|| (
						strlen(Option::get("extranet", "extranet_site")) > 0
						&& !self::extranetSite()
					)
				)
			);
		}

		return $result;
	}

	public static function getAdminAvailability()
	{
		global $USER;

		static $result = null;

		if ($result === null)
		{
			$result = (
				$USER->canDoOperation('edit_all_users')
				&& (
					!ModuleManager::isModuleInstalled('extranet')
					|| (
						strlen(Option::get("extranet", "extranet_site")) > 0
						&& !self::extranetSite()
					)
				)
			);
		}

		return $result;
	}

	/**
	 * Get Settings
	 * @return UserSettings
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * Get specified entity field caption.
	 * @param string $fieldID Field ID.
	 * @return string
	 */
	protected function getFieldName($fieldID)
	{
		$name = Loc::getMessage("MAIN_USER_FILTER_{$fieldID}");

		if($name === null)
		{
			$name = $fieldID;
		}

		return $name;
	}

	public function prepareFieldData($fieldID)
	{
		$result = null;

		if ($fieldID === 'GENDER')
		{
			$result = [
				'params' => ['multiple' => 'N'],
				'items' => [
					'F' => Loc::getMessage('MAIN_USER_FILTER_GENDER_F'),
					'M' => Loc::getMessage('MAIN_USER_FILTER_GENDER_M')
				]
			];
		}
		elseif (in_array($fieldID, [ 'INTEGRATOR', 'ADMIN' ]))
		{
			$result = [
				'params' => ['multiple' => 'N'],
				'items' => [
					'Y' => Loc::getMessage('MAIN_USER_FILTER_Y'),
				]
			];
		}
		elseif (
			$fieldID === 'PERSONAL_COUNTRY'
			|| $fieldID === 'WORK_COUNTRY'
		)
		{
			$countriesList = [];
			$countries = getCountryArray();
			foreach($countries['reference_id'] as $key => $countryId)
			{
				$countriesList[$countryId] = $countries['reference'][$key];
			}

			$result = [
				'items' => $countriesList
			];
		}
		elseif ($fieldID === 'DEPARTMENT')
		{
			return [
				'params' => [
					'apiVersion' => 3,
					'context' => 'USER_LIST_FILTER_DEPARTMENT',
					'multiple' => 'N',
					'contextCode' => 'DR',
					'enableDepartments' => 'Y',
					'departmentFlatEnable' => 'Y',
					'enableAll' => 'N',
					'enableUsers' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'N',
					'isNumeric' => 'N',
//					'prefix' => 'DR',
				]
			];
		}

		return $result;
	}

	/**
	 * Prepare field list.
	 * @return Field[]
	 */
	public function prepareFields()
	{
		$result = [];

		$whiteList = $this->getSettings()->getWhiteList();

		$fieldsList = [
			'ID' => [],
			'NAME' => [
				'whiteList' => [ 'FULL_NAME', 'NAME' ]
			],
			'LAST_NAME' => [
				'whiteList' => [ 'FULL_NAME', 'LAST_NAME' ],
				'options' => [ 'default' => true ]
			],
			'SECOND_NAME' => [
				'whiteList' => 'SECOND_NAME'
			],
			'FIRED' => [
//				'conditionMethod' => 'self::getFiredAvailability',
				'options' => [ 'type' => 'checkbox' ]
			],
			'EXTRANET' => [
				'conditionMethod' => 'self::getExtranetAvailability',
				'options' => [ 'type' => 'checkbox' ]
			],
			'INVITED' => [
				'conditionMethod' => 'self::getInvitedAvailability',
				'options' => [ 'type' => 'checkbox' ]
			],
			'INTEGRATOR' => [
				'conditionMethod' => 'self::getIntegratorAvailability',
				'options' => [ 'type' => 'list', 'partial' => true ]
			],
			'ADMIN' => [
				'conditionMethod' => 'self::getAdminAvailability',
				'options' => [ 'type' => 'list', 'partial' => true ]
			],
			'IS_ONLINE' => [
				'options' => [ 'type' => 'checkbox' ]
			],
			'DEPARTMENT' => [
				'whiteList' => 'UF_DEPARTMENT',
				'options' => [ 'default' => true, 'type' => 'dest_selector', 'partial' => true ]
			],
			'DEPARTMENT_FLAT' => [
				'whiteList' => 'UF_DEPARTMENT_FLAT',
				'options' => [ 'type' => 'dest_selector', 'partial' => true ]
			],
			'TAGS' => [
				'whiteList' => 'TAGS',
				'options' => [ 'default' => true ]
			],
			'LOGIN' => [
				'whiteList' => 'LOGIN'
			],
			'EMAIL' => [
				'whiteList' => 'EMAIL'
			],
			'DATE_REGISTER' => [
				'whiteList' => 'DATE_REGISTER',
				'options' => [ 'type' => 'date' ]
			],
			'LAST_ACTIVITY_DATE' => [
				'whiteList' => 'LAST_ACTIVITY_DATE',
				'options' => [ 'type' => 'date' ]
			],
			'BIRTHDAY' => [
				'whiteList' => 'PERSONAL_BIRTHDAY',
				'options' => [ 'type' => 'date' ]
			],
			'GENDER' => [
				'whiteList' => 'PERSONAL_GENDER',
				'options' => [ 'type' => 'list', 'partial' => true ]
			],
			'PHONE_MOBILE' => [
				'whiteList' => 'PERSONAL_MOBILE'
			],
			'PERSONAL_CITY' => [
				'whiteList' => 'PERSONAL_CITY'
			],
			'PERSONAL_STREET' => [
				'whiteList' => 'PERSONAL_STREET'
			],
			'PERSONAL_STATE' => [
				'whiteList' => 'PERSONAL_STATE'
			],
			'PERSONAL_ZIP' => [
				'whiteList' => 'PERSONAL_ZIP'
			],
			'PERSONAL_MAILBOX' => [
				'whiteList' => 'PERSONAL_MAILBOX'
			],
			'PERSONAL_COUNTRY' => [
				'whiteList' => 'PERSONAL_COUNTRY',
				'options' => [ 'type' => 'list', 'partial' => true ]
			],
			'WORK_CITY' => [
				'whiteList' => 'WORK_CITY'
			],
			'WORK_STREET' => [
				'whiteList' => 'WORK_STREET'
			],
			'WORK_STATE' => [
				'whiteList' => 'WORK_STATE'
			],
			'WORK_ZIP' => [
				'whiteList' => 'WORK_ZIP'
			],
			'WORK_MAILBOX' => [
				'whiteList' => 'WORK_MAILBOX'
			],
			'WORK_COUNTRY' => [
				'whiteList' => 'WORK_COUNTRY',
				'options' => [ 'type' => 'list', 'partial' => true ]
			],
			'WORK_PHONE' => [
				'whiteList' => 'WORK_PHONE'
			],
			'POSITION' => [
				'whiteList' => 'WORK_POSITION'
			],
			'COMPANY' => [
				'whiteList' => 'WORK_COMPANY'
			],
			'WORK_DEPARTMENT' => [
				'whiteList' => 'WORK_DEPARTMENT'
			],
		];

		foreach($fieldsList as $column => $field)
		{
			$whiteListPassed = false;
			if (
				!empty($field['conditionMethod'])
				&& is_callable($field['conditionMethod'])
			)
			{
				$whiteListPassed = call_user_func_array($field['conditionMethod'], []);
			}
			elseif (
				empty($whiteList)
				|| empty($field['whiteList'])
			)
			{
				$whiteListPassed = true;
			}
			elseif (
				!empty($whiteList)
				&& !empty($field['whiteList'])
				&& is_array($field['whiteList'])
			)
			{
				foreach($field['whiteList'] as $whiteListField)
				{
					if (in_array($whiteListField, $whiteList))
					{
						$whiteListPassed = true;
						break;
					}
				}
			}
			elseif (
				!empty($whiteList)
				&& !empty($field['whiteList'])
				&& !is_array($field['whiteList'])
			)
			{
				if (in_array($field['whiteList'], $whiteList))
				{
					$whiteListPassed = true;
				}
			}

			if ($whiteListPassed)
			{
				$result[$column] = $this->createField(
					$column,
					(!empty($field['options']) ? $field['options'] : [])
				);
			}
		}

		return $result;
	}

}