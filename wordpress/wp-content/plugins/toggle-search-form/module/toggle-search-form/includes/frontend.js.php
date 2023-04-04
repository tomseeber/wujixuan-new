(function($) {
			
	$(document).ready(function(){
		var node = '.fl-node-<?php echo $id; ?>';
		var submitIcon = $( node + ' .fa-search-icon');
		var submitInput = $( node + ' .inline-search');
		var searchBox = $( node + ' .fl-builder-inline-search');
		var isOpen = false;
		
		$(document).mouseup(function(){
			if(isOpen == true){
				submitInput.val('');
				submitIcon.click();
			}
		});

		submitIcon.mouseup(function(){
			return false;
		});

		searchBox.mouseup(function(){
			return false;
		});

		submitIcon.click(function(){
			if(isOpen == false){
				searchBox.addClass('inline-search-open');
				searchBox.find("#inline-search").animate({"padding" : "0 15px"}, 1100);
				isOpen = true;
			} else {
				searchBox.removeClass('inline-search-open');
				searchBox.find("#inline-search").animate({"padding" : "0"}, 1100);
				isOpen = false;
			}
		});
	});
	
})(jQuery);