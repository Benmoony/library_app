<?php
	//The main page for any queries that the user will grab from the DB.
	//Needs more queries such as activities, whiteboard use.
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

    <!--scripts here are for the transfer select plugin-->
    <script src="./javascript/transfer-select-js/jquery.js"></script>
    <script src="./javascript/transfer-select-js/bootstrap-transfer.js"></script>


	<script src="./javascript/icons.js"></script>

    <link href="styles/bootstrap-transfer.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/layout.css" type="text/css" >
    <link rel="stylesheet" href="styles/format.css" type="text/css" >

    <!-- This script needs to be changed to period/not date after periods are enabled in the database-->
    <script type="text/javascript">
        var cur_selected_date;
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

                        console.log("got dates");
                        var json_object = JSON.parse(data);
                        var survey_select = document.getElementById('survey_id_select');

                        for(var i = 0; i < json_object.length; i++){
                            var obj = json_object[i];
                            surv_id = obj['survey_id'];
                            lay_id = obj['layout_id'];
                            var option = document.createElement('option');
                            option.value = surv_id;
                            option.innerHTML = "Survey: " + surv_id +" for Layout" + lay_id;
                            survey_select.appendChild(option);
                        }
                    }
                });

            });
        });
    </script>

    <!--script for enabling the bootstrapTransfer plugin-->
    <script type="text/javascript">
        $(function() {
            var t = $('#multi-select').bootstrapTransfer(
                {'target_id': 'multi-select-input',
                 'height': '15em',
                 'hilite_selection': true});

            t.populate([
                {value:"1", content:"Apple"},
                {value:"2", content:"Orange"},
                {value:"3", content:"Banana"},
                {value:"4", content:"Peach"},
                {value:"5", content:"Grapes"}
            ]);

        });
    </script>

</head>

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
                if (array_key_exists("username", $_SESSION)){
                    ?>
                    <h3 class="log-state"> Logged In: <?= $_SESSION["username"]?> </h3>
                    <?php
                }
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
                <select name="period" id="period-select">
                    <option value="0">Choose a Period</option>
                    <?php
                    //create php function to grab all existing period's from the database
                    //get_period_options();
                    ?>
                </select>
                <select name="in-floor" id="in_survey_select">
                    <!-- Options to be generated by php or javascript from selected period information-->
                    <!-- This will be built with a multiple select with checkboxes-->
                    <option value="0">Choose Survey's</option>
                </select>
                <input type="submit" name="submit-query" />
            </fieldset>
        </form>
                <?php
            }
        ?>

        <div id="multi-select"></div>

		<?php

    ?>
    </main>
    <footer>
        <p>Designed by Web App team</p>
        <p> &copy; Humboldt State University</p>
    </footer>
</body>
</html>
