<?php
//
// Wrapper for Graphite URL API
//
// Copyright (C) 2013 Andrea Simonetto - andrea.simonetto@cnaf.infn.it
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

require_once("common.inc.php");

function datefmt($date, $now = NULL) {
    $sec = preg_replace_callback('/^(\d+)([hdw])$/', function($matches) {
            $secFactor = array("h" => 3600, "d" => 86400, "w" => 86400 * 7);
            return $matches[1] * $secFactor[$matches[2]];
        }, $date);

    if($sec != $date) {
        if($now === NULL)
            $now = time();
        return date("H:i_Ymd", $now - $sec);
    }

    return preg_replace('/^(\d{8})(\d{2})(\d{2})$/', '$2:$3_$1', $date);
}

global $GRAPHITE_BASE_URL;
global $GRAPH_DEFAULT_SIZE;

//
if(!array_key_exists("args", $_GET))
    die("'args' parameter missing!");

list($type, $submit, $queue, $period, $unit, $format) = preg_split("/,/", $_GET["args"]);

//
$size = substr($_GET["size"], 1);
list($width, $height) = preg_split('/x/', $size != "" ? $size : $GRAPH_DEFAULT_SIZE);
$submit = preg_replace('/^all$/', '*', $submit);
$queue  = preg_replace('/^all$/', '*', $queue);
$gqueue = ($queue == "each" ? "*" : $queue);

//
$ts    = time();
$until = date("H:i_Ymd", $ts);
$today = date("Ymd", $ts);
$dow   = date("N", $ts); // 1=Mon, ..., 7=Sun

switch($period) {
    case "day":
        $from = "00:00_$today";
        break;
    case "week":
        $from = date("Ymd", $ts - 86400 * ($dow - 1));
        break;
    case "month":
        $from = date("Ym", $ts) . "01";
        $until = $today;
        break;
    case "year":
        $from = date("Y", $ts) . "0101";
        $until = $today;
        break;
    default:
        $dates = preg_split("/-/", $period);
        $dates_num = count($dates);
        if($dates_num == 0)
            die("Error: date not specified");

        $from = datefmt($dates[0], $ts);
        if($dates_num >= 2)
            $until = datefmt($dates[1], $ts);
        break;
}

//
$params = array(
    "format" => $format,
    "width" => $width,
    "height" => $height,
    "from" => $from,
    "until" => $until,
    "yMin" => 0,
    "vtitle" => ucfirst($unit),
    "fgcolor" => "000000",
    "bgcolor" => "ffffff",
    "majorGridLineColor" => "darkgrey",
);

$submitTypeDisplay = ($submit == "*" ? "Total" : ucfirst($submit));
//$dateFormat = ?
//$params["xFormat"] = $dateFormat;

