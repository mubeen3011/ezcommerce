<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
<?php 
$file_get_content=file_get_contents("http://www.11street.my/totalsearch/TotalSearchAction/searchTotal.do?targetTab=T&isGnb=Y&prdType=&category=&cmd=&pageSize=60&lCtgrNo=0&mCtgrNo=0&sCtgrNo=0&ctgrType=&fromACK=&gnbTag=TO&schFrom=&tagetTabNm=T&aKwdTrcNo=&aUrl=&kwd=at620%2F14&callId=bd9811c8948dcbc831c");
echo $file_get_content;
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