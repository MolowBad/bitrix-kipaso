<?php

namespace Dw\Deluxe\Components;

use Bitrix\Main\Loader;
use Bitrix\Main\UserConsent\Agreement;
use CBitrixComponent;

final class UserAgreement extends CBitrixComponent
{
	private const DEFAULT_CACHE_TIME = 360_000_000;

	public function onPrepareComponentParams($arParams){
		$arParams["CACHE_TYPE"] ??= "A";
		$arParams["CACHE_TIME"] ??= self::DEFAULT_CACHE_TIME;

		if(!isset($arParams["REPLACEMENTS"]) || !is_array($arParams["REPLACEMENTS"])) {
			$arParams["REPLACEMENTS"] = [];
		}

		return $arParams;
	}

	public function executeComponent()
	{
		$agreementId = $this->agreementParameter();

		if($agreementId === null) {
			return;
		}

		Loader::requireModule("dw.deluxe");

		if($this->startResultCache()) {
			$agreement = new Agreement($agreementId, $this->arParams['REPLACEMENTS']);

			if (!$agreement->isExist() || !$agreement->isActive()) {
				$this->abortResultCache();
				return;
			}

			$agreementData = $agreement->getData();

			$isBuiltInAgreement = $agreementData['TYPE'] === Agreement::TYPE_STANDARD;
			$shouldShowAgreementTextAsHtml = $agreement->isAgreementTextHtml() && !$isBuiltInAgreement;

			$this->arResult = [
				...$agreementData,
				'TITLE' => $agreement->getTitle(),
				'URL' => $agreement->getUrl(),
				'FIELDS' => $agreement->getFields(),
				"FIELD_VALUES" => $agreement->getFieldValues(),
				'TEXT' => $agreement->getText(),
				'IS_HTML' => $shouldShowAgreementTextAsHtml,
				'HTML' => $shouldShowAgreementTextAsHtml ? $agreement->getHtml() : '',
			];

			$this->includeComponentTemplate();
		}
	}

	private function agreementParameter(): ?int
	{
		$agreementId = $this->arParams['AGREEMENT_ID'] ?? null;

		if (
			$agreementId === null
				|| !is_int($agreementId)
				&& (
						!is_string($agreementId)
						|| !ctype_digit($agreementId)
					)
				) {
			return null;
		}

		return (int) $agreementId;
	}
}
