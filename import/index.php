<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
</head>
<body style="background-color:#66FFB2;">
    <input type="text" value="0x000" placeholder="node id" id="node_id" style="padding:10px 25px 10px 25px;"/>
    <input type="button" value="Intellect" style="background-color:#55ACF7;color:white;padding:10px 25px 10px 25px;" id="intellect"/>
    <input type="button" value="GLDS" style="background-color:#55ACF7;color:white;padding:10px 25px 10px 25px;" id="glds"/>
    <input type="button" value="Vula" style="background-color:#55ACF7;color:white;padding:10px 25px 10px 25px;" id="vula"/>
    <input type="button" value="Graph" style="background-color:#55ACF7;color:white;padding:10px 25px 10px 25px;" id="graph"/>

    <script>

    document.getElementById('intellect').onclick = function(){
    $( "#data" ).load( "intellect.php?nodeid="+$('#node_id').val() );

    }

    document.getElementById('glds').onclick = function(){
    $( "#data" ).load( "glds.php?nodeid="+$('#node_id').val() );

    }

    document.getElementById('vula').onclick = function(){
    $( "#data" ).load( "vula.php?nodeid="+$('#node_id').val() );

    }

    document.getElementById('graph').onclick = function(){
    $( "#data" ).load( "graph.php" );

    }

    </script>


    <div id="data">

    </div>
</body>
