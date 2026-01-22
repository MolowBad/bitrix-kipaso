<?php
namespace Dw\Deluxe\Components;

use CCurrencyLang;
use CUser;
use CCatalogSKU;
use CIBlockElement;
use CCatalogProduct;
use CBitrixComponent;
use Bitrix\Main\Loader;
use Bitrix\Currency\CurrencyManager;

final class FastBuyForm extends CBitrixComponent
{
	private const DEFAULT_CACHE_TIME = 360_000_000;

	public function onPrepareComponentParams($arParams)
	{
		$arParams["CACHE_TYPE"] ??= "A";
		$arParams["CACHE_TIME"] ??= self::DEFAULT_CACHE_TIME;
		return $arParams;
	}

	public function executeComponent()
	{
		$productId = $this->productIdParameter();
		if ($productId === null) {
			return;
		}

		Loader::requireModule("dw.deluxe");

		if ($this->startResultCache()) {
			$productData = $this->buildProductData($productId);
			if ($productData === null) {
				$this->abortResultCache();
				return;
			}

			$this->arResult['PRODUCT'] = $productData;

			$this->setResultCacheKeys(['PRODUCT']);
			$this->endResultCache();
		}

		$this->arResult['PRODUCT']['PRICE'] = $this->buildProductPrice($productId);
		$this->arResult['USER_DATA'] = $this->buildCurrentUserData();

		$this->includeComponentTemplate();
	}

	private function productIdParameter(): ?int
	{
		$productId = $this->arParams['PRODUCT_ID'] ?? null;
		if (
			$productId === null
			|| !is_int($productId)
			&& (
				!is_string($productId)
				|| !ctype_digit($productId)
			)
		) {
			return null;
		}
		return (int) $productId;
	}

	private function buildProductData(int $productId): ?array
	{
		$productOrOfferData = $this->findElementData($productId);
		if ($productOrOfferData === null) {
			return null;
		}

		$parentProduct = CCatalogSKU::GetProductInfo($productId);
		if (!is_array($parentProduct) || !isset($parentProduct['ID'])) {
			return $productOrOfferData;
		}

		$offerData = $productOrOfferData;
		$productData = $this->findElementData($parentProduct['ID']);
		if ($productData === null) {
			return $offerData;
		}

		return $this->inheritOfferFromProduct($offerData, $productData);
	}

	private function inheritOfferFromProduct(array $offerData, array $productData): array
	{
		$offerData['PROPERTIES'] = array_merge(
			$productData['PROPERTIES'],
			$offerData['PROPERTIES']
		);

		if ((int)$offerData['DETAIL_PICTURE'] === 0 && (int)$productData['DETAIL_PICTURE'] > 0) {
			$offerData['DETAIL_PICTURE'] = $productData['DETAIL_PICTURE'];
		}

		return $offerData;
	}

	private function findElementData(int $elementId): ?array
	{
		$result = CIBlockElement::GetList(
			['SORT' => 'ASC'],
			['ID' => $elementId],
			false,
			['nTopCount' => 1],
			['*']
		);

		if ($element = $result->GetNextElement()) {
			$fields = $element->GetFields();
			$properties = $element->GetProperties(['EMPTY' => 'N']);

			$fields['PROPERTIES'] = $properties;

			return $fields;
		}

		return null;
	}

	private function buildProductPrice(int $productId): ?array
	{
		global $USER;

		$optimalPrice = CCatalogProduct::GetOptimalPrice(
			$productId,
			1,
			$USER->GetUserGroupArray()
		);

		if (!is_array($optimalPrice) || !isset($optimalPrice['RESULT_PRICE'])) {
			return null;
		}

		$resultPrice = $optimalPrice['RESULT_PRICE'];
		$baseCurrency = CurrencyManager::getBaseCurrency();

		return [
			'CURRENCY' => $baseCurrency,
			'ACTUAL' => $resultPrice['DISCOUNT_PRICE'],
			'ACTUAL_FORMATTED' => CCurrencyLang::CurrencyFormat($resultPrice['DISCOUNT_PRICE'], $baseCurrency, true),
			'BASE' => $resultPrice['BASE_PRICE'],
			'BASE_FORMATTED' => CCurrencyLang::CurrencyFormat($resultPrice['BASE_PRICE'], $baseCurrency, true),
			'DISCOUNT' => $resultPrice['DISCOUNT'],
			'DISCOUNT_PERCENT' => $resultPrice['PERCENT'],
			'HAS_DISCOUNT' => (float) $resultPrice['DISCOUNT'] > 0
		];
	}

	private function buildCurrentUserData(): ?array
	{
		global $USER;

		if (!$USER->IsAuthorized()) {
			return null;
		}

		$userResult = CUser::GetByID($USER->GetID());
		$userData = $userResult->Fetch();

		if (!is_array($userData)) {
			return null;
		}

		unset(
			$userData["PASSWORD"],
			$userData["CHECKWORD"],
			$userData["CHECKWORD_TIME"]
		);

		return [
			...$userData,
			'ID' => $USER->GetID(),
			'EMAIL' => $USER->GetEmail(),
			'FULL_NAME' => $USER->GetFullName(),
			'FIRST_NAME' => $USER->GetFirstName(),
			'LAST_NAME' => $USER->GetLastName(),
		];
	}
}
