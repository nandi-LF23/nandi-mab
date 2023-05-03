<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js" integrity="sha384-xrRywqdh3PHs8keKZN+8zzc5TX0GRTLCcmivcbNJWm2rs5C8PRhcEn3czEjhAO9o" crossorigin="anonymous"></script>


<style>
#container {
	min-width: 310px;
	max-width: 800px;
	height: 400px;
	margin: 0 auto;
}
</style>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<?php

require_once('db.php');

$db = new db();


?>
<div style="padding-top:50px;padding-left:50px;">
<form method="get" action="graph.php">
<div class="row">

  <div class="col-md-4">

  <div class="form-group">
   
    <select name="quickselect" class="form-control selectpicker">
      <option>Select Range</option>
      <option value="1d">Last 24 Hours</option>
      <option value="1w">Last Week</option>
      <option value="2w">Last Two Weeks</option>
      <option value="1m">Last Month</option>
    </select>
  </div>

  </div>

  <div class="col-md-4">

    <div class="form-group">
     
      <select name="nodeid" class="form-control selectpicker">
        
        <option>Select field</option>
<?php

$sql = 'SELECT * FROM fields';
$stmt = $db->unprepared_query( $sql );
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($data as $row)
{
  
  echo '<option value="'.$row['node_id'].'" >'.$row['field_name'].'</option>';
}
?>

      </select>
    </div>

  </div>

  <div class="col-md-4">
   <div class="form-group">
      <input type="submit" value="Plot Graph" class="btn btn-primary"/>
    </div>
  </div>

</div>
</form>
<div id="container"></div>

<?php

$where = null;

switch($_GET['quickselect'])
{
  case '1d':$where = 'AND date_time >= NOW() - INTERVAL 1 DAY;';break;
  case '1w':$where = 'AND date_time >= NOW() - INTERVAL 7 DAY;';break;
  case '2w':$where = 'AND date_time >= NOW() - INTERVAL 14 DAY;';break;
  case '1m':$where = 'AND date_time >= NOW() - INTERVAL 30 DAY;';break;
}
?>

<script>
Highcharts.chart('container', {
  exporting: {
        buttons: {
            contextButton: {
                enabled: false
            },
            exportButton: {
                text: 'Export',
                // Use only the download related menu items from the default
                // context button
                menuItems: [
                    'downloadPNG',
                    'downloadJPEG',
                    'downloadPDF',
                    'downloadSVG'
                ]
            },
            printButton: {
                text: 'Print',
                onclick: function () {
                    this.print();
                }
            }
        }
    },
chart: {
      type: 'line',
      zoomType: 'x'
  },

title: {
    text: 'Soil Moisture'
},

xAxis: {
  title: {
        text: 'date & time'
    },
      categories: [
        <?php
      $sql = 'SELECT date_time FROM ac_usa WHERE probe_id=\''.$_GET['nodeid'].'\' '.$where;
      $stmt = $db->unprepared_query( $sql );
      $data = $stmt->fetchAll(PDO::FETCH_NUM);
      echo '\''.$data[0][0].'\'';
      array_shift($data);
      foreach ($data as $row)
        echo ',\''.$row[0].'\'';
      ?>
      ]

},

yAxis: {
    title: {
        text: 'Percentage'
    }
},
legend: {
    layout: 'vertical',
    align: 'right',
    verticalAlign: 'middle'
},

plotOptions: {
    series: {
        label: {
            connectorAllowed: false
        }
        
    }
},




series: [{
    name: '100mm',
    data: [
      <?php
      $sql = 'SELECT sm1 FROM ac_usa WHERE probe_id=\''.$_GET['nodeid'].'\' '.$where;
      
      $stmt = $db->unprepared_query( $sql );
      $data = $stmt->fetchAll(PDO::FETCH_NUM);
      echo $data[0][0];
      array_shift($data);
      foreach ($data as $row)
        echo ','.$row[0];
      ?>
    ]
}, {
  name: '200mm',
    data: [
      <?php
      $sql = 'SELECT sm3 FROM ac_usa WHERE probe_id=\''.$_GET['nodeid'].'\' '.$where;
      $stmt = $db->unprepared_query( $sql );
      $data = $stmt->fetchAll(PDO::FETCH_NUM);
      echo $data[0][0];
      array_shift($data);
      foreach ($data as $row)
        echo ','.$row[0];
      ?>
    ]
}, {
    name: '300mm',
    data: [
      <?php
      $sql = 'SELECT sm2 FROM ac_usa WHERE probe_id=\''.$_GET['nodeid'].'\' '.$where;
      $stmt = $db->unprepared_query( $sql );
      $data = $stmt->fetchAll(PDO::FETCH_NUM);
      echo $data[0][0];
      array_shift($data);
      foreach ($data as $row)
        echo ','.$row[0];
      ?>
    ]
}, {
    name: '400mm',
    data: [
      <?php
      $sql = 'SELECT sm4 FROM ac_usa WHERE probe_id=\''.$_GET['nodeid'].'\' '.$where;
      $stmt = $db->unprepared_query( $sql );
      $data = $stmt->fetchAll(PDO::FETCH_NUM);
      echo $data[0][0];
      array_shift($data);
      foreach ($data as $row)
        echo ','.$row[0];
      ?>
    ]
}, {
    name: '500mm',
    data: [
      <?php
      $sql = 'SELECT sm5 FROM ac_usa WHERE probe_id=\''.$_GET['nodeid'].'\' '.$where;
      $stmt = $db->unprepared_query( $sql );
      $data = $stmt->fetchAll(PDO::FETCH_NUM);
      echo $data[0][0];
      array_shift($data);
      foreach ($data as $row)
        echo ','.$row[0];
      ?>
    ]
}, {
    name: '600mm',
    data: [
      <?php
      $sql = 'SELECT sm6 FROM ac_usa WHERE probe_id=\''.$_GET['nodeid'].'\' '.$where;
      $stmt = $db->unprepared_query( $sql );
      $data = $stmt->fetchAll(PDO::FETCH_NUM);
      echo $data[0][0];
      array_shift($data);
      foreach ($data as $row)
        echo ','.$row[0];
      ?>
    ]
}],

responsive: {
    rules: [{
        condition: {
            maxWidth: 500
        },
        chartOptions: {
            legend: {
                layout: 'horizontal',
                align: 'center',
                verticalAlign: 'bottom'
            }
        }
    }]
}

});
</script>