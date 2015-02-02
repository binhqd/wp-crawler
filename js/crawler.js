$(document).ready(function() {
	requestCrawler(1);
});

function requestCrawler(page)
{
	console.log('Crawler Page: '+page);
	jQuery.ajax({
        type: 'GET',
        url: 'crawler.php?page='+page,
        success: function(data) {
        	var obj = jQuery.parseJSON(data);
        	if (!obj.success) {
        		var nextPage = parseInt(obj.page) + 1;
	      		console.log('Crawler success: ' + obj.page);
	        	setTimeout(requestCrawler, 1000, nextPage);
        	} else {
        		console.log('Crawler successfully!!!');
        	}
        }
    });
}
