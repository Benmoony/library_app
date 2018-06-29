<?php
	//The main page for any queries that the user will grab from the DB.
	//TODO: give a calendar view to choose the date of a survey record,
	//  Load the state of the library during that survey to give us not only area_use, but furniture location
	session_start();
    require_once('form_functions.php');
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <title> Library Query Report </title>
    <meta charset="utf-8" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	   <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css"
    integrity="sha512-Rksm5RenBEKSKFjgI3a41vrjkw4EVPlJ3+OiI65vTjIdo9brlAacEuKOiQ5OFh7cOI1bkDwLqdLw3Zg0cRJAAQ=="
    crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js"
    integrity="sha512-/Nsx9X4HebavoBvEBuyp3I7od5tA0UzAxs+j83KgC8PU0kgB4XiK4Lfe4y4cgBtaRJQEIFCW+oC506aPT2L1zw=="
    crossorigin=""></script>
		<script src="./javascript/report-objs-pop.js"></script>
		<script src="./javascript/leaflet.rotatedMarker.js"></script>
		<script src="./javascript/icons.js"></script>
		<script src="./javascript/leaflet.browser.print.min.js"></script>
    <link rel="stylesheet" href="styles/layout.css" type="text/css" >
    <link rel="stylesheet" href="styles/format.css" type="text/css" >

		<?php
		if (array_key_exists("username", $_SESSION)){
				?>
				<h3 class="log-state"> Logged In: <?= $_SESSION["username"]?> </h3>
				<?php
		}
		?>

</head>
<script type="text/javascript">
    var cur_selected_date;
		var json_object;
		var survey_info_legend = L.control();

    $(function(){
        $('#date-select').on("change", function(){
            var form_info = document.getElementById("choose_survey_form");
            cur_selected_date = form_info.elements["date-select"].value;

            //Get rid previous select options before repopulating
            var select = document.getElementById('survey_id_select');
            var length = select.options.length;
            if(length > 1){
                for(i = 0; i < length; i++){
                    select.remove(1);
                }
            }
			$.ajax({
                url: 'phpcalls/get-survey-ids.php',
                type: 'get',
                data:{ 'selected_date': cur_selected_date },
                success: function(data){

                    //console.log("got dates");
                    json_object = JSON.parse(data);
                    var survey_select = document.getElementById('survey_id_select');

                    for(var i = 0; i < json_object.length; i++){
                        var obj = json_object[i];
												//console.log(obj);
                        surv_id = obj['survey_id'];
                        lay_id = obj['layout_id'];
												floor_num = obj['floor'];
												surv_date_time = obj['survey_date'];
												surv_date_time_arr = surv_date_time.split(" ");
												surv_time = surv_date_time_arr[1];
                        var option = document.createElement('option');
                        option.value = surv_id;
                        option.innerHTML = "Survey: " + surv_id + " for Layout " + lay_id + " on floor " + floor_num + " at " + surv_time;
                        survey_select.appendChild(option);
                    }
                }
            });

        });
    });

		/*
		delete line: 149, 170
		*/
		function printReport(){
			var map = document.getElementById('mapid').innerHTML;
			var rFrame = document.getElementById('print_frame');
			rFrame.contentWindow.print();
		}
