<?php
define("SAVEROOT", "/home/rochi/Rec-adio");

$json = file_get_contents("../conf/config.json");
$config = json_decode($json, true);

define("DB_HOST", $config["mysql"]["hostname"]);
define("DB_PORT", $config["mysql"]["port"]);
define("DB_NAME", $config["mysql"]["database"]);
define("DB_USER", $config["mysql"]["username"]);
define("DB_PASSWORD", $config["mysql"]["password"]);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$options = array(PDO::MYSQL_ATTR_INIT_COMMAND=>"SET CHARACTER SET 'utf8'");

set_time_limit(0);
ini_set("memory_limit", "-1");
ini_set("display_errors", 1);
error_reporting(E_ALL);

try {
    $dbh = new PDO("mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME, DB_USER, DB_PASSWORD, $options);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if(isset($_GET["id"])) {
        $stmt = $dbh->prepare("UPDATE Programs SET `count` = `count` + 1 WHERE id = :id");
        $stmt->execute(array(":id" => $_GET["id"]));
        header('Location: ' . $_GET["url1"]."/".urlencode($_GET["url2"])."/".urlencode($_GET["url3"]) , true, 301);
    }
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/black/pace-theme-loading-bar.min.css" />
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

<!-- Tablesorter: required -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.2/css/theme.blue.min.css" rel="stylesheet">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.2/js/jquery.tablesorter.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.2/js/widgets/widget-filter.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.2/js/widgets/widget-storage.min.js"></script>

<!-- Grouping widget -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.2/css/widget.grouping.min.css" rel="stylesheet"> <!-- added v2.28.4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.2/js/parsers/parser-input-select.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.2/js/parsers/parser-date-weekday.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.tablesorter/2.31.2/js/widgets/widget-grouping.min.js"></script>

<script>
window.onpageshow = function(event) {
	if (event.persisted) {
		 window.location.reload();
	}
};

$(function() {

    $("#groups").tablesorter({
    sortList: [[0,0],[3,1]],
    theme : "blue",
    headers: {
        5: { sorter: false }
        // 7: defaults to "shortDate", but set to "weekday-index" ("group-date-weekday") or "time" ("group-date-time")
    },
    widgets: [ "group", "columns", "filter", "zebra" ],
    widgetOptions: {
        group_collapsible : true,  // make the group header clickable and collapse the rows below it.
        group_collapsed   : true, // start with all groups collapsed (if true)
        group_saveGroups  : false,  // remember collapsed groups
        group_saveReset   : '.group_reset', // element to clear saved collapsed groups
        group_count       : " ({num})", // if not false, the "{num}" string is replaced with the number of rows in the group

        // apply the grouping widget only to selected column
        group_forceColumn : [0],   // only the first value is used; set as an array for future expansion
        group_enforceSort : true, // only apply group_forceColumn when a sort is applied to the table

        group_callback    : function($cell, $rows, column, table) {
        // callback allowing modification of the group header labels
        // $cell = current table cell (containing group header cells ".group-name" & ".group-count"
        // $rows = all of the table rows for the current group; table = current table (DOM)
        // column = current column being sorted/grouped
        if (column === 2) {
            var subtotal = 0;
            $rows.each(function() {
            subtotal += parseFloat( $(this).find("td").eq(column).text() );
            });
            $cell.find(".group-count").append("; subtotal: " + subtotal );
        }
        },
        // event triggered on the table when the grouping widget has finished work
        group_complete    : "groupingComplete"
    }
    });
});
</script>
<style type="text/css">
.t, .f {font-weight: bold;}
.d, .b, .c, .s {font-weight: bold; text-align: center;}
</style>
<title>Listen - Rec-adio</title><meta name="ROBOTS" content="NOINDEX,NOFOLLOW,NOARCHIVE" /></head>
<body>
    <div class="center">
        <h1>Listen Rec-adio</h1>
        <table id="groups">
            <thead><tr><th class="group-text">Title</th><th>File</th><th>Station</th><th>Date</th><th>Count</th><th>Play</th></tr></thead>
            <tbody>
<?php
try {
    $sql = "SELECT * FROM Programs WHERE path IS NOT NULL";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
} catch (PDOException $e) {
    echo $e->getMessage();
    exit;
}

while($res = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $file = explode("/", str_replace(SAVEROOT."/savefile/", "", $res["path"]));
    echo "<tr>"
    ."<td class=\"t\">".$res["title"]."</td>"
    ."<td class=\"f\">".$file[1]."</td>"
    ."<td class=\"s\">".$res["station"]."</td>"
    ."<td class=\"d\">".date("Y/m/d H:i", strtotime($res["rec-timestamp"]))."</td>"
    ."<td class=\"c\">".$res["count"]."</td>"
    ."<td class=\"b\"><a href=\"index.php?id=".$res["id"]
    ."&url1=./savefile"
    ."&url2=".urlencode($file[0])
    ."&url3=".urlencode($file[1])
    ."&tmp=".uniqid()."\">"
    ."<i class=\"material-icons\">play_circle_filled</i></td>"
    ."</a></tr>\n";
}
?>
            </tbody>
        </table>
        
    </div>
</body></html>