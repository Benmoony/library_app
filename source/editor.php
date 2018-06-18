<?php
	//In this file, the core structure of editing a layout is implemented.
    session_start();
	require_once('./config.php');
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <title> Layout Editor </title>
    <meta charset="utf-8" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <link rel="stylesheet" href="styles/layout.css" type="text/css" >
    <link rel="stylesheet" href="styles/format.css" type="text/css" >
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css"
   integrity="sha512-Rksm5RenBEKSKFjgI3a41vrjkw4EVPlJ3+OiI65vTjIdo9brlAacEuKOiQ5OFh7cOI1bkDwLqdLw3Zg0cRJAAQ=="
   crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js"
   integrity="sha512-/Nsx9X4HebavoBvEBuyp3I7od5tA0UzAxs+j83KgC8PU0kgB4XiK4Lfe4y4cgBtaRJQEIFCW+oC506aPT2L1zw=="
   crossorigin=""></script>
   <script src="./javascript/icons.js"></script>
   <script src="./javascript/submit_layout.js"></script>
   <script src="./javascript/layoutFunction.js"></script>
   <script src="./javascript/helpers.js"></script>
   <script src="./javascript/add-areas.js"></script>
   <script src="./javascript/leaflet.rotatedMarker.js"></script>
   <script type="text/javascript">
    $(function() {
        $("#nav_toggle").click(function(){
            $("nav").toggleClass("hidden");
            $("header").toggleClass("hidden");
            $("main").toggleClass("to-top");
            $("footer").toggleClass("hidden");
            $(".hide_nav").toggleClass("nav_open");
        })
    });
    </script>
