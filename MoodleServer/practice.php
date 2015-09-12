<?php

require_once("inc/session.php");

if ($_GET["pid"] > 0)
    $practiceid = $_GET["pid"];
else
    die();

$result = $mysqli->query("SELECT * from laboratoryPractice WHERE practiceid = {$practiceid}");
if ($result->num_rows == 1)
{
    $row 	       = $result->fetch_assoc();
    $practicelevel = $row[level];
    $practicetitle = $row[title];
    $practicedesc  = $row[description];
    $practicedate  = $row[practicedate];
}

if($isstudent)
{
    $result = $mysqli->query("SELECT * from studentRecord WHERE username = '{$myusername}' AND practiceid = {$practiceid} LIMIT 1");
    if ($result->num_rows == 1)
    {
        $row 	        = $result->fetch_assoc();
        $myscore        = $row[score];
        if($myscore >= 95)
            $output = "Very Good";
        if($myscore > 80 && $myscore < 95)
            $output = "Good";
        if($myscore >= 60 && $myscore < 80)
            $output = "Average";
        if($myscore < 60)
            $output = "Poor";
    }
}
else
{
    $result = $mysqli->query("SELECT * FROM studentRecord WHERE practiceid = {$practiceid}");
    if($result->num_rows != 0)
    {
        $i = 0;
        while($row = $result->fetch_array()) 
        {
            $usernames[$i]  = $row[username];
               
            $firstname[$i] = $DB->get_field('user', 'firstname', array('username' => $usernames[$i]));
            //echo 
            $lastname[$i] = $DB->get_field('user', 'lastname', array('username' => $usernames[$i]));
            $names[$i] = (!empty($lastname[$i])) ? "{$firstname[$i]} {$lastname[$i]}" : $firstname[$i];

            $scores[$i]     = $row[score];
            $i++;
        }
        $j = $i;
        $i = 0;
    }    
}

if($isstudent)
{
    $title = "{$myname}'s Online Practice Results";
}
if($isteacher)
{
    $title = "Online Practice Results";
}
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('incourse');
echo $OUTPUT->header();
?>
    <link rel="stylesheet" href="css/foundation.css" />
    <script src="js/vendor/modernizr.js"></script>
<?php
if($isstudent)
{
?>
<table width="100%">
  <thead>
    <tr>
      <th width="200">Data</th>
      <th>Details</th>
    </tr>
  </thead>
  <tbody>
    <tr>
        <td><? echo "{$practiceid}. {$practicetitle}" ?></td>
        <td><?php echo $practicedesc?></td>
    </tr>
    <tr>
      <td>Due date</td>
      <td><?php echo $practicedate?></td>
    </tr>
      
    <tr>
      <td>Name</td>
      <td><?php echo $myname?></td>
    </tr>
    <tr>
      <td>Score</td>
        <td><?php echo "{$myscore} ({$output})"?></td>
    </tr>
  </tbody>
</table>

<div class="progress">
    <span class="meter" style="width:<?php echo $myscore ?>%">
          <p class="percent"><?php echo $myscore ?>%</p>
    </span>
</div>


<?php
}
else
{
    if(!empty($practicetitle))
    {
?>
<table width="100%">
  <thead>
    <tr>
      <th width="300">Data</th>
      <th>Details</th>
    </tr>
  </thead>
  <tbody>
    <tr>
        <td><?php echo "{$practiceid}. {$practicetitle}" ?></td>
        <td><?php echo $practicedesc ?></td>
    </tr>
    <tr>
        <td>Due date</td>
        <td><?php echo $practicedate?></td>
    </tr>
  </tbody>
</table>
<table width="100%">
  <thead>
    <tr>
        <th width="200">Username</th>
        <th width="200">Name</th>
        <th>Score</th>
    </tr>
  </thead>
  <tbody>
<?php
    if($j == 0)
    {
?>
      <tr>
        <td colspan="3">No students have made the practice</td>
      </tr>
<?php
    }
    while($i < $j)
    {
?>
    <tr>
        <td><? echo $usernames[$i] ?></td>
        <td><? echo $names[$i] ?></td>
        <td><?php echo $scores[$i] ?></td>
    </tr>
<?php
        $i++;
    }
?>
  </tbody>
</table>
<?php
    }
    else
        echo "No data";
}
echo $OUTPUT->footer();


?>