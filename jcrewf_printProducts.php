<?php
	include('header.php');
  	include('simple_html_dom.php');

	$page = $_GET["page"];
	$type = $_GET["type"];
	
	echo "<h3>" . $type . "</h3>";
	echo "<a href=\"index.php\">back to index</a>";
	 
	getProducts($page, $type);
	function getProducts($page, $type) 
	{  
		// load page with cURL and simple HTML DOM
		$ch = curl_init($page);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		$curlOut = curl_exec($ch);
		$html = new simple_html_dom();  
		$html = str_get_html($curlOut);
		curl_close($ch);
		
		$items = $html->find('#arrayProdInfo');
		$images = $html->find('td.arrayImg a img');
		
		include('connection.php');

		// update mysql database
		$rowsChanged = 0;
		$rowsAdded = 0;
		
		for($j = 0; $j<count($items); $j++)
		{
			$result = $mysqli->query("SELECT * FROM `jcrew` WHERE prodImage = '".$images[$j]->src."'");
			$currPriceString = $items[$j]->children(1)->children(1)->children(0)->innertext;
			$currPrice = substr($currPriceString, strpos($currPriceString, '$')+1);
			
			if (!$result) 
			{
  				die($mysqli->error);
			}
			
			if ($result->num_rows > 0) 
			{
				$prevEntry = $result->fetch_array(MYSQLI_ASSOC);
				if($prevEntry["prodPrice"] > $currPrice)
				{
					mysqli_query($mysqli,"UPDATE `jcrew` SET `prodPrice`= '". $currPrice ."' WHERE prodImage = '".$images[$j]->src."'") 
					or die(mysqli_error($mysqli));
					$rowsChanged++;
				}
				
				if($prevEntry["currPrice"] != $currPrice)
				{
					mysqli_query($mysqli,"UPDATE `jcrew` SET `currPrice`= '". $currPrice ."' WHERE prodImage = '".$images[$j]->src."'") 
					or die(mysqli_error($mysqli));
					$rowsChanged++;
				}
			}
			
			else
			{
				mysqli_query($mysqli,"INSERT IGNORE INTO jcrew (prodImage, prodName, prodPrice, prodType, currPrice)
				VALUES ('". $images[$j]->src ."', '". str_replace("'"," ",$items[$j]->first_child()->first_child()->innertext) ."', 
						'". $currPrice ."',
						'". $type ."', '". $currPrice ."')") 
				or die(mysqli_error($mysqli));
				$rowsAdded++;
			}
			
			mysqli_free_result($result);
		}
		
		//echo "<p>Rows changed: " . $rowsChanged . "</p>";
		//echo "<p>Rows added: " . $rowsAdded . "</p>";
					
		/* ~~~outputting an HTML table ~~~*/
		
		// creating a matrix of database
		$result = $mysqli->query("SELECT * FROM `jcrew` WHERE prodType = '" . $type ."'");
		
		while($row = $result->fetch_array(MYSQLI_ASSOC))
		{
			$rows[] = $row;
		}
		
		mysqli_free_result($result);
		
		// drawing table
		$maxcols = 4;
		$i = 0;
	
		echo "<table class=\"prodArray\">";
		echo "<tr>";
		
		for($j = 0; $j<count($rows); $j++) 
		{
			if ($i == $maxcols) 
			{
				$i = 0;
				echo "</tr><tr>";
			}
			
			echo "<td class=\"prodCell\">";
			echo "<div class=\"prodImage\"<a href=\"". $rows[$j]["prodImage"] ."\"><img src=\"" . $rows[$j]["prodImage"] . "\" /></a></div>";
			echo "<div class=\"prodName\">" . $rows[$j]["prodName"] . "</div>";
			echo "<div class=\"currPrice\">Current Price: $" . number_format((float)$rows[$j]["currPrice"], 2, '.', '') . "</div>";
			echo "<div class=\"prodPrice\">Lowest Price: $" . number_format((float)$rows[$j]["prodPrice"], 2, '.', '') . "</div>";
			echo "</td>";
			
			$i++;  
		}
		
		$mysqli->close();
	}
	
	include('footer.php');
?>