</head>
<body>
    <button class="hide_nav" id="nav_toggle">&plus;</button>
    <header class="hidden">
        <img class="logo" src="images/hsu-wm.svg">
        <h1>Library Data Collector</h1>


    <?php
        if (array_key_exists("username", $_SESSION)){
            ?>
            <h3 class="log-state"> Logged In: <?= $_SESSION["username"]?> </h3>
            <?php
        }
    ?>
    </header>
    <?php
        if (!array_key_exists("username", $_SESSION)){
            ?>
            <p class="invalid-login"> Please first <a href="index.php">login</a> before accessing the app</p>
            <?php
        }
        else{
             ?>
            <nav class="hidden">
                <p class="nav"><a href="home.php">Home</a></p>
                <p class="nav"><a href="data-collection.php">Data Collection</a></p>
                <p class="nav"><a href="query-select.php">Query Report</a></p>
                <p class="nav selected"><a href="editor.php">Layout Creator</a></p>
                <p class="nav"><a href="logout.php">Logout</a></p>
            </nav>
            <main class="to-top">
                <form class="layout-selector" id="lay-select">
                    <fieldset>
                        <!-- Choose the floor to work from-->
                        <select name="floor-select">
                            <option value="default">Choose a Floor</option>
                            <option value=1>Floor 1</option>
                            <option value=2>Floor 2</option>
                            <option value=3>Floor 3</option>
                        </select>
						<button type="button" id="submit_floor">Select</button>
						</br></br>
						<!--select a piece of furniture to place -->
						<label>Select a piece of furniture:</label>
						</br>
						<select name="furniture-select" >
							<?php
								//get furniture types to populate dropdown for placing on map
								$dbh = new PDO($dbhost, $dbh_select_user, $dbh_select_pw);

								$fTypeSelectStmt = $dbh->prepare("SELECT * FROM furniture_type");
								$fTypeSelectStmt->execute();
								$furnitureTypes = $fTypeSelectStmt->fetchAll();

								foreach($furnitureTypes as $row) {
							?>
							<option value=<?= $row['furniture_type_id'] ?>> <?= $row['furniture_name'] ?> </option>
							<?php
								}
							?>
						</select>
						</br></br>

						<button type="button" id="getAreas" >Generate Areas</button>
						<button type="button" id="insertLayout" disabled="true">Insert Layout</button>
						<div class="loading">
							<img src="images/loadwheel.svg" id="load-image">
						</div>
					</fieldset>
                </form>
				<!--Create div for the popup -->
				<div id="popupHolder"><div id="popup"></div></div>
                <div id="mapid"></div>
                    <?php
                }
            ?>
                <footer class="footd hidden">
                    <p>Designed by HSU Library Web App team. &copy; Humboldt State University</p>
                </footer>
            </main>
    <script>
		//create map
        var mymap = L.map('mapid', {crs: L.CRS.Simple, minZoom: 0, maxZoom: 4});
		var furnitureLayer = L.layerGroup().addTo(mymap);
		var areaLayer = L.layerGroup().addTo(mymap);
		var drawnItems = new L.FeatureGroup();
        var bounds = [[0,0], [360,550]];
		mymap.fitBounds(bounds);

		//setup global variables
		var selected_marker;
		var selected_furn;
		var floor_image = "local";

		//container for furniture objects
        var furnMap = new Map();
		var mapKey = 0;

		//create a container for areas
		var areaMap = new Map();

		//floor image placed from dropdown selection
        var image;

		//define our furniture object here
		function Furniture(id,ftype, latlng, fname){
			this.id = id;
			this.fname = fname;
			this.marker;
			this.degreeOffset = 0;
			this.x = latlng.lng;
			this.y = latlng.lat;
			this.ftype = ftype;
		}

		var selectFloor = document.getElementById("submit_floor");
        selectFloor.onclick = function(){
            //remove old floor image and place newly selected floor image
            if( mymap.hasLayer(image)){
                mymap.removeLayer(image);
            }
            var form_info = document.getElementById("lay-select");
            floor_selection = form_info.elements.namedItem("floor-select").value;
			var floorIMGstr;

			switch(floor_selection){
				case "1": floorIMGstr = "floor1.svg";break
				case "2": floorIMGstr = "floor2.svg";break;
				case "3": floorIMGstr = "floor3.svg";break;
				default: floorIMGstr = "floor1.svg";break;
			}


            image = L.imageOverlay('./images/' + floorIMGstr, bounds).addTo(mymap);
        }

		//get areas and place over map
		var getAreas = document.getElementById("getAreas");
		getAreas.onclick = function(){
			//get areas for this floor
			//TODO: create new areas or select different areas/
			//currently, it associates floor number with layout to get areas from L1 for floor 1, 2 for floor 2, etc.

			//check if the areaMap has been populated already
			var mapPopulated = false;
			areaMap.forEach(function(value, key, map){
				mapPopulated = true;
			});
			//create areas if the map is empty
			if(!mapPopulated){
				createAreas(floor_selection);
			}
			insertLayout.disabled = false;
		}

		//Make sure all pieces of furniture are in areas before inserting a new layout.
		var insertLayout = document.getElementById("insertLayout");
		insertLayout.onclick = function(){
			var layoutReady = true;
			var outOfBoundsLatLng = [];
			//calculate the area each piece of furniture is in.
			furnMap.forEach(function(value, key, map){
				//get the x,y for each piece of furniture
				y = value.y;
				x = value.x;
				area_id="TBD";

				areaMap.forEach(function(jtem, key, mapObj){
					//check if x,y are in a polygon
					if(isMarkerInsidePolygon(y,x, jtem.polyArea)){
						area_id = jtem.area_id;
					}
				});
				if(area_id !== "TBD"){
					value.inArea = area_id;
				} else {
					layoutReady = false;
					outOfBoundsLatLng = [y,x];
				}
			});


			//check if the layout is ready to insert
			if(layoutReady){
				//var layoutName = prompt("Name this layout:");
				//alert(layoutName);
				var author = "<?= $_SESSION["username"]?>";
				submitLayout(author, floor_selection, furnMap, areaMap);
			}else{
				//layout not ready, alert the user and pan to last marker out of bounds.
				alert("Not all of your furniture is in an area, fix this before re-submitting!");
				mymap.panTo(outOfBoundsLatLng);
			}
		}

		//place a draggable marker onClick!
		function onMapClick(e) {
			//get the furniture select element
			var furn = document.getElementById("lay-select").elements.namedItem("furniture-select");
			//get the type id from the value
			var ftype = furn.value;
			//convert the string furniture type into an int to send to getIconObj(int ftype)
			ftype = parseInt(ftype);

			//do not allow user to place chairs or rooms yet.
			//Chairs are objects to attach to furniture.
			//Rooms require validating a room# and area before placing.
			if(ftype == 20 || ftype == 32){
				alert("Sorry, you can't place chairs or rooms yet. Contact your admin.");
			}else{
				//get the index of the selected item
				var findex = furn.selectedIndex;
				//get the options
				var furnOption = furn.options;
				//get the inner text of the selected furniture item to save the name.
				var fname = furnOption[findex].text;


				var selectedIcon = getIconObj(ftype);

				var latlng = e.latlng;

				//create the furniture object and store in map
				var newFurn = new Furniture(mapKey, ftype, latlng, fname);
				furnMap.set(mapKey, newFurn);
				if(document.getElementById("popup") == null){
						popupDiv = document.createElement("DIV");
						popupDiv.id = "popup";
						document.getElementById("popupHolder").appendChild(popupDiv);
				}

				var popup = document.getElementById("popup");
				var popupDim =
				{
					'minWidth': '200',
					'minHeight': '2000px',
				};//This is the dimensions for the popup

				marker = L.marker(e.latlng, {
						fid: mapKey++,
						icon: selectedIcon,
						rotationAngle: 0,
						draggable: true
				}).addTo(furnitureLayer).bindPopup(popup,popupDim);
				//give it an onclick function
				 marker.on('click', markerClick);

				//define drag events
				marker.on('drag', function(e) {
					console.log('marker drag event');
				});
				marker.on('dragstart', function(e) {
					console.log('marker dragstart event');
					mymap.off('click', onMapClick);
				});
				marker.on('dragend', function(e) {
					//update latlng for insert string
					var changedPos = e.target.getLatLng();
					var lat=changedPos.lat;
					var lng=changedPos.lng;

					selected_marker = this;
					selected_furn = furnMap.get(selected_marker.options.fid);
					selected_furn.x = lng;
					selected_furn.y = lat;

					//generate sql insert string for furniture
					//var insertString = getFurnitureString(lng,lat,degreeOffset, furniture+"_"+numSeats, "chair");
					//change popup to insertString
					//this.bindPopup(insertString);

					//output to console to check values
					console.log('marker dragend event');

					setTimeout(function() {
						mymap.on('click', onMapClick);
					}, 10);
				});
			}
		}

			//bind onMapClick function
		mymap.on('click', onMapClick);

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
			//marker = L.marker(e.latlng, {icon: couchFour }).addTo(furnitureLayer).bindPopup("I am a Computer Station.");
			$('#mapid .furnitureIcon').css({'width':newzoom,'height':newzoom});
			$('#mapid .furnitureLargeIcon').css({'width':newLargeZoom,'height':newLargeZoom});
		});

		function markerClick(e){
			//when a marker is clicked, it should be rotatable, and delete able
			selected_marker = this;
			selected_furn = furnMap.get(selected_marker.options.fid);
			//make sure the nameDiv is created and attached to popup
			if(document.getElementById("nameDiv") == null){
				var nameDiv = document.createElement("div");
				nameDiv.id = "nameDiv";
				document.getElementById("popup").appendChild(nameDiv);
			}
			//set the nameDiv to the name of the current furniture
			var nameDiv = document.getElementById("nameDiv");
			nameDiv.innerHTML = "<strong>Type: </strong>"+selected_furn.fname+"</br></br>";

			if(document.getElementById("deleteButtonDiv") == null) {
				//create a div to hold delete marker button
				var deleteButtonDiv = document.createElement("div");
				deleteButtonDiv.id = "deleteButtonDiv";
				//attach deleteButton div to popup
				document.getElementById("popup").appendChild(deleteButtonDiv);
				//create delete button
				var deleteMarkerButton = document.createElement("BUTTON");
				deleteMarkerButton.id = "deleteMarkerButton";
				deleteMarkerButton.innerHTML = "Delete";
				deleteMarkerButton.onclick = deleteHelper;
				//deleteMarkerButton.className = "deleteButton";
				//add the button to the div
				document.getElementById("deleteButtonDiv").appendChild(deleteMarkerButton);
			}

			//check if the rotateDiv has been made
			if(document.getElementById("rotateDiv") == null){
				//create a div to hold rotateButton
				var rotateDiv = document.createElement("div");
				rotateDiv.id = "rotateDiv";
				//attach the rotatebutton div to the popup
				document.getElementById("popup").appendChild(rotateDiv);
				rotateHelper("rotateDiv");
			}
		}

    </script>
</body>
</html>
