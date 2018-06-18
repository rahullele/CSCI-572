<?php

header('Content-Type:	text/html;	charset=utf-8');	
$limit	=	10;	
$query	=	isset($_REQUEST['q'])	?	$_REQUEST['q']	:	false;	
$results	=	false;
$count=0;
$csv = file('UrlToHtml_NBCNews.csv');

foreach($csv as $line) {
    $line = str_getcsv($line);
    $array["/Users/rahul/solr-7.2.1/example/exampledocs/crawl_data/".$line[0]] = trim($line[1]);
}
//var_dump($array);

if($query)	
{		require_once('Apache/Solr/Service.php');	
	
    
	$solr	=	new	Apache_Solr_Service('localhost', 8983, '/solr/myexample/');	
	
	if	(get_magic_quotes_gpc()	==	1)	
	{			
    $query	=	stripslashes($query);			}
    try	
	{		
        if($_GET['ranking']=='lucene')
        $results	=	$solr->search($query,0,	$limit);
        else
         $results	=	$solr->search($query,0,	$limit,$arrayName = array('sort' => 'pageRankFile desc'));   
    }	
	catch	(Exception	$e)	
	{		die("<html><head><title>SEARCH	EXCEPTION</title><body><pre>{$e->__toString()}</pre></
body></html>");				}					}	
?>	
<html> <head> <title>PHP Solr Client Example</title></head>			
<body>	
	<form	accept-charset="utf-8"	method="get">	
	<label	for="q">Search:</label>	
	<input	id="q"	name="q"	type="text"	value="<?php	echo htmlspecialchars($query, ENT_QUOTES,	
'utf-8'); ?>"/>	
        <input type="radio" name="ranking" <?php if (isset($_GET['ranking']) && $_GET['ranking']=="lucene") echo 'checked="checked"';?> value="lucene" checked/> Lucene
	<input type="radio" name="ranking" <?php if (isset($_GET['ranking']) && $_GET['ranking']=="pagerank") echo 'checked="checked"';?>value="pagerank" />PageRank <br/><br/> 
	<input	type="submit"/>	
	</form>	
<?php
//	display	results	
if($results)	
{		
$total	= (int) $results->response->numFound;	$start= min(1,$total);	
$end	=	min($limit,	$total); ?>	

	<div>Results <?php echo	$start;	?> - <?php	echo $end;?> of <?php echo $total;?>:</div>	
	<ol>	
    <?php	
	//	iterate	result	documents	
	foreach	($results->response->docs as $doc)	
	{		foreach($doc as $field => $value){
                if($field == "og_url"){
                        $link = $value; 
                } 
                if($field == "id"){
                        $id = $value; 
                } 
        } 
	?>			<li> 	
	<table	style="border:1px; text-align:left">	
<?php	
	//	iterate	document	fields	/	values	
	foreach	($doc	as	$field	=>	$value)	
	{			if($field=="og_url" || $field=="title" || $field=="og_description" || $field=="id" ){ $count++;	?>	
	<tr>	
	<th><?php	 if($field=="og_url") echo htmlspecialchars(URL,	ENT_NOQUOTES,	'utf-8');
                 else if($field=="og_description") echo htmlspecialchars(Description,	ENT_NOQUOTES,	'utf-8');
                 else echo htmlspecialchars($field,	ENT_NOQUOTES,	'utf-8');?></th>	
        <td><?php if($field=="og_url" || $field=="title"){ 
                      if($value!=""){?>
                      <a href = "<?php echo $array[$id] ; ?>"><?php echo $value;?></a>
                   
                         <?php }else{
                          if($field=="og_url")
                          echo htmlspecialchars($array[$id],	ENT_NOQUOTES,	'utf-8');
                          else
                              echo"N/A";
                      }
                      } 
                 else { 
                        if($value!="")
                        echo htmlspecialchars($value,	ENT_NOQUOTES,	'utf-8'); 
                        else echo"N/A"; 
                      }	
                            }?>
     </td></tr>
        
				<?php }
     if($count==3){
         ?>
            <tr>
                <th><?php echo htmlspecialchars("URL",	ENT_NOQUOTES,	'utf-8'); ?>
                </th>
                <td>
                    <a href = "<?php echo $array[$id] ; ?>"><?php echo $array[$id];?></a>
                </td>
            </tr><?php
     }
         $count=0;   		
     
        ?>	</table>			</li><br>
        
<?php				}				?>	
	</ol>	
<?php	
}			?>	</body></html>