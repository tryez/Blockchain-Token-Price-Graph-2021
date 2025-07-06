<?php

include 'config/index.php';


$tokens_query = "SELECT tokens.*, CONCAT(tokens.name, ' (', blockchains.name, ')') AS name_with_blockchain 
FROM tokens 
LEFT JOIN blockchains ON tokens.blockchain_id = blockchains.id
WHERE tokens.id = ?";

$item = DB::selectOne($tokens_query, [2]);

$item2 = DB::selectOne($tokens_query, [1]);

// $blockchain = DB::selectOne('SELECT * FROM blockchains WHERE id = ?', [$item['blockchain_id']]);

?>


<!DOCTYPE html>
<html>
<head>

<title>Uzna Token Graph</title>

<link rel="stylesheet" type="text/css" href="css/UznaTokenGraph.css">


</head>
<body>



<div style="display: flex; gap: 14px; justify-content: center; align-items: center">

  <div class="token-graph-renderer"></div>

  <div class="token-graph-renderer2"></div>

</div>




<script type="text/javascript" src="js/UznaTokenGraph.js"></script>

<script type="text/javascript">

const tokenId = <?php echo $item['id'] ?>;
const tokenName = "<?php echo $item['name_with_blockchain'] ?>";

let graph1 = new TokenGraph({
  name: tokenName,
  tokenId: tokenId,
  container: document.querySelector('.token-graph-renderer'),
  url: 'example_backend.php'
});




const tokenId2 = <?php echo $item2['id'] ?>;
const tokenName2 = "<?php echo $item2['name_with_blockchain'] ?>";

let graph2 = new TokenGraph({
  name: tokenName2,
  tokenId: tokenId2,
  container: document.querySelector('.token-graph-renderer2'),
  url: 'example_backend.php',
  filters: {
    exchange: 'pair',
    time: "all",
    basis: "base"
  }
});

</script>





<!-- <script type="text/javascript" src="js/tooltip.js"></script> -->

</body>
</html>