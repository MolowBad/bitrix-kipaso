<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (empty($arResult))
	return;

$arResult = [
	...array_filter(
		$arResult,
		fn (array $element) => !empty($element["TITLE"])
	)
];

foreach ($arResult as $position => &$element) {

	$element["TITLE"] = htmlspecialcharsEx($element["TITLE"]);
	$element["POSITION"] = $position+1;

}

$lastElement = array_pop($arResult);

$result = <<<LIST
	<div id="breadcrumbs">
		<ul itemscope itemtype="https://schema.org/BreadcrumbList">
LIST;

foreach ($arResult as &$element) {

	$result .= <<<ITEM
		<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
			<a href="{$element["LINK"]}" title="{$element["TITLE"]}" itemprop="item">
				<span itemprop="name">{$element["TITLE"]}</span>
				<meta itemprop="position" content="{$element["POSITION"]}">
			</a>
		</li>
		<li>
			<span class="arrow"> &bull; </span>
		</li>
	ITEM;

}

$result .= <<<ITEM
	<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
		<span itemprop="name" class="changeName">{$lastElement["TITLE"]}</span>
		<meta itemprop="position" content="{$lastElement["POSITION"]}">
	</li>
ITEM;

$result .= <<<END_LIST
		</ul>
	</div>
END_LIST;

return $result;
