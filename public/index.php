<?php
require 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="autocomplete/auto-complete.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <title>Browse the <?php echo $corpus;?> collaboration graph</title>
    <style>
      #graph-container {
        width: 100%;
        height: 80vh;
        margin: 0;
        padding: 0;
        overflow: hidden;
      }
     /* context-menu is for right click */
     .context-menu {
       display: none;
       z-index: 1000;
       top: 25px;
       left: 100px;
       position: absolute;
       overflow: hidden;
       border: 1px solid #CCC;
       white-space: nowrap;
       font-family: sans-serif;
       background: #fffdd0;
       color: #333;
       border-radius: 5px;
       padding: 0;
     }

     /* Each of the items in the list */
     .context-menu li {
       padding: 8px 12px;
       cursor: pointer;
       list-style-type: none;
       transition: all .3s ease;
       user-select: none;
     }

     .context-menu li:not(:first-child):hover {
       background-color: #DEF;
     }
    </style>
  </head>
  <body>
    <ul id="node-menu" class="context-menu">
      <li><span id="nodename"></span></li>
      <li class="node-menu-item" id="menu-expand-node" data-target="">Expand node</li>
      <li class="node-menu-item" id="menu-delete-node" data-target="">Delete node</li>
      <li class="node-menu-item" id="menu-cluster-node" data-target="">Cluster neighbors</li>
    </ul>
    <div class="container">
      <div>
        <div>
          <h3>Browse the <?php echo $config['corpus'];?> collaboration graph</h3>
          <p><?php echo $config['description'];?> You can click on nodes to expand or contract or delete them.
            You can also enter an arbitrary starting name.
            Other graphs can be found
            <a href="https://cstheory.com/graphs/">here</a></p></p>
        </div>
      </div>
      <div class="row align-items-top justify-content-between">
        <div class="col-4">
          <div class="d-flex align-items-start">
            <div>              
              <input id="search" name="q" class="form-control form-control-sm" type="text" placeholder="add node">
              <label for="search">Enter a name to add a node.</label>
            </div>
            <button class="ms-3 btn btn-sm btn-warning" id="cleargraph">Clear graph</button>
          </div>
        </div>
        <div class="col-2">
          <input class="form-control form-control-sm border-red" id="weightFilter" type="number" min="1" value="1">
          <label for="weightFilter">Minimum edge weight to draw edge</label>
        </div>
        <div class="col-4">
          <span id="nodecount"></span><br><span id="edgecount"></span><br><span id="status"></span>
        </div>
      </div>
    </div>
    <div id="graph-container"></div>
    <script src="visjs/vis-network.js"></script>
    <script src="autocomplete/auto-complete.js"></script>
    <script src="https://iacr.org/libs/js/jquery/3.3.1/jquery.min.js"></script>
    <script>
     var xhr;
     var ac = new autoComplete({
       selector: 'input#search',
       minChars: 3,
       source: function(term, response) {
         console.log(term);
         try {xhr.abort(); } catch (e) {}
         xhr = $.getJSON('search.php', {q: term}, function(data) {
           response(data);
         });
       },
       delay: 500,
       renderItem: function(item, search) {
         search = search.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
         return '<div class="autocomplete-suggestion" data-id="' + item['id']+'" data-val="' + item['name']+'">' + item['name'] + '</div>';
       },
       onSelect: function(e, term, item) {
         addEdges(item.dataset.id);
       }
     });
    </script>
    <script>
     var nodes, edges, network, edgesView, container;
     container = document.getElementById('graph-container');
     var weightFilterSelector = document.getElementById('weightFilter')
     var menuDeleteNode = document.getElementById('menu-delete-node')
     var menuExpandNode = document.getElementById('menu-expand-node')
     var menuClusterNode = document.getElementById('menu-cluster-node')
     var edgeFilterValue = 0;

     function showStatus(val) {
        document.getElementById('status').innerHTML = val;
     }
     function updateCounts() {
       document.getElementById('nodecount').innerHTML = nodes.length + ' nodes';
       document.getElementById('edgecount').innerHTML = edges.length + ' edges';
     }
     function addEdges(id) {
       showStatus('Loading edges...');
       fetch('vis.php?id=' + id)
         .then(response => response.json())
         .then(data => {
           console.log('received data');
           console.dir(data);
           let existingNodes = nodes.distinct('id');
           let newNodes = [];
           data.nodes.forEach(v => {
             if (!existingNodes.includes(v.id)) {
               newNodes.push(v);
             }
           });
           if (newNodes.length) {
             nodes.add(newNodes);
           }
           let existingEdges = edges.distinct('id');
           let newEdges = []
           data.edges.forEach(e => {
             if (!existingEdges.includes(e.id)) {
               newEdges.push(e);
             }
           });
           console.log('new edges');
           console.dir(newEdges);
           if (newEdges.length) {
             edges.add(newEdges);
             updateCounts();
             network.redraw();
           }
           showStatus('');
           setTimeout(stopAndCenter, 5000); //  + newEdges.length);
         });
     }
     function edgeFilter(edge) {
       if (edge.weight >= edgeFilterValue) {
         return true;
       } else {
         return false;
       }
     };
     function stopAndCenter() {
       stopAnimation();         
       network.fit({animation: true});
     }
     function stopAnimation() {
       console.log('stopped');
       network.stopSimulation();
       showStatus('');
     }
     // David Naccache is 22059
     // Adi Shamir is 9730
     function loadGraph(id) {
       if (network) {
         network.destroy();
       }
       showStatus('Loading network...');
       fetch('vis.php?id=' + id)
         .then(response => response.json())
         .then(data => {
           console.dir(data);
           nodes = new vis.DataSet(data.nodes);
           edges = new vis.DataSet(data.edges);
           let container = document.getElementById("graph-container");
           var options = {
             nodes: {
               color: {
                 highlight: '#ffb0b1',
                 background: 'rgba(152,251,152,.3)'
               }
             },
             layout: {
               improvedLayout: true,
               clusterThreshold: 200
             },
             physics: {
               solver: "forceAtlas2Based",
               stabilization: {
                 enabled: true,
                 iterations: 1000,
                 updateInterval: 50,
                 onlyDynamicEdges: false,
                 fit: false
               }
             },
             edges: {
               width: 4,
               smooth: {
                 type: 'continuous',
                 forceDirection: 'none'
               }
             }
           };
           edgesView = new vis.DataView(edges, {filter: edgeFilter});
           network = new vis.Network(container, {edges: edgesView, nodes: nodes}, options);
           console.dir(network);
           network.on('selectNode', (opts) => {
             console.dir(opts);
           });
     <?php if (array_key_exists('extras', $config)) {
           foreach($config['extras'] as $nodeId) {
             echo "addEdges($nodeId);";
           }
         }
       ?>
             network.on('startStabilizing', (evt) => {
             showStatus('drawing graph');
           });
           network.on('stabilizationIterationsDone', (evt) => {
             showStatus('');
           });
//           network.on('oncontext', function(props) {
//             props.event.preventDefault();
//             console.log('oncontext');
//             console.dir(props);
//             console.dir(network.getNodeAt(props.pointer.DOM));
//           });
           // If someone clicks on a node, show the menu.
           network.on('click', function(props) {
             if (props.nodes && props.nodes.length) {
               let nodeId = props.nodes[0];
               let node = nodes.get(nodeId);
               if (node && 'title' in node) {
                 console.dir(node);
                 $('#nodename').text(node['title']);
               }
               document.querySelectorAll('.node-menu-item').forEach(function(item) {
                 item.setAttribute('data-target', nodeId);
               });
               $('#node-menu').css({display: 'block',
                                    top: props.event.center.y + 'px',
                                    left: container.offsetLeft + props.event.center.x + 'px'});
               //props.event.preventDefault();
             } else {
               $('#node-menu').hide();
             }
           });
           network.on('dragStart', function(params) {
             $('#node-menu').hide();
           });
           network.on('dragEnd', function (params) {
             console.dir(params);
             //             params.event.preventDefault();
             setTimeout(stopAnimation, 4);
             showStatus('');
           });
           setTimeout(stopAndCenter, 4000);
           updateCounts();
           showStatus('');
         });
     }
     document.getElementById('cleargraph').addEventListener('click', (e) => {
       console.log('clear network');
       nodes.clear();
       edges.clear();
       updateCounts();
     });
       weightFilterSelector.addEventListener('change', (e) => {
         edgeFilterValue = weightFilterSelector.value;
         console.log('weight filter change');
         edgesView.refresh();
       });
       menuExpandNode.addEventListener('click', (e) => {
         $('#node-menu').hide();
         console.dir(e);
         let nodeId = e.target.dataset.target;
         console.dir(nodeId);
         if (nodeId.startsWith('cluster')) {
           network.openCluster(nodeId);
           setTimeout(stopAndCenter, 4000);
         } else {
           nodes.update({'id': nodeId, color: {background: 'yellow'}}); //#ffb0b1'}});
           addEdges(nodeId);
         }
       });
       menuClusterNode.addEventListener('click', (e) => {
         $('#node-menu').hide();
         console.dir(e);
         let nodeId = e.target.dataset.target;
         console.dir(nodeId);
         let title = 'Cluster of clusters';
         let node = nodes.get(nodeId);
         if (node) {
           title = 'Neighbors of ' + node.title;
         }
         network.clusterByConnection(nodeId, {
           clusterNodeProperties: {
             shape: 'diamond',
             color: 'green',
             title: title
           }
         });
         setTimeout(stopAndCenter, 4000);
       });
       menuDeleteNode.addEventListener('click', (e) => {
         $('#node-menu').hide();
         let nodeId = e.target.dataset.target;
         if (nodeId.startsWith('cluster')) {
           if (nodeId in network.body.nodes) {
             console.dir(network.body.nodes[nodeId]);
             let nodesInCluster = network.getNodesInCluster(nodeId);
             console.dir(nodesInCluster);
             network.openCluster(nodeId);
             nodesInCluster.forEach((nid) => {
               nodes.remove(nid);
             });
           } else {
             console.log('not in nodes');
           }
         } else {
           nodes.remove(nodeId);
         }
         network.redraw();
         updateCounts();
         setTimeout(stopAndCenter, 4000);
       });
     loadGraph(<?php echo $config['start'];?>);
    </script>    
  </body>
</html>
