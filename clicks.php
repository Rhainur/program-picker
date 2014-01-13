<?php
$start = microtime();

$mysqli = new mysqli("hostname", "username", "password", "db_name");

$q_result = $mysqli->query("SELECT path, COUNT(*) as clicks FROM internal_hits GROUP BY path ORDER BY length(path) DESC");

$result = $q_result->fetch_all(MYSQLI_ASSOC);

$final_data = array();

$pointer =& $final_data;

$sum = 0;

// The following section of code iterates through
// each level of clicks and generates the sum of
// all clicks "below" the current level.

foreach($result as $row){
  if($row['path'] == '#') // Empty/root node. Ignore.
    continue;
  $nodes = explode('/', substr($row['path'], 1));
  for($i = 0; $i < count($nodes); $i++){
    $currentKey = $nodes[$i];
    if(!isset($pointer[$currentKey])){
      if($i == (count($nodes)-1)){
        $sum += $row['clicks'];
        $pointer[$currentKey] = $row['clicks'];
      }else{
        $pointer[$currentKey] = array();
        $pointer =& $pointer[$currentKey];
      }
    }else{
      $pointer =& $pointer[$currentKey];
    }
  }

  $pointer =& $final_data;
}

if(isset($_GET['debug'])){
  print_r($result);
  print_r($final_data);
  die();
}
$mysqli->close();
?><!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">

  <title>Program Picker Click Stats</title>
  <meta name="description" content="A visualization of the choices people make while using the program picker">
  <meta name="author" content="Rohit Nair">
  <link rel="stylesheet" href="../css/style.css">
  <style>
  path{
    stroke: black;
    stroke-width: 0.5px;
    shape-rendering: geometricPrecision;
    transition: all 1s;
    -moz-transition: all 1s;
    -webkit-transition: all 1s;
    -o-transition: all 1s;
    cursor: pointer;
  }

  path:hover{
    fill: #222;
  }

  h1, h2{
    text-align: center;
  }

  svg{
    width: 850px;
    height: 850px;
    display: block;
    margin: 0 auto;
  }

  #hoverInfo{
    position: absolute;
    border-radius: 5px;
    border: 1px #333333 solid;
    background-color: rgba(255,255,255,0.8);
    padding: 0 10px;
    display: none;
  }
  </style>
  <script type="text/javascript">
    var pkBaseURL = (("https:" == document.location.protocol) ? "https://rohitnair.net/admin/piwik/" : "http://rohitnair.net/admin/piwik/");
    document.write(unescape("%3Cscript src='" + pkBaseURL + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
  </script>
  <script type="text/javascript">
    try {
      var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 1);
      piwikTracker.trackPageView();
      piwikTracker.enableLinkTracking();
    } catch( err ) {}
  </script>
</head>

<body>
    <div class="logo-container" style="width: 150px; height: 150px; margin: 0 auto">
      <a href="//www.rohitnair.net">
        <img class="logo" src="../img/logo.png" alt="Rohit Nair" />
      </a>
    </div>
    <h1>Program Picker Click Stats</h1>
    <h2><?php echo $sum ?> total clicks</h2>
    <div id="hoverInfo">
      <h3 id="breadcrumb">
          &nbsp;
      </h3>
      <h3 id="clicks">
          &nbsp;
      </h3>
    </div>
    <svg xmlns="http://www.w3.org/2000/svg" version="1.1">
    </svg>
  <script src="d3.v3.min.js"></script>
  <script>

  var data = <?php echo json_encode($final_data) . ';' ?>

  var colors = ['#263826', '#406155', '#7C9C71', '#DBC297'];

  function humanFriendly(text){
    text = text.replace(/_/g, ' ');
    // Capitalizes first letters of words
    return text.replace(/(?:^|\s)\S/g, function(a) { return a.toUpperCase(); });

  }

    function parseData(dataNode, breadCrumb){
        var sum = 0;
        for(item in dataNode){
            if(dataNode.hasOwnProperty(item)){
                if(typeof dataNode[item] === 'object'){   
                    var subCrumb;
                    if(breadCrumb.length > 0)
                        subCrumb = breadCrumb + ' > ' + humanFriendly(item);
                    else
                        subCrumb = humanFriendly(item);
                    sum += parseData(dataNode[item], subCrumb);
                }else{
                  dataNode[item] = parseInt(dataNode[item]);
                  sum += dataNode[item];
                }
            }
        }
        dataNode.sum = sum;
        dataNode.crumb = breadCrumb;
        return sum;
    }

  function generateArcs(dataNode, sectionRadius, sectionSize, sectionStart, depth){
    var sectionSum = 0;

    for(item in dataNode){
        if(dataNode.hasOwnProperty(item) && item != "sum" && item != "crumb"){
            if(typeof dataNode[item] === 'object'){
                var subSectionSize = sectionSize * dataNode[item].sum / dataNode.sum;
                var subSectionStart = sectionStart + sectionSum;
                sectionSum += subSectionSize;
                generateArcs(dataNode[item], sectionRadius + 100, subSectionSize, subSectionStart, depth+1);
            }
        }
    }

    drawSum = 0;
    for(item in dataNode){
        if(dataNode.hasOwnProperty(item) && item != "sum" && item != "crumb" ){
            sectionValue = typeof dataNode[item] === 'object'?dataNode[item].sum:dataNode[item];
            arcStartAngle = (sectionStart + (sectionSize * (drawSum/dataNode.sum))) * 2 * Math.PI;
            arcEndAngle = arcStartAngle + ((sectionSize * (sectionValue/dataNode.sum)) * 2 * Math.PI);
            drawSum += sectionValue;

            var sectionCrumb;
            if(dataNode[item].crumb !== 'undefined'){
              if(dataNode.crumb.length > 0){
                sectionCrumb = dataNode.crumb + ' > ' + humanFriendly(item);
              }else{
                sectionCrumb = humanFriendly(item);
              }
            }else{
              sectionCrumb = dataNode[item].crumb;
            }
            d3.select("svg").append("path")
                .attr("d", d3.svg.arc()
                    .innerRadius(sectionRadius)
                    .outerRadius(sectionRadius+100)
                    .startAngle(arcStartAngle)
                    .endAngle(arcEndAngle) ).attr("transform", "translate(425,425)")
                .attr("fill", colors[depth])
                .attr("breadcrumb", sectionCrumb)
                .attr("clicks", sectionValue)
                .attr("clicksPerc", Math.round((sectionValue/dataNode.sum)*10000)/100 )
                .attr("clicksPercAll", Math.round((sectionValue/<?php echo $sum ?>)*10000)/100 )
                .attr("href", '#' + sectionCrumb.toLowerCase().replace(/ > /g, '/').replace(/ /g, '_'))
                .on('mouseover', function(){
                    d3.select('#breadcrumb').text(d3.select(this).attr('breadcrumb'));
                    d3.select('#clicks').text(d3.select(this).attr('clicks') + ' clicks ( ' + d3.select(this).attr('clicksPerc') + '% of parent, ' + d3.select(this).attr('clicksPercAll') + '% of total )');
                    d3.select('#hoverInfo').style('display', "block");
                })
                .on('mouseout', function(){
                    d3.select('#hoverInfo').style('display', "none");
                })
                .on('click', function(){
                    window.open('http://www.rohitnair.net/pp/' + d3.select(this).attr("href"));
                });
        }
    }
  }

  parseData(data, "");
  generateArcs(data, 5, 1, 0, 0);
  d3.select('svg').on('mousemove', function(){
    d3.select('#hoverInfo').style({'top': (d3.event.pageY+10) + 'px', 'left': (d3.event.pageX+10) + 'px'});
  });
  </script>
  <div style="text-align: center; opacity: 0.5; font-size: 10px; padding-bottom: 10px">
    <?php
    $end = microtime();
    $creationtime = ($end - $start) / 1000;
    printf("Page created in %.7f seconds.", $creationtime);
    ?>
  </div>
</body>
</html>