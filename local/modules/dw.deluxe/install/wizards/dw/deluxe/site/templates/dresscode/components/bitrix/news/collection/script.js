$(function () {
	var changeSortParams = function () {
		window.location.href = $(this).find(".dropDownItem.selected").data("value");
	};

	$("#selectSortParams, #selectCountElements").on("change", changeSortParams);
});