</script>
<body>
    <header>
        <img class="logo" src="images/hsu-wm.svg">
        <h1>Library Data Collector</h1>


        <?php
            if (!array_key_exists("username", $_SESSION)){
                ?>
                <p class="invalid-login"> Please first <a href="index.php">login</a> before accessing the app</p>
                <?php
            }
            else{
                 ?>
                <nav>
                    <p class="nav"><a href="home.php">Home</a></p>
                    <p class="nav"><a href="data-collection.php">Data Collection</a></p>
                    <p class="nav selected"><a href="query-select.php">Query Report</a></p>
                    <p class="nav"><a href="editor.php">Layout Creator</a></p>
                    <p class="nav"><a href="logout.php">Logout</a></p>
                </nav>
    </header>
    <main>

        <form class="report-selector" id="choose_survey_form">
            <fieldset>

							<div id="query_header_container">
								<h2 id="query_header"><?= $_SESSION["username"]?> what shall we query today? </h2>
							</div>
                <!--THIS IS A PLACEHOLDER! SELECT WILL BE POPULATED BY DATES FROM DB-->
                <select name="date" id="date-select">
                    <option value="0">Choose a Date</option>
                    <?php
                    get_dates_options();
                    ?>
                </select>
                <!--THIS IS A PLACEHOLDER! SELECT WILL BE POPULATED BY TIMES FROM DB-->
                <select name="survey_id" id="survey_id_select">
                    <option id="chosen_survey" value="">Choose a Survey</option>
                </select>

                <input type="submit" name="submit-query" id="query_submit_button"/>
								<input type="button" id="query_print_button" value="Print Report" style="display:none" onclick="printReport()"/>
            </fieldset>
        </form>
                <?php
            }
        ?>

			<div id="mapid"></div>
			<iframe id="print_frame">
				<html>
					<body>
					</body>
				</html>
			</iframe>
		<?php

		if (array_key_exists("survey_id", $_GET)){
			?>
			<script>

				//create maps and grab survey_id
				var survey_id = <?= $_GET["survey_id"] ?>;

				//used to keep track of all the seats on the floor and how many are being used
				var totalSeats = 0;
				var seatsUsed = 0;
				var print_header;
				var area_string;
				var modified_furn = 0;

				document.getElementById("query_print_button").style.display = "block";

				$.ajax({
	                url: 'phpcalls/get-survey-info.php',
	                type: 'get',
	                data:{ 'survey_id': survey_id},
	                success: function(data){
	                    //console.log("got dates");
	                  json_id = JSON.parse(data);

										//console.log(obj);
	                  lay_id = json_id[0].layout_id;
										floor_num = json_id[0].floor;
										surv_date_time = json_id[0].survey_date;
										surv_date_time_arr = surv_date_time.split(" ");
										surv_time = surv_date_time_arr[1];
										surv_date = surv_date_time_arr[0];
	                  var query_header = document.getElementById('query_header');
	                	query_header.style.display = "none";

										survey_info_legend.onAdd = function (mymap){
											var div = L.DomUtil.create('div', 'report_legend');
											var header = document.createElement('p');
											header.innerHTML = "Survey: " + survey_id + "</br>Layout: "
														+ lay_id + "</br>Floor: " + floor_num
														+ "</br> Time: " + surv_time + "</br> Date: " + surv_date;
											print_header = "Survey " + survey_id + " for Layout "
														+ lay_id + " on Floor " + floor_num
														+ " at " + surv_time + " on " + surv_date;

											div.appendChild(header);

											return div;
										}

										survey_info_legend.addTo(mymap);
	                }
	            });
				areaMap = new Map();
				furnMap = new Map();

				//define objects
				function Area(area_id, verts, area_name){
					this.area_id = area_id;
					this.verts = verts;
					this.area_name = area_name;
					this.occupants = 0;
					this.seats = 0;
				}

				function Verts(x, y, order){
					this.x = x;
					this.y = y;
					this.order = order;
				}

				function Furniture(fid, numSeats, x, y, degreeOffset, ftype, inArea, occupants, activities){
					this.fid = fid;
					this.numSeats = numSeats;
					this.x = x;
					this.y = y;
					this.degreeOffset = degreeOffset;
					this.ftype = ftype;
					this.inArea = inArea;
					this.occupants = occupants;
					this.activities = activities;
					totalSeats = +numSeats + +totalSeats;
					seatsUsed = +occupants + +seatsUsed;
				}

				function Activity(count, name){
					this.count = count;
					this.name = name;
				}

				//popuate furnMap and areaMap then place on map
				populateObjs(survey_id);


			//make map
			var mymap = L.map('mapid', {crs: L.CRS.Simple});
			var areaLayer = L.layerGroup().addTo(mymap);
			var furnitureLayer = L.layerGroup().addTo(mymap);
			var bounds = [[0,0], [360,550]];

			mymap.fitBounds(bounds);

			//On zoomend, resize the marker icons
        mymap.on('zoomend', function() {
            var markerSize;
            //resize the markers depending on zoomlevel so they appear to scale
            //zoom is limited to 0-4
            switch(mymap.getZoom()){
                case 0: markerSize= 5; break;
                case 1: markerSize= 10; break;
                case 2: markerSize= 20; break;
                case 3: markerSize= 40; break;
                case 4: markerSize= 80; break;
            }
            //alert(mymap.getZoom)());
            var newzoom = '' + (markerSize) +'px';
            var newLargeZoom = '' + (markerSize*2) +'px';
            $('#mapid .furnitureIcon').css({'width':newzoom,'height':newzoom});
            $('#mapid .furnitureLargeIcon').css({'width':newLargeZoom,'height':newLargeZoom});
        });

				mymap.on("browser-print-start", function(e){
					/*on print start we already have a print map and we can create new control and add it to the print map to be able to print custom information */
					survey_info_legend.addTo(e.printMap);
				});

				mymap.on("browser-print-end", function(e){
					survey_info_legend.addTo(mymap);
        });

			</script>
			<?php
		}
    ?>
    </main>
    <footer>
			<p>Designed by HSU Library Web App team. &copy; Humboldt State University</p>
    </footer>
</body>
</html>
