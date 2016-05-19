<div class="container">
    <div class="col-md-3 col-xs-12">
        <div class="row widget">
            Completion % This Month
            <div id="container-workload" style="height: 150px;"></div>
        </div>
    </div>
    <div class="col-md-3 col-xs-12">
        <div class="row widget">
            Allotted Time
            <div id="time-gauge" style="height: 230px;"></div>
        </div>
        <div class="row widget">
            Network Breakdown
            <div id="strategy-breakdown" style="height: 230px;"></div>
        </div>
    </div>
    <div class="col-md-6 col-xs-12">
        <div class="row widget">
            Daily Activity
            <div id="daily-line" style="height: 230px;"></div>
        </div>
        <div class="row widget">
            Team Completion
            <div id="global-completion" style="height: 230px;"></div>
        </div>
    </div>
</div>

<script>
    $(function () {
        //--->TIME GAUDGE
        $('#time-gauge').highcharts({
        exporting:{
            chartOptions: { // specific options for the exported image
                plotOptions: {
                    series: {
                        dataLabels: {
                            enabled: true
                        }
                    }
                }
            },
            scale: 3,
            fallbackToExportServer: false
        },
        chart: {
            type: 'gauge',
            plotBackgroundColor: null,
            plotBackgroundImage: null,
            plotBorderWidth: 0,
            plotShadow: false
        },

        title: {
            text: null
        },
				credits:{
        	enabled:false
        },
        pane: {
            startAngle: -150,
            endAngle: 150,
            background: [{
                backgroundColor: {
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [
                        [0, '#FFF'],
                        [1, '#333']
                    ]
                },
                borderWidth: 0,
                outerRadius: '109%'
            }, {
                backgroundColor: {
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [
                        [0, '#333'],
                        [1, '#FFF']
                    ]
                },
                borderWidth: 1,
                outerRadius: '107%'
            }, {
                // default background
            }, {
                backgroundColor: '#DDD',
                borderWidth: 0,
                outerRadius: '105%',
                innerRadius: '103%'
            }]
        },

        // the value axis
        yAxis: {
            min: 0,
            max: 100,

            minorTickInterval: 'auto',
            minorTickWidth: 1,
            minorTickLength: 10,
            minorTickPosition: 'inside',
            minorTickColor: '#666',

            tickPixelInterval: 30,
            tickWidth: 2,
            tickPosition: 'inside',
            tickLength: 10,
            tickColor: '#666',
            labels: {
                step: 2,
                rotation: 'auto'
            },
            title: {
                text: '%'
            },
            plotBands: [{
                from: 0,
                to: 50,
                color: '#55BF3B' // green
            }, {
                from: 50,
                to: 75,
                color: '#DDDF0D' // yellow
            }, {
                from: 75,
                to: 100,
                color: '#DF5353' // red
            }]
        },

        series: [{
            name: 'Assigned',
            data: [<?= $time_per; ?>],
            tooltip: {
                valueSuffix: ' %'
            }
        }]
    });
        //--strategy breakdown
        $('#strategy-breakdown').highcharts({
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
                type: 'pie'
            },
            title: {
                text: null
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: false
                    },
                    showInLegend: true
                }
            },
            credits:{
            	enabled:false,
            
            },
            series: [{
                name: 'Networks',
                colorByPoint: true,
                data: <?= $breakdown;?>
            }]
        });    
        
    //--gloabal completion-->
    
    $('#global-completion').highcharts({
        chart: {
            type: 'column'
        },
        title: {
            text: null
        },
        xAxis: {
            type: 'category',
            labels: {
                rotation: -45,
                style: {
                    fontSize: '13px',
                    fontFamily: 'inherit'
                }
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: '% complete'
            },
            ceiling: 100,
            max: 100
        },
        legend: {
            enabled: false
        },
        credits: {
            enabled: false
        },
        tooltip: {
            pointFormat: '{point.y:.1f}%'
        },
        series: [{
            data: <?= $completion_global ?>,
            dataLabels: {
                enabled: true,
                rotation: -90,
                color: '#FFFFFF',
                align: 'right',
                format: '{point.y:.1f}', // one decimal
                y: 10, // 10 pixels down from the top
                style: {
                    fontSize: '13px',
                    fontFamily: 'inherit'
                }
            }
        }]
    });
        
    //--daily line-->
    
        $('#daily-line').highcharts({
            chart:{
                backgroundColor:'#FBFBFB'
            },  
            title: {
                text: '',
            },
            xAxis: {
                categories: <?= $daily_line['cats'] ?>
            },
            yAxis: {
                title: {
                    text: '# Activities'
                },
                plotLines: [{
                    value: 0,
                    width: 1,
                    color: '#808080'
                }]
            },
            tooltip: {
                valueSuffix: ''
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Activity',
                data:<?= $daily_line['acts'] ?>
            }]
        });
    
    
    
    
    //--com-->

        var gaugeOptions = {

            chart: {
                type: 'solidgauge',
                backgroundColor:'#FBFBFB'
            },

            title: null,

            pane: {
                center: ['50%', '85%'],
                size: '148%',
                startAngle: -90,
                endAngle: 90,
                background: {
                    backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || '#EEE',
                    innerRadius: '60%',
                    outerRadius: '100%',
                    shape: 'arc'
                }
            },

            tooltip: {
                enabled: false
            },

            // the value axis
            yAxis: {
                stops: [
                    [0.1, '#DF5353'], // green
                    [0.5, '#DDDF0D'], // yellow
                    [0.9, '#55BF3B'] // red
                ],
                lineWidth: 0,
                minorTickInterval: null,
                tickPixelInterval: 400,
                tickWidth: 0,
                title: {
                    y: -70
                },
                labels: {
                    y: 16
                }
            },

            plotOptions: {
                solidgauge: {
                    dataLabels: {
                        y: 5,
                        borderWidth: 0,
                        useHTML: true
                    }
                }
            }
        };

        // The speed gauge
        $('#container-workload').highcharts(Highcharts.merge(gaugeOptions, {
            yAxis: {
                min: 0,
                max: 100,
                title: {
                    text: null
                }
            },

            credits: {
                enabled: false
            },

            series: [{
                name: 'Workload',
                data: [<?=$completion?>],
                dataLabels: {
                    format: '<div style="text-align:center"><span style="font-size:10px;color:' +
                        ((Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black') + '">{y}%</span><br/>' +
                           '<span style="font-size:12px;color:silver"></span></div>'
                },
                tooltip: {
                    valueSuffix: ' '
                }
            }]

        }));
    });
</script>