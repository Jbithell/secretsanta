<?php
$debug = false;
if ($debug) {
  error_reporting(-1);
  ini_set('display_errors', 'On');
}
$error = "BEGIN DEBUG MESSAGE: ";

class MyDB extends SQLite3
{
  function __construct()
  {
     $this->open('database/secretsanta.db3');
  }
}
$db = new MyDB();
if(!$db){
  $error .= $db->lastErrorMsg();
} else {
  $error .= "Opened database successfully\n";
}


//BEGIN MAIN CODE
//Get User id
if (!isset($_GET['id'])) die("Hi there - it looks like you're not taking part in our Secret Santa - but thanks for stopping by anyway!");


$sql ="SELECT * FROM participants WHERE stringpass='" . SQLite3::escapeString($_GET['id']) . "' LIMIT 1;";
$ret = $db->query($sql);
$user = $ret->fetchArray(SQLITE3_ASSOC);
if (!$user) die("Sorry - we can't find your userid - please check the link you were given is correct!");
$error .= "Operation done successfully\n";


$sql ="SELECT * FROM assignments LEFT JOIN participants ON assignments.recieverid=participants.id WHERE assignments.giverid='" . $user['id'] . "' LIMIT 1;";
$ret = $db->query($sql);
$assignment = $ret->fetchArray(SQLITE3_ASSOC);
if (!$assignment) {
  $error .= "Creating Assignmnet";
  $sql ="SELECT id FROM participants WHERE id NOT IN (SELECT recieverid FROM assignments);";
  $ret = $db->query($sql);
  $possibleassignments = [];
  while($row = $ret->fetchArray(SQLITE3_ASSOC)) {
      if ($row["id"] == $user['id']) continue; //Don't assign them to themselves!
      $possibleassignments[] = $row["id"];
   }

   if (count($possibleassignments) < 1) die("Sorry - we don't have anyone to assign you to at the moment - please contact an administrator");


   $assignto = $possibleassignments[array_rand($possibleassignments)];
   $sql ="INSERT INTO `assignments`(`id`,`giverid`,`recieverid`) VALUES (NULL," . $user['id'] . "," . $assignto . ");";
   if (!$db->query($sql)) die("Sorry - we can't assign you to anyone right now - please try again later");
   $sql ="SELECT * FROM assignments LEFT JOIN participants ON assignments.recieverid=participants.id WHERE assignments.giverid='" . $user['id'] . "' LIMIT 1;";
   $ret = $db->query($sql);
   $assignment = $ret->fetchArray(SQLITE3_ASSOC);
   if (!$assignment) die("Sorry - we can't assign you to anyone right now due to a database error - please try again later");
}
$error .= "Operation done successfully\n";





//End of File
$db->close();
if ($debug) echo $error;
?>







<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha.5/css/bootstrap.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha.5/js/bootstrap.min.js"></script>
<title>Secret Santa</title>
<style>
* {
  box-sizing: border-box;
}

#wrapper {
  width: 400px;
  margin: 0 auto;
}

.envelope {
  width: 200px;
  height: 100px;
  margin: 130px auto 0;
  background: #ddd;
  box-shadow:
    0 0 1px rgba(0,0,0,0.5),
    0 1px 3px rgba(0,0,0,0.25);
  position: relative;
  perspective: 800px;
}
.envelope:after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 0;
  height: 0;
  border: 0 solid rgba(0,0,0,0.25);
  border-width: 45px 100px;
  border-top-color: transparent;
  z-index: 2;
}

.envelope .flap {
  position: absolute;
  width: 100%;
  height: 0;
  border: 0 solid transparent;
  border-width: 50px 100px;
  z-index: 3;
}
.envelope .flap.front {
  border-left-color: #eee;
  border-right-color: #eee;
  border-bottom-color: #ccc;
  z-index: 3;
}
.envelope .flap.front:after {
  content: '';
  width: 100%;
  height: 0;
  position: absolute;
  left: -100px;
  bottom: -50px;
  border: 0 solid transparent;
  border-width: 49px 100px;
  border-bottom-color: #eee;
}

