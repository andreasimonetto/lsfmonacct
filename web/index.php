<?php
require_once 'common.inc.php';
$queues = get_queues();
array_unshift($queues, "all");
$queues_js = '['. implode(',', array_map(function($v) { return "{label: '".ucfirst($v)."', value:'$v'}"; }, $queues)) .']';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Monitoring &amp; accounting @ Tier1</title>
<link rel="stylesheet" href="js/jqwidgets/styles/jqx.base.css" type="text/css" />
<!-- link rel="stylesheet" href="js/jqwidgets/styles/jqx.ui-lightness.css" type="text/css" / -->
<link rel="stylesheet" href="css/style.css" type="text/css" />
<script type="text/javascript" src="js/moment.min.js"></script>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxcore.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxdata.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxbuttons.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxscrollbar.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxmenu.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxlistbox.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxdropdownlist.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxgrid.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxgrid.selection.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxgrid.columnsresize.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxgrid.filter.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxgrid.sort.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxgrid.pager.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxgrid.grouping.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxdockpanel.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxdatetimeinput.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxcalendar.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxtooltip.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxradiobutton.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxcheckbox.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxexpander.js"></script>
<script type="text/javascript" src="js/jqwidgets/jqxchart.js"></script>
<script type="text/javascript" src="js/jqwidgets/globalization/globalize.js"></script>
<script type="text/javascript" src="js/jqwidgets/globalization/globalize.culture.it-IT.js"></script>
<script type="text/javascript" src="js/customgraph.js"></script>
<script type="text/javascript">
function updateGraph(event) {
    var queueSelected = $('#queue-selector').jqxListBox('getSelectedItem');
    var unitSelected = $("#unit-selector").jqxDropDownList('getSelectedItem');
    if(!queueSelected || !unitSelected)
        return;

    var type = $('#type-accounting').jqxRadioButton('checked') ? 'accounting' : 'monitoring';
    var submit_local = $('#submit-local').jqxCheckBox('checked');
    var submit_grid = $('#submit-grid').jqxCheckBox('checked');
    var submit = submit_local ? (submit_grid ? 'all' : 'local') : 'grid';
    var queue = queueSelected.value;
    var from = moment($('#period-from').jqxDateTimeInput('getDate')).format('YYYYMMDDHHmm');
    var to = moment($('#period-to').jqxDateTimeInput('getDate')).format('YYYYMMDDHHmm');
    var period = from+'-'+to
    var unit = unitSelected.value;

    //
    var graph = document.getElementById('graph');
    var graphWrapper = document.createElement('div');
    graphWrapper.id = 'graph-wrapper';
    if(graph.childNodes.length == 0)
        graph.appendChild(graphWrapper);
    else
        graph.replaceChild(graphWrapper, graph.childNodes[0]);

    if(type == 'accounting' && unit == 'hs06_grid') {
        var width = parseFloat(graph.style.width);
        var height = Math.floor(parseFloat(graph.style.height));
        graphWrapper.style.margin = '6px 0 0 ' + Math.floor((width - 800) / 2.0)  + 'px';

        gridPledgeAccounted(period, function(data) {
            // prepare jqxGrid settings
            $('#' + graphWrapper.id).jqxGrid({
                source: new $.jqx.dataAdapter({localdata: data, datatype: 'array'}),
                //pageable: true,
                width: 800,
                height: height - 8,
                sortable: true,
                selectionmode: 'multiplecellsadvanced',
//                theme: 'classic',
                columns: [
                  { text: 'Queue', datafield: 'queue', width: 180 },
                  { text: 'CPT (Hs06-period)', datafield: 'cpt', width: 130, cellsformat: 'd2', cellsalign: 'right' },
                  { text: 'CPT average (Hs06-day)', datafield: 'cpt_avg', width: 170, cellsformat: 'd2', cellsalign: 'right' },
                  { text: 'WCT (Hs06-period)', datafield: 'wct', width: 130, cellsformat: 'd2', cellsalign: 'right' },
                  { text: 'WCT average (Hs06-day)', datafield: 'wct_avg', width: 170, cellsformat: 'd2', cellsalign: 'right' }
                ]
            });
        });
    }
    else if(type == 'accounting' && unit == 'wct') {
        graphWrapper.style.width = graph.style.width;
        graphWrapper.style.height = graph.style.height;
        graphPledgeAccounted(period, function(accounted_tot, pledge_tot, data) {
            // prepare jqxChart settings
            $('#' + graphWrapper.id).jqxChart({
                title: "", description: "Accounted (average): " + Math.floor(accounted_tot) + " hs06 - Pledged: " + pledge_tot + " hs06",
                //title: "Wall Clock Accounting",
                //description: "WCT accounted versus pledged in TIME PERIOD",
                enableAnimations: false,
                showBorderLine: false,
                padding: { left: 5, top: 5, right: 5, bottom: 5 },
                titlePadding: { left: 90, top: 0, right: 0, bottom: 10 },
                source: data,
                categoryAxis: {
               	    dataField: "queue",
                    showGridLines: false
                },
                colorScheme: "scheme01",
                seriesGroups: [{
                    type: "column",
                    columnsGapPercent: 30,
                    seriesGapPercent: 0,
                    valueAxis: {
                        minValue: 0,
                        description: "Hepspec06"
                    },
                    series: [
                        { dataField: "accounted", displayText: "Accounted (average)" },
                        { dataField: "pledged", displayText: "Pledged" }
                    ]
                }]
            });
        });
    }
    else if(type == 'accounting' && unit == 'pledges') {
        var width = parseFloat(graph.style.width);
        var height = parseFloat(graph.style.height) - 6;
        var ray = Math.floor(width < height ? width : height);
        graphWrapper.style.width = graphWrapper.style.height = ray + 'px';
        graphWrapper.style.margin = '6px 0 0 ' + Math.floor(((width - ray) / 2.0)) + 'px';

        graphPledges(period, function(data) {
            // prepare jqxChart settings
            $('#' + graphWrapper.id).jqxChart({
               	title: "", description: "",
               	enableAnimations: false,
               	showLegend: true,
               	showBorderLine: false,
               	padding: { left: 5, top: 5, right: 5, bottom: 5 },
               	titlePadding: { left: 90, top: 0, right: 0, bottom: 10 },
               	source: data,
               	colorScheme: "scheme01",
               	seriesGroups: [{
                    type: "pie",
                    showLabels: true,
                    series: [{
                       	dataField: "pledge",
                       	displayText: "queue",
                       	initialAngle: 0,
                       	centerOffset: 0,
                       	formatSettings: { sufix: '%', decimalPlaces: 1 }
                    }]
               	}]
            });
        });
    }
    else {
        var imgLink = document.createElement('a');
        var img = document.createElement('img');
        var width = Math.floor(parseFloat(graph.style.width) * 0.9);
        var height = Math.floor(parseFloat(graph.style.height)) - 40;
        var img_base_url = type+'/'+submit+'/'+queue+'/'+period+'.'+unit;

        // img.parentNode.href = img_base_url+'-'+window.innerWidth+'x'+window.innerHeight+'.png';
        img.src = img_base_url+'-'+width+'x'+height+'.png';
        imgLink.href = img_base_url+'-'+document.body.clientWidth+'x'+document.body.clientHeight+'.png';
        imgLink.appendChild(img);

        var button = ['csv', 'json'];
        var buttons = document.createElement('div');
        buttons.className = 'format-button';
        for(var i in button) {
            var buttonLink = document.createElement('a');
            var buttonImg = document.createElement('img');
            buttonLink.href = img_base_url + '.' + button[i]
            buttonImg.src = 'images/' + button[i] + '.png';
            buttonLink.appendChild(buttonImg);
            buttons.appendChild(buttonLink);
        }

        graphWrapper.appendChild(buttons);
        graphWrapper.appendChild(imgLink);
    }
}

