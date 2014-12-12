// Load the Visualization API and the corechart package.
google.load('visualization', '1', {
	'packages' : [ 'corechart' ]
});

// Set callback to run when the Google Visualization API is loaded.
google.setOnLoadCallback(drawChart);

/*
 * draw charts
 * 
 */
function drawChart() {
	drawSummaryChart();
	drawStatsChart();
}

/*
 * draw stats by date charts
 */
function drawStatsChart() {
	var jsonData = jQuery.ajax({
		type : 'POST',
		url : sos_chart.ajax_url,
		dataType : "json",
		data : {
			action : 'get-stats',
			_ajax_nonce : sos_chart.stats_nonce,
		},
		async : false
	}).responseText;
	console.log(jsonData);
	var data = new google.visualization.DataTable(jsonData);
	data.sort([ {
		column : 0
	} ]);

	// Instantiate and draw our chart, passing in some options.
	var chart = new google.visualization.LineChart(document.getElementById('stats_chart'));
	var options = {		
		title : sos_chart.stats_title,
		width : 600,
		height : 400
	};
	chart.draw(data, options);
}

/*
 * draw stats summary chart 
 */
function drawSummaryChart() {
	var jsonData = jQuery.ajax({
		type : 'POST',
		url : sos_chart.ajax_url,
		dataType : "json",
		data : {
			action : 'get-stats-summary',
			_ajax_nonce : sos_chart.stats_summary_nonce,
		},
		async : false
	}).responseText;
	
	var data = new google.visualization.DataTable(jsonData);

	// Instantiate and draw our chart, passing in some options.
	var chart = new google.visualization.BarChart(document.getElementById('summary_chart'));
	var options = {
		title : sos_chart.summary_title,
		orientation : 'horizontal',
		width : 500,
		height : 400
	};
	chart.draw(data, options);
}
