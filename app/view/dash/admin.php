<div class="container">

    <div class="col-md-6">
        <div class="row widget">
            <div id="distibution">
            </div>
        </div>
    </div>
    <div class="col-md-6"></div>

</div>
<script>
$(function () {

    // Radialize the colors
    Highcharts.getOptions().colors = Highcharts.map(Highcharts.getOptions().colors, function (color) {
        return {
            radialGradient: {
                cx: 0.5,
                cy: 0.3,
                r: 0.7
            },
            stops: [
                [0, color],
                [1, Highcharts.Color(color).brighten(-0.3).get('rgb')] // darken
            ]
        };
    });
});
    
    var distibution = JSON.parse('<?=$distibution?>');
    //DASH DIST 
    $('#distibution').highcharts({
        chart:{
            type:'line'
        },  
        title: {
            text: ''
        },
        xAxis: {
           
        },

        series: [{
            name:'Actions',
            data: distibution
        }]
    });

</script>