.envelope .flap.top {
  border-top-width: 55px;
  border-top-color: #aaa;
  z-index: 3;
  animation-duration: 1s;
  animation-fill-mode: forwards;
  -webkit-transform-origin-y: top;
  transform-origin-y: top;
  perspective: 800;
  transform-style: preserve-3d;
}
.envelope.open .flap.top {
  animation-name: flip;
}
.envelope .flap.top:after {
  content: '';
  position: absolute;
  left: -100px; /* border-left-width */
  top: -55px; /* border-top-width */
  width: 100%;
  height: 0;
  border: 0 solid transparent;
  border-width: 54px 100px;
  border-top-color: #eee;
}

.envelope .letter {
  position: absolute;
  width: 194px;
  height: 84px;
  background: #fff;
  top: 8px;
  left: 3px;
  border: 1px solid #ccc;
  z-index: 1;
  animation-duration: 2s;
  animation-delay: 1.5s;
  animation-fill-mode: forwards;
  transform-style: preserve-3d;
}
.envelope.open .letter {
  animation-name: remove;
}
.envelope .letter:before,
.envelope .letter:after {
  content: '';
  position: absolute;
  width: 192px;
  height: 75%;
  left: -1px;
  background: #fff;
  border: 1px solid #ccc;
  animation-duration: 1s;
  animation-delay: 4s;
  animation-fill-mode: forwards;
  -webkit-transform-origin-y: top;
  transform-origin-y: top;
  transform-style: preserve-3d;
  transform: rotateX(10deg);
}
.envelope .letter:before {
  z-index: 1;
}
.envelope.open .letter:before {
  animation-name: fold-up;
}
.envelope .letter:after {
  animation-delay: 5s;
  animation-fill-mode: forwards;
  -webkit-transform-origin-y: bottom;
  transform-origin-y: bottom;
  transform: rotateX(-5deg);
  bottom: 0;
}
.envelope.open .letter:after {
  animation-name: fold-down;
}

@keyframes flip {
  100% {
    transform: rotateX(180deg);
    z-index: 1;
  }
}

@keyframes remove {
  50% {
    top: -120px;
  }
  100% {
    top: 8px;
    z-index: 3;
  }
}

@keyframes fold-up {
  from {
  }
  to {
    transform: rotateX(140deg);
  }
}

@keyframes fold-down {
  from {
  }
  to {
    transform: rotateX(-140deg);
  }
}

#reset {
  background: #eee;
  display: inline-block;
  margin-top: -100px;
  text-align: center;
  padding: 10px 20px;
  border: 1px solid #ddd;
  border-radius: 25px;
  background: linear-gradient(#eee, #ccc);
  color: #333;
  text-shadow: 0 1px 0 #fff;
  cursor: pointer;
  float: right;
}
#reset:hover {
  opacity: 0.8;
}
#reset:active {
  background: linear-gradient(#ccc, #eee);
}
</style>
<script>
$(document).ready(function() {
  $('#reset').click(function() {
    $('.envelope').removeClass('open');
    setTimeout(function() {
      $('.envelope').addClass('open');
    }, 500);
  });
});
</script>
<body style="padding: 20px;">
  <h1 style='font-family: "Gill Sans MT"; font-size: 24px; font-style: normal; font-variant: normal; font-weight: 500; line-height: 26.4px;'><?=$user['shortname']?>,</h1><h1 <h1 style='font-family: "Gill Sans MT"; font-size: 20px; font-style: normal; font-variant: normal; font-weight: 500; line-height: 22.4px;'>Your secret santa this year is...</h1>
  <div id="wrapper">
    <div class="envelope open">
      <div class="flap front"></div>
      <div class="flap top"></div>
      <div class="letter">
        <div id="lettercontent" style="padding: 10px; padding-top: 15px; overflow: hidden;">
          <center><h2><?=$assignment["shortname"]?></h2><i><?=$assignment["name"]?></i></center>
        </div>
      </div>
    </div>

  </div>
</body>
