$(window).on("load", function () {
	$(".banner-animated").addClass("banner-image-load");
});

$(function () {
	var openCommentForm = function (event) {
		$(".form-with-comments").click();
	};
	$(document).on("click", ".open-form-with-comments", openCommentForm);
});
