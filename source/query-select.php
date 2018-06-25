<?php
	//Page to select what type of query you wish to perform
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
    <link rel="stylesheet" href="styles/layout.css" type="text/css" >
    <link rel="stylesheet" href="styles/format.css" type="text/css" >
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
        <h2>Welcome <?= $_SESSION["username"]?> please select a query type: </h2>
        <div id="nav_form">
            <button class="nav_button"  onclick="window.location = 'query-report.php'">Query Single Report</button>
            <button class="nav_button"  onclick="window.location ='query-range.php'">Query Range of Reports</button>
        </div>
    </main>
            <?php
        }
    ?>
    <footer>
        <p>Designed by HSU Library Web App team. &copy; Humboldt State University</p>
    </footer>
</body>
</html>
