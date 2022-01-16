<?php
// Given an ID, this returns a graph with all neighbors of the node with the given
// ID, along with edges between the neighbors of ID. The data structure is tailored for
// use with the vis.js library for graph layout, and consists of a list of nodes and
// a list of edges.
// * Nodes have id, label, title, and color.
// * edges have id, from, to, weight, color, and title. Note that the edges are undirected,
//   in spite of the name 'from'. Weights are the number of coauthored papers. ID is either
//   from-to or to-from, depending on whether from < to.

if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
  die('missing id');
}
require 'config.php';
require 'db.php';
try {
  $options = [PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
              PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
              PDO::ATTR_EMULATE_PREPARES   => false];
  $db = new PDO("mysql:host=localhost;dbname=$database;charset=utf8mb4",
                $dbuser,
                $dbpstr,
                $options);
  $sql = 'SELECT id,name,edges,LENGTH(edges) as len from graph where id=?';
  $stmt = $db->prepare($sql);
  $stmt->execute([$_GET['id']]);
  $center = $stmt->fetch();
  $title = strval($center['id']) . ': ' . $center['name'] . '(' . strval($center['len']/6) . ' '. $config['nodename'] .')';
  $nodes = array(array('id' => $_GET['id'],
                       'label' => $center['name'],
                       'title' => $title,
                       'color' => '#ffb0b1'));
  $weights = array();
  $nodelookup = array($_GET['id'] => $center['name']);
  // A map from id to edge object. ID is $from-$to where $from < $to
  $edges = array();
  for ($offset = 0; $offset < $center['len']; $offset += 6) {
    $values = unpack('Lid/vw', $center['edges'], $offset);
    $to = strval($values['id']);
    $nodelookup[$to] = 1;
    if ($to < $_GET['id']) {
      $id = $to . '-' . $_GET['id'];
    } else {
      $id = $_GET['id'] . '-' . $to;
    }
    array_push($weights, $values['w']);
    $edges[$id] = array('from' => $_GET['id'],
                        'to' => $to,
                        'id' => $id,
                        'weight' => $values['w']);
  }
  $neighbors = implode(',', array_values(array_column($edges, 'to')));
  $sql = "SELECT id,name,edges,LENGTH(edges) as len FROM graph where id in ($neighbors)";
  $stmt = $db->prepare($sql);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  foreach($rows as $neighbor) {
    $title = strval($neighbor['id']) . ':' . $neighbor['name'] . ' (' . strval($neighbor['len']/6) . ' ' . $config['nodename'] . ')';
    array_push($nodes, array('id' => strval($neighbor['id']),
                             'label' => $neighbor['name'],
                             'title'=> $title));
    $from = $neighbor['id'];
    $nodelookup[$from] = $neighbor['name'];
    for ($offset = 0; $offset < $neighbor['len']; $offset += 6) {
      $values = unpack('Lid/vw', $neighbor['edges'], $offset);
      $to = strval($values['id']);
      if (key_exists($to, $nodelookup)) {
        // We don't add the edge twice.
        if ($to < $from) {
          $id = $to . '-' . $from;
          array_push($weights, $values['w']);
          $edges[$id] =array('from' => $neighbor['id'],
                             'to' => $to,
                             'id' => $id,
                             'weight' => $values['w']);
        }
      }
    }
  }
  $maxweight = max($weights);
  foreach($edges as &$e) {
    $color = 'rgba(' . strval(intval(192 * $e['weight'] / $maxweight)) . ',128,128,.2)';
    $e['color'] = $color;
    $title = strval($e['weight']) . ': ' . $nodelookup[$e['from']] . ' - ' . $nodelookup[$e['to']];
    $e['title'] = $title;
  }
  $data = array('nodes' => $nodes,
                'edges' => array_values($edges));
  echo json_encode($data, JSON_PRETTY_PRINT);
} catch (PDOException $pe) {
  print($pe->getMessage());
}
  
