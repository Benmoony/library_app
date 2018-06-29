//Gets record information and sends queries for furniture and area which call populates to their maps.
function populateObjs(survey_id){
	$.ajax({
		url: 'phpcalls/record-from-survey.php',
		type: 'get',
		data:{ 'survey_id': survey_id },
		success: function(data){
			console.log("Retrieved survey record.");
			jsondata = JSON.parse(data);
			queryArea(jsondata.layout);
			queryFurniture(survey_id,jsondata.layout);
			loadMap(parseInt(jsondata.floor));
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			console.log("Status: " + textStatus);
			console.log("Error: " + errorThrown);
		}
	});
}

//query for furniture data
function queryFurniture(survey_id, layout_id){
	$.ajax({
		url: 'phpcalls/report-furniture-layout.php',
		type: 'get',
		data:{ 'survey_id': survey_id,
				'layout_id': layout_id},
		success: function(data){
			jsondata = JSON.parse(data);
			//console.log(jsondata);
			popFurnMap(jsondata);

		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			console.log("Status: " + textStatus);
			console.log("Error: " + errorThrown);
		}
	});

}

//query for area data
function queryArea(layout_id){
	$.ajax({
		url: 'phpcalls/area-from-survey.php',
		type: 'get',
		data:{ 'layout_id': layout_id },
		success: function(data){
			console.log("Retrieved areas.");
			jsondata = JSON.parse(data);
			popAreaMap(jsondata);

		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			console.log("Status: " + textStatus);
			console.log("Error: " + errorThrown);
		}
	});
}

//populate the furnMap
function popFurnMap(jsonFurniture){
	for(key in jsonFurniture){
		//retrieve furniture
		furn = jsonFurniture[key];

		//get activities for this furniture
		activities = [];
		jsonActivities = furn.activities;
		for(iter in jsonActivities){
			curAct = jsonActivities[iter];
			actCount = curAct[0];
			actName = curAct[1];
			tempAct = new Activity(actCount, actName);
			activities.push(tempAct);
		}
		cur_furn = new Furniture(furn.furniture_id,furn.num_seats, furn.x, furn.y, furn.degree_offset, furn.furn_type, furn.in_area, furn.occupants, activities);
		//if the original x&y are not equal to x&y, this furniture is modified
		if(furn.y != furn.original_y || furn.x != furn.original_x){
			cur_furn.modified = true;
			cur_furn.original_x = furn.original_x;
			cur_furn.original_y = furn.original_y;
			modified_furn++;
		}

		furnMap.set(furn.furniture_id, cur_furn);
	}
	addSurveyedFurniture();
	addSurveyedAreas();

	var queried_info_legend = L.control({position: 'bottomright'});

	queried_info_legend.onAdd = function (mymap){
		var div = L.DomUtil.create('div', 'report_legend');
		var header = document.createElement('p');
		var floor_percent = seatsUsed/totalSeats;
		floor_percent *= 100;
		floor_percent = floor_percent.toFixed(2);

		header.innerHTML ="Seats in use: " + seatsUsed +
					"</br>Possible seats: " + totalSeats +
					"</br> Floor % Use: " + floor_percent + "%";

		div.appendChild(header);

		//This is used for the printabe iFrame
		var report = document.getElementById("print_frame");
		var report_header = document.createElement("H2");
		var overview_header = document.createElement("H3");
		var report_text = document.createElement("P");
		var area_header = document.createElement("H3");
		var area_data = document.createElement("P");

		report_header.style.textAlign = "center";
		overview_header.style.textDecoration = "underline";
		area_header.style.textDecoration = "underline";

		report.contentDocument.body.appendChild(report_header);
		report.contentDocument.body.appendChild(overview_header);
		report.contentDocument.body.appendChild(report_text);
		report.contentDocument.body.appendChild(area_header);
		report.contentDocument.body.appendChild(area_data);

		report_header.innerHTML = print_header;
		overview_header.innerHTML = "Floor Overview:";
		report_text.innerHTML ="Seats in use: " + seatsUsed +
					"</br>Possible seats: " + totalSeats +
					"</br> Floor % Use: " + floor_percent + "%";
		area_header.innerHTML = "Area Overview:";
		area_data.innerHTML = area_string;

		if(modified_furn > 0){
			var mod_furn_header = document.createElement("H3");
			report.contentDocument.body.appendChild(mod_furn_header);
			mod_furn_header.style.textDecoration = "underline";
			mod_furn_header.innerHTML = "Modified Furniture: " + modified_furn; 
		}

		return div;
	}

	queried_info_legend.addTo(mymap);
	L.control.browserPrint({
		title: 'Library Query Report',
		documentTitle: 'Library Query Report',
		printLayer: L.tileLayer('https://stamen-tiles-{s}.a.ssl.fastly.net/watercolor/{z}/{x}/{y}.{ext}', {
			attribution: 'HSU Library App Team',
			minZoom: 1,
			maxZoom: 16,
			ext: 'png'
		}),
		closePopupsOnPrint: false,
		printModes: [
			L.control.browserPrint.mode.landscape(),
			"Portrait"
		],
		manualMode: false
	}).addTo(mymap);

	mymap.on("browser-print-start", function(e){
		/*on print start we already have a print map and we can create new control and add it to the print map to be able to print custom information */
		queried_info_legend.addTo(e.printMap);
	});

	mymap.on("browser-print-end", function(e){
		queried_info_legend.addTo(mymap);
	});

	/*var report = document.getElementById("print_frame");
	var report_text = document.createElement("P");

	report.contentDocument.body.appendChild(report_text);

	var title = document.createElement("TR");
	header.setAttribute("id", "title");
	document.getElementById("print_table").appendChild(title);

	var title_data = document.createElement("TD");
	var title_text = document.createTextNode()

	report_text.innerHTML = "Seats in use: " + seatsUsed +
				"</br>Possible seats: " + totalSeats +
				"</br> Floor % Use: " + floor_percent + "%";
	//report_data.appendChild(report_text);*/
}

