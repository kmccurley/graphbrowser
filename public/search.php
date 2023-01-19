<?php
// This is used only for the autocomplete facility. It requires better optimization.
if (empty($_GET['q'])) {
  die('missing q');
}
require('db.php');
try {
  $options = [PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
              PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
              PDO::ATTR_EMULATE_PREPARES   => false];
  $db = new PDO("mysql:host=localhost;dbname=$database;charset=utf8mb4",
                $dbuser,
                $dbpstr,
                $options);
  $sql = 'SELECT id,name from graph where name like ? or lastname like ? limit 40';
  //echo $sql;
  $stmt = $db->prepare($sql);
  $pattern = $_GET['q'] . '%';
  $stmt->execute([$pattern, $pattern]);
  $results = $stmt->fetchAll();
  echo json_encode($results);
} catch (PDOException $pe) {
  print($pe->getMessage());
}
  