//
switch($type) {
    case "monitoring":
        switch($unit) {
            case "jobs":
                // TODO: complete
                if($queue == "each")
                    die("Unsupported E01");

                $gridColor = "a080ff";
                $localColor = "6464ff";
                if($submit == "*") {
                    $running = "alias(color(stacked(sumSeries(keepLastValue(monitoring.$queue.grid.running))),'$gridColor'), 'GRID running'),"
                        . "alias(color(stacked(sumSeries(keepLastValue(monitoring.$queue.local.running))), '$localColor'), 'Local running'),";
                }
                else {
                    $color = ($submit == "grid" ? $gridColor : $localColor);
                    $running = "alias(color(stacked(sumSeries(keepLastValue(monitoring.$queue.$submit.running))), '$color'), 'Running'),";
                }

                $params["target"] = "group("
                    . "alias(color(sumSeries(keepLastValue(group(monitoring.$queue.$submit.running, monitoring.$queue.$submit.unknown, monitoring.$queue.$submit.pending, monitoring.$queue.$submit.suspended))), 'red'), 'Total'),"
                    . $running
                    . "alias(color(stacked(sumSeries(keepLastValue(monitoring.$queue.$submit.unknown))), 'black'), 'Unknown'),"
                    . "alias(color(stacked(sumSeries(keepLastValue(monitoring.$queue.$submit.pending))), 'green'), 'Pending'),"
                    . "alias(color(stacked(sumSeries(keepLastValue(monitoring.$queue.$submit.suspended))), 'ff66ff'), 'Suspended')"
                    . ")";
                break;

            //
            // TODO: case "sec"
            //
            case "sec":
                $params["target"] = "group("
                    . "alias(color(sumSeries(keepLastValue(monitoring.$queue.$submit.cpt)), 'red'), 'CPT'),"
                    . "alias(color(sumSeries(keepLastValue(monitoring.$queue.$submit.wct)), 'blue'), 'WCT')"
                    . ")";
            break;
            case "efficiency":
                // TODO: complete
                if($queue == "each")
                    die("Unsupported E02");

                $params["yMax"] = 100;
                $params["vtitle"] = "%";
                $params["target"] = "alias(color(scale(divideSeries(sumSeries(keepLastValue(monitoring.$queue.$submit.cpt)),"
                    . "sumSeries(keepLastValue(monitoring.$queue.$submit.wct))), 100), 'red'), 'CPT/WCT')";
                break;
        }
        break;

    case "accounting":
        switch($unit) {
            case "jobs":
                if($queue == "each") {
                    // This function require to perform multiple queries to the graphite web service
                    // due to lack of expressivity in graphite function API. But the resulting query
                    // is far too long to be submitted 
                    die("Unsupported E03");
                }
                else {
                    $params["target"] = "group("
                        . "alias(color(stacked(sumSeries(keepLastValue(accounting.$queue.$submit.ndone))), '6464ff'), 'Done $submitTypeDisplay'),"
                        . "alias(color(stacked(diffSeries(sumSeries(keepLastValue(accounting.$queue.$submit.njobs)), sumSeries(keepLastValue(accounting.$queue.$submit.ndone)))), 'a080ff'), 'Finished $submitTypeDisplay')"
                        . ")";
                }
                break;

            case "sec":
            case "hs06":
                if($queue == "each") {
                    $wct = "keepLastValue(accounting.$gqueue.$submit.wct.$unit)";
                    $cpt = "keepLastValue(accounting.$gqueue.$submit.cpt.$unit)";

                    $params["target"] = "groupByNode(aliasSub(keepLastValue(accounting.$gqueue.$submit.*.$unit), '.*accounting\\.(\\w+)\\.(\\w+)\\.(\\w+)\\.$unit.*', '\\1:\\3.\\2'), 0, 'sumSeries')";
                }
                else {
                    $wct = "keepLastValue(accounting.$gqueue.$submit.wct.$unit)";
                    $cpt = "keepLastValue(accounting.$gqueue.$submit.cpt.$unit)";

                    $params["target"] = "group("
                        . "alias(color(sumSeries($wct), 'red'), 'WCT $submitTypeDisplay'),"
                        . "alias(color(sumSeries($cpt), 'blue'), 'CPT $submitTypeDisplay'))";
                }
                break;

            case "efficiency":
                $params["yMax"] = 100;
                $params["vtitle"] = "%";

                $cpt = "keepLastValue(accounting.$gqueue.$submit.cpt.hs06)";
                $wct = "keepLastValue(accounting.$gqueue.$submit.wct.hs06)";
                if($queue == "each") {
                    // This function require to perform multiple queries to the graphite web service
                    // due to lack of expressivity in graphite function API. But the resulting query
                    // is far too long to be submitted AFAIK.
                    die("Unsupported E04");

                    /*
                    $params["target"] = "group(" . implode(",", array_map(function($q) {
                        global $submit;
                        return "alias(divideSeries(sumSeries(accounting.$q.$submit.cpt.hs06),sumSeries(accounting.$q.$submit.wct.hs06)),'$q')";
                    }, $queues)) . ")"; */
                }
                else {
                    $params["target"] = "alias(color(scale(divideSeries(sumSeries($cpt), sumSeries($wct)), 100), 'red'), 'CPT/WCT')";
                }
                break;

            case "pledge":
                $pledge = "keepLastValue(pledge.$gqueue.hs06)";
                $params["target"] = ($queue == "each" ? "aliasByNode($pledge, 1)" : "alias(sumSeries($pledge), 'Pledge')");
                break;
        }
        break;
}

//
switch($format) {
    case "png":
        header("Content-type: image/png");
        break;
    case "json":
        header("Content-type: application/json");
        break;
    case "csv":
        header("Content-type: text/csv");
        break;
    default:
    case "csv":
        header("Content-type: text/html");
        break;
}

//header("Cache-Control: no-cache, must-revalidate");
//header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

//
$fp = fopen("$GRAPHITE_BASE_URL/render?" . http_build_query($params), "r");
fpassthru($fp);
fclose($fp);

?>