//populate the areaMap
function popAreaMap(jsonAreas){
	for(key in jsonAreas){
		cur_area = jsonAreas[key];

		verts = [];
		for(i in cur_area.area_vertices){
			cur_verts = cur_area.area_vertices[i];

			newVert = new Verts(cur_verts.v_x , cur_verts.v_y, cur_verts.load_order);
			verts.push(newVert);
		}

		newArea = new Area(cur_area.area_id, verts, cur_area.area_name);
		areaMap.set(cur_area.area_id, newArea);
	}
}

//place floor map
function loadMap(floor){
	switch(floor){
		case 1:
			image = L.imageOverlay('images/floor1.svg', bounds).addTo(mymap);
			break;
		case 2:
			image = L.imageOverlay('images/floor2.svg', bounds).addTo(mymap);
			break;
		case 3:
			image = L.imageOverlay('images/floor3.svg', bounds).addTo(mymap);
			break;
	}

}

//iterate through furnMap adding all furniture to mymap
function addSurveyedFurniture(){
	furnMap.forEach(function(key, value, map){

		latlng = [key.y, key.x];
		ftype = parseInt(key.ftype)
		selectedIcon = getIconObj(ftype);
		degreeOffset = parseInt(key.degreeOffset);
		numSeats = key.numSeats;
		occupied = key.occupants;
		fid = key.fid;
		areaId = key.inArea;
		activities = key.activities;

		//add occupants & numseats to area totals
		curArea = areaMap.get(areaId);
		curArea.occupants += parseInt(occupied);
		curArea.seats += parseInt(numSeats);

		//if this furniture is modified, draw a line from its original latlng
		if(key.modified){
			originalLatLng = [key.original_y, key.original_x];
			pointList = [latlng, originalLatLng];

			polyline = new L.polyline(pointList, {
				color: 'red',
				weigth: 3,
				opacity: 0.5,
				smoothFactor: 1
			});

			polyline.addTo(mymap);
		}

		marker = L.marker(latlng, {
			icon: selectedIcon,
			rotationAngle: degreeOffset,
						rotationOrigin: "center",
			draggable: false,
			ftype: ftype,
			numSeats: numSeats,
			fid: fid.toString()
		}).addTo(furnitureLayer);
		//initialize the popupString for a regular piece of furniture
		popupString = "Seats occupied: " + occupied + " of " + numSeats;

		//set oppacity to a ratio of the seat use, minimum of 0.3 for visibility
		oppacity = 0.3 + occupied/numSeats;
		//default oppacity for rooms is 0.5 or 1 for rooms that are occupied
		if(numSeats === "0"){
			popupString = "Room occupants: " + occupied;
			if(occupied > 0){
				oppacity = 1;
			} else {
				oppacity = 0.5
			}
		}
		//add activities and their count to the popupString
		for(i in activities){
			popupString += "</br>"+activities[i].count +"X: " + activities[i].name;
		}

		marker.bindPopup(popupString);
		marker.setOpacity(oppacity);
		marker.addTo(mymap);
	});
}

//iterate through areaMap adding areas
function addSurveyedAreas(){
	areaMap.forEach(function(key, value, map){
		drawArea(key).addTo(mymap);
	});
}

//draw surveyed areas
function drawArea(area){
	var curVerts = [];
	var report = document.getElementById("print_frame");

	for(var i=0; i < area.verts.length; i++){
		area_verts = area.verts[i];
		curVerts.push([area_verts.x,area_verts.y]);
	}
	var poly = L.polygon(curVerts);
	var use_percent = area.occupants/area.seats;
	use_percent *= 100;
	use_percent = use_percent.toFixed(2);
	popupString = "<strong>"+area.area_name +"</strong></br>Average use: " + area.occupants + " of " + area.seats + " or " + use_percent + "%";
	if (use_percent > 10)
	{
		popupString = popupString.fontcolor("green");
		poly.setStyle({fillColor: 'green'});
	}

	else
	{
		popupString = popupString.fontcolor("red");
		poly.setStyle({fillColor: 'red'});
	}

	poly.bindPopup(popupString);

	if(area.seats === 0){
		area_string += "<strong>" + area.area_name + "</strong></br>Room use: " + area.occupants + "</br></br>";
	}

	else if(typeof area_string == 'undefined'){
		area_string = "<strong>" + area.area_name + "</strong></br>Average use: " + area.occupants + " of " + area.seats + " or " + use_percent + "%</br></br>";
	}

	else{
		area_string += "<strong>" + area.area_name + "</strong></br>Average use: " + area.occupants + " of " + area.seats + " or " + use_percent + "%</br></br>";
	}

	return poly;
}
