<?php
//Plugin: User Line Chart Stats
//Author: Ivan
//Allows you to view the history of games for players as a timeline.

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '1';

if ($PluginEnabled == 1  ) {
   
   if (isset($_GET["u"]) AND is_numeric($_GET["u"]) ) {
   AddEvent("os_head","OS_JQuery182");
   AddEvent("os_display_custom_fields","OS_ChartData");
   }

   function OS_JQuery182() {
     global $db;
	 global $MinDuration;
	 global $UserData;
	 global $MapString;
	 
	 if ( isset($_GET["showgames"]) AND $_GET["showgames"] == 10)  $Total = 10; else
	 if ( isset($_GET["showgames"]) AND $_GET["showgames"] == 20)  $Total = 20; else
	 if ( isset($_GET["showgames"]) AND $_GET["showgames"] == 50)  $Total = 50; else
	 if ( isset($_GET["showgames"]) AND $_GET["showgames"] == 100) $Total = 100; else
	 if ( isset($_GET["showgames"]) AND $_GET["showgames"] == 200) $Total = 200; else
	 $Total = 50;
	 
	 $ord = 'DESC';
	 $label  = 'last';
	 if ( isset($_GET["first"]) ) { $ord = 'ASC'; $label  = 'first'; }
	 $id = (int) $_GET["u"];
	 $sth = $db->prepare("SELECT s.*, g.id, g.map, g.gamename, g.datetime, g.ownername, g.duration,  g.creatorname, dg.winner, 
	 g.gamestate  AS type, s.player, dp.kills, dp.deaths, dp.creepkills, dp.creepdenies, dp.assists, dp.hero, dp.neutralkills, dp.newcolour
	 FROM ".OSDB_STATS." as s 
	 LEFT JOIN ".OSDB_GP." as gp ON (gp.name) = (s.player)
	 LEFT JOIN ".OSDB_GAMES." as g ON g.id = gp.gameid
	 LEFT JOIN ".OSDB_DG." as dg ON g.id = dg.gameid 
	 LEFT JOIN ".OSDB_DP." as dp ON dp.gameid = dg.gameid AND gp.colour = dp.colour
	 WHERE s.id = :id AND (g.map) LIKE ('%".$MapString."%') AND g.duration>='".$MinDuration."' 
	 ORDER BY g.id ".$ord."
	 LIMIT $Total");
	 
	 $sth->bindValue(':id', $id, PDO::PARAM_INT); 
	 
	 $result = $sth->execute();
	 $numrows = $sth->rowCount(); 
	 $c=0;
     $ChartData = array();
	 $tempPoints = 0;
	 while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	 $ChartData[$c]["id"]        = (int)($row["id"]);
	 $ChartData[$c]["gamename"]  = ($row["gamename"]);
	 $ChartData[$c]["winner"]  = ($row["winner"]);
	if ( isset($row["newcolour"]) ) {
	$ChartData[$c]["newcolour"]  = ($row["newcolour"]);
	if ( $row["newcolour"] <=5  AND $row["winner"] == 1 )  $ChartData[$c]["win"]  = 1; else 
	if ( $row["newcolour"] >5   AND $row["winner"] == 1 )  $ChartData[$c]["win"]  = 2; else 
	if ( $row["newcolour"] >5   AND $row["winner"] == 2 )  $ChartData[$c]["win"]  = 1; else 
	if ( $row["newcolour"] <=5  AND $row["winner"] == 2 )  $ChartData[$c]["win"]  = 2; 
	} else $ChartData[$c]["newcolour"]  = 0;
	if ( $row["winner"] == 0 ) $ChartData[$c]["win"] = 0;
	
	if ( $ChartData[$c]["win"] == 1) $tempPoints++; else
	if ( $ChartData[$c]["win"] == 2) $tempPoints--; 
	
	$ChartData[$c]["ChartPoints"] = $tempPoints;
	$c++;
	 }
    
	/*
	$Plot = $c/2;
	if ( $Plot>=$tempPoints ) $Plot  = $tempPoints+1;
	*/
	$ChartData[0]["MaxChartPoints"] = $tempPoints;
	//aasort($ChartData, "id" );
	$Plot  = $tempPoints/2;
	
$cat = -1;
$Category = "'0', ";
$Data = "0, ";
foreach ( $ChartData as $Chart ) {
  $cat++;
  $Data.= "".$Chart["ChartPoints"].", ";
  $Category.= "'".$cat."', ";
}

  $Category = substr($Category, 0, strlen($Category)-2 );
  $Data = substr($Data, 0, strlen($Data)-2 );
?>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script type="text/javascript">
$(function () {
<?php 
//Fix JQuery conflict for bootstrap template
if ( OS_THEMES_DIR == "bootstrap" ) { ?>$.noConflict();<?php } 
?>
        $('#container').highcharts({
            chart: {
                type: 'line',
                marginRight: 100,
                marginBottom: 25
            },
            title: {
                text: '<?=strtoupper($UserData[0]["player"])?> games',
                x: -30 //center
            },
            subtitle: {
                text: 'Display <?=$label?> <?=$Total?> games',
                x: -30
            },
            xAxis: {
				categories: [<?=$Category?>]
            },
            yAxis: {
                title: {
                    text: '<?=$UserData[0]["player"]?> games',
					y: -20
                },
                plotLines: [{
                    value: <?=($Plot)?>,
                    width: 3,
                    color: '#808080'
                }]
            },
            tooltip: {
                valueSuffix: ' wins of {point.x}'
            },
            legend: {
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'top',
                x: 0,
                y: 100,
                borderWidth: 2
            },
            series: [
			   {
                name: '<?=$UserData[0]["player"]?>',
                data: [<?=$Data?>],
               }
			]
        });
    });
    

		</script>
<?php
   }
   
   function OS_ChartData() {
?>
<script src="<?=OS_HOME.OS_PLUGINS_DIR?>user_line_chart/highcharts.js"></script>
<script src="<?=OS_HOME.OS_PLUGINS_DIR?>user_line_chart/js/modules/exporting.js"></script>
<a name="chart"></a>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed padLeft padTop padBottom">
Display last: 
<a href="<?=OS_HOME?>?u=<?=(int)$_GET["u"]?>&amp;showgames=10#chart" class="menuButtons" >10</a> 
<a href="<?=OS_HOME?>?u=<?=(int)$_GET["u"]?>&amp;showgames=20#chart" class="menuButtons" >20</a> 
<a href="<?=OS_HOME?>?u=<?=(int)$_GET["u"]?>&amp;showgames=50#chart" class="menuButtons">50</a> 
<a href="<?=OS_HOME?>?u=<?=(int)$_GET["u"]?>&amp;showgames=100#chart" class="menuButtons">100</a> 
<a href="<?=OS_HOME?>?u=<?=(int)$_GET["u"]?>&amp;showgames=200#chart" class="menuButtons">200</a> 
games

<span style="float:right; padding-right:16px;">Display first: 
<a href="<?=OS_HOME?>?u=<?=(int)$_GET["u"]?>&amp;showgames=10&amp;first#chart" class="menuButtons" >10</a> 
<a href="<?=OS_HOME?>?u=<?=(int)$_GET["u"]?>&amp;showgames=20&amp;first#chart" class="menuButtons" >20</a> 
<a href="<?=OS_HOME?>?u=<?=(int)$_GET["u"]?>&amp;showgames=50&amp;first#chart" class="menuButtons">50</a> 
<a href="<?=OS_HOME?>?u=<?=(int)$_GET["u"]?>&amp;showgames=100&amp;first#chart" class="menuButtons">100</a> 
<a href="<?=OS_HOME?>?u=<?=(int)$_GET["u"]?>&amp;showgames=200&amp;first#chart" class="menuButtons">200</a> 
games
</span>
<div id="container" style="min-width: 400px; height: 400px; margin: 0 auto"></div>

	 </div>
    </div>
   </div>
  </div>
</div>
<?php
   }
   
}
?>
