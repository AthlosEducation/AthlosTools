/* Value Labels Plugin for flot.
 * Homepage:
 * http://sites.google.com/site/petrsstuff/projects/flotvallab
 *
 * Released under the MIT license by Petr Blahos, December 2009.
 *
 */
(function ($) {
    var options = {
        valueLabels: {
	    show: false,
        }
    };
    
    function init(plot) {
        plot.hooks.draw.push(function (plot, ctx) {
	    if (!plot.getOptions().valueLabels.show) {
	        return
	    }
	    $.each(plot.getData(), function(ii, series) {
		    plot.getPlaceholder().find("#valueLabels"+ii).remove();
		    var html = '<div id="valueLabels-' + ii + '" class="valueLabels">';
		    var last_val = null;
		    var last_x = -1000;
		    var last_y = -1000;
		    for (var i = 0; i < series.data.length; ++i) {
			if (series.data[i] == null)
			    continue;
			  
			var x = series.data[i][0], y = series.data[i][1];
			if (x < series.xaxis.min || x > series.xaxis.max || y < series.yaxis.min || y > series.yaxis.max)
			    continue;
			var val = y;
			if (series.valueLabelFunc) {
				val = series.valueLabelFunc({ series: series, seriesIndex: ii, index: i });
			}
			val = ""+val;
			if (val!=last_val || i==series.data.length-1) {
				//-- figure out number of bars to offset --//
				if(x == 1){
					if(plot.getData().length == 1){ var barNum = -1; }else if(plot.getData().length == 2){ var barNum = 1; }else if(plot.getData().length == 3){ var barNum = 2; }else if(plot.getData().length == 4){ var barNum = 3; }
				}else if(x == 2){
					if(plot.getData().length == 1){ var barNum = (ii - .5); console.log(barNum); }else if(plot.getData().length == 2){ var barNum = (ii + .5); }else if(plot.getData().length == 3){ var barNum = (ii + 1); }else if(plot.getData().length == 4){ var barNum = (ii + 1) + .5; }
				}
				
				var x_diff = (plot.width() * (x / series.xaxis.max));
				var x_bar = x_diff * .2
				var xx = x_diff - ((x_bar / 2) * barNum);
				var y_diff = (plot.height() * (parseInt(val) / series.yaxis.max));
				var yy = plot.height() - 12 - (y_diff / 2);
				if (Math.abs(yy-last_y)>20 || last_x<xx) {
					last_val = val;
					last_x = xx + val.length*8;
					last_y = yy;
					//-- determine final xx offset --//
					xx = xx + (x_bar * ii);
					if(val != '0.1'){
						var head = '<div style="position: absolute; left:' + xx + 'px;top:' + yy + 'px; color: #fff; font-weight: 600;" class="valueLabel';
						var tail = '">' + val + '</div>';
						//html+= head + "Light" + tail + head + tail;
						html+= head + "Light" + tail;
					}
				}
			}
		    }
		    html+= "</div>";
		    plot.getPlaceholder().append(html);
		});
        });
    }
    
    $.plot.plugins.push({
        init: init,
        options: options,
        name: 'valueLabels',
        version: '1.0'
    });
})(jQuery);

