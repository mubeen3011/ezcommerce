<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
<?php 
$file_get_content=file_get_contents("https://www.lazada.com.my/catalog/?spm=a2o4k.home.search.3.75f824f6CNZD2u&q=AT620/14&_keyori=ss&from=search_history&sugg=AT620%2F14_2_1");

?>
<body>
	<div style="display:none"> <?=$file_get_content?></div>
	<div id="showtable" >
</div>
</body>

<script>
		//console.log(window.pageData.mods.listItems);
		var html = '<table border=1><tr><th>Product Name</th><th>Seller Name</th><th>Product Id</th></tr>';
		$.each( window.pageData.mods.listItems,function(index,value){
			console.log(value);
			html += '<tr><td>'+value.name+'</td><td>'+value.sellerName+'</td><td>'+value.cheapest_sku+'</td></tr>';
		})
		$('#showtable').html(html);
</script>