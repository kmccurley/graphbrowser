# Graph Browser

This contains code and data for browsing collaboration graphs (a kind of social network
graph). The code is broken into pieces:

1. data preparatation code under data/
2. app code is under public/, including code that runs on the server, but also
   client side code served through index.php. That subdirectory can be copied to
   a directory on a php-equipped web server. Configuration is stored in db.php.

In addition, data/ contains the original data for two databases, namely
lightnight and cryptodb. The data for dblp is too big.

The design of the database schema has changed since inception. Nodes are
assigned a non-negative integer ID up to 2^31 (this is a primary key in the
database).  Edges are assumed to have a non-negative integer weight of up to
2^16. Each row in the database consitutes a node with its edgelist (this already
means the database is at least twice the size of the graph).  The edgelist of a
node is packed into a MEDIUMBLOB. We pack an edge into 6 bytes, using 4 bytes for
the ID and 2 bytes for the weight.

Theoretically we could use 5 bytes per edge. The 40 bits would split up as 16
bits for weight and 24 bits for ID, but the space savings isn't worth the
complexity of code because using 6 bytes allows us to use the python and php
pack() utilities. For the DBLP graph, we have 5,941,535 total documents, but
only 2,815,566 documents with coauthors, so we need at least 22 bits for each
nodeID in an edge. For the arxiv graph, there are 2,005,097 documents, but only
1,268,920 have coauthors. Thus we also need at least 21 bits to store the
IDs. Note that some of the weights in arxiv are bigger than 1024, so we need at
least 12 bits to store the weights.