var _periodChanged = false, _refreshHandler;
var _monitoringUnitSelector = [
    { label: 'Jobs', value: 'jobs' },
    { label: 'Efficiency', value: 'efficiency' },
    { label: 'Seconds', value: 'sec' }
];
var _accountingUnitSelector = [
    { label: 'Wall Clock Time', value: 'wct' },
    { label: 'CPT/WCT Chart', value: 'hs06_grid' },
    { label: 'Pledge', value: 'pledges' },
    { label: 'HepSpec06', value: 'hs06' },
    { label: 'Efficiency', value: 'efficiency' },
    { label: 'Jobs', value: 'jobs' },
    { label: 'Seconds', value: 'sec' }
];

$(document).ready(function () {
    $("#type").jqxExpander({ width: 250/*, height: 100 */});
    $("#submit").jqxExpander({ width: 250/*, height: 100 */});
    $("#queue").jqxExpander({ width: 250/*, height: 100 */});
    $("#period").jqxExpander({ width: 250/*, height: 100 */});
    $("#unit").jqxExpander({ width: 250/*, height: 100 */});

    //$("#jqxbutton").jqxTooltip({ position: 'top', content: 'This is a jqxButton.' });

    // monitoring/accounting radio button
    $("#type-monitoring").jqxRadioButton({ width: 120, height: 25 });
    $("#type-monitoring").bind('change', function(event) {
        if(!event.args.checked)
            return;

        $("#unit-selector").jqxDropDownList('clear');
        for(var i in _monitoringUnitSelector)
            $("#unit-selector").jqxDropDownList('addItem', _monitoringUnitSelector[i]);

        if(!_periodChanged) {
            $('#period-now').jqxCheckBox('check');
            var now = new Date();
            var yesterday = new Date();
            yesterday.setDate(now.getDate() - 1);
            $('#period-from').jqxDateTimeInput('setDate', yesterday);
            $('#period-to').jqxDateTimeInput('setDate', now);
            _periodChanged = false;
        }

        document.getElementById('header-title').textContent = 'Monitoring @ Tier1';
        $("#unit-selector").jqxDropDownList('selectIndex', 0);
    });

    $("#type-accounting").jqxRadioButton({ width: 120, height: 25 });
    $("#type-accounting").bind('change', function(event) {
        if(!event.args.checked)
            return;

        $("#unit-selector").jqxDropDownList('clear');
        for(var i in _accountingUnitSelector)
            $("#unit-selector").jqxDropDownList('addItem', _accountingUnitSelector[i]);

        if(!_periodChanged) {
            $('#period-now').jqxCheckBox('uncheck');
            var now = new Date();
            var curyear = new Date(now.getFullYear(), 0, 1, 0, 0, 0, 0);
            $('#period-from').jqxDateTimeInput('setDate', curyear);
            $('#period-to').jqxDateTimeInput('setDate', now);
            _periodChanged = false;
        }

        document.getElementById('header-title').textContent = 'Accounting @ Tier1';
        $("#unit-selector").jqxDropDownList('selectIndex', 0);
    });

    // Submit type (local, grid, all) check box
    $("#submit-local").jqxCheckBox({ width: 120, height: 25 });
    $("#submit-local").bind('change', updateGraph);
    $("#submit-local").jqxCheckBox('check');
    $("#submit-grid").jqxCheckBox({ width: 120, height: 25 });
    $("#submit-grid").bind('change', updateGraph);
    $("#submit-grid").jqxCheckBox('check');

    // Prepare the data adapter for queues info
    var dataAdapter = new $.jqx.dataAdapter({
        datatype: "json",
        datafields: [
            { name: 'name' },
        ],
        id: 'id',
        url: '/queues.json'
    });

    // Create queues selector
    $("#queue-selector").jqxListBox({ source: <?=$queues_js?>, displayMember: "label", valueMember: "value", width: 246, height: 250, theme: '' });
    $("#queue-selector").jqxListBox('selectIndex', 0);
    $("#queue-selector").jqxDropDownList('selectIndex', 0);
    $("#queue-selector").bind('select', updateGraph);

    // Create from-to date/time selectors
    var now = new Date();
    var yesterday = new Date();
    yesterday.setDate(now.getDate() - 1);
    $("#period-from").jqxDateTimeInput({ width: '180px', height: '25px', formatString: 'yyyy/MM/dd HH:mm', value: yesterday });
    $("#period-from").jqxDateTimeInput({ firstDayOfWeek: 1 });
//    $("#period-from").jqxDateTimeInput({ allowNullDate: false });
    $("#period-from").jqxDateTimeInput({ culture: 'it-IT' });
    $("#period-from").bind('change', function(event) {
        _periodChanged = true;
        updateGraph()
    });
    $("#period-to").jqxDateTimeInput({ width: '180px', height: '25px', formatString: 'yyyy/MM/dd HH:mm', value: now });
    $("#period-to").jqxDateTimeInput({ firstDayOfWeek: 1 });
//    $("#period-to").jqxDateTimeInput({ allowNullDate: false });
    $("#period-to").jqxDateTimeInput({ culture: 'it-IT' });
    $("#period-to").bind('change', function(event) {
        _periodChanged = true;
        updateGraph()
    });

    $("#period-now").jqxCheckBox({ width: 120, height: 25 });
    $("#period-now").bind('change', function(event) {
        if(event.args.checked) {
             $("#period-to").jqxDateTimeInput({ disabled: true });
            _refreshHandler = self.setInterval(function() {
                $("#period-to").jqxDateTimeInput('setDate', new Date());
            }, 60000);
        }
        else {
             $("#period-to").jqxDateTimeInput({ disabled: false });
            clearInterval(_refreshHandler);
        }
    });

    // Create unit selector
    $("#unit-selector").jqxDropDownList({ source: _monitoringUnitSelector, displayMember: 'label', valueMember: 'value', width: '236px', height: '25px'});
    $("#unit-selector").jqxDropDownList('selectIndex', 0);
    $("#unit-selector").bind('select', updateGraph);

    // Create dock panel
    $("#dockpanel").jqxDockPanel({ width: "100%", height: document.body.clientHeight });

    //
    $("#type-<?= preg_match('/accounting/', $_SERVER['REQUEST_URI']) ? 'accounting' : 'monitoring'; ?>").jqxRadioButton('check');
});
</script>
</head>
<body>
<div id='dockpanel'>
<div id='header' dock='top' style='text-align:center'>
<h1 id='header-title'>Monitoring &amp; accounting @ Tier1</h1>
</div>
<div id='form' dock='left'>
<div id='type' class='expander'>
    <div class='expander-title'>Type</div>
    <div class='expander-inside-text'>
        <div id='type-monitoring'>Monitoring</div>
        <div id='type-accounting'>Accounting</div>
    </div>
</div>
<div id='unit' class='expander'>
    <div class='expander-title'>Unit</div>
    <div class='expander-inside-dropdown'>
        <div id='unit-selector'></div>
    </div>
</div>
<div id='period' class='expander'>
    <div class='expander-title'>Period</div>
    <div class='expander-inside-text'>
        <table>
            <tr><td style='text-align: right'>From:</td><td><div id='period-from'></div></td></tr>
            <tr><td style='text-align: right'>To:</td><td><div id='period-to'></div></td></tr>
            <tr><td>&nbsp;</td><td><div id='period-now'>Now</div></td></tr>
        </table>
    </div>
</div>
<div id='queue' class='expander'>
    <div class='expander-title'>Queue</div>
    <div class='expander-inside-selector'>
        <div id='queue-selector'></div>
    </div>
</div>
<div id='submit' class='expander'>
    <div class='expander-title'>Submit</div>
    <div class='expander-inside-text'>
        <div id='submit-local'>Local</div>
        <div id='submit-grid'>Grid</div>
    </div>
</div>
</div>
<div id='graph' style='text-align: center'>
    <div id='graph-wrapper'><a href='#'><img id='graph-img' /></a></div>
</div>
</div>
</body>
</html>
