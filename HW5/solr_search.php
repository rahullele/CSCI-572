<?php
ini_set('memory_limit', '-1'); 
include 'SpellCorrector.php';
include('simple_html_dom.php');
// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$query = trim($query);
$queryArray = explode(" ", $query);
$corrected_query = "";
foreach ($queryArray as $term) {
  $corrected_query .= SpellCorrector::correct($term);
  $corrected_query .= " ";
  # code...
}
$pagerank = isset($_REQUEST['sort']) ? true : false;
$results = false;
$spellch = $_REQUEST['spellcheck'] == "true" ? true : false;
//echo $_REQUEST['spellcheck'];
if($spellch)
{
  //$temp = $corrected_query;
  $corrected_query = $query;
  //query=$temp;
    //echo $corrected_query;
}
if ($query)
{
  // The Apache Solr Client library should be on the include path
  // which is usually most easily accomplished by placing in the
  // same directory as this script ( . or current directory is a default
  // php include path entry in the php.ini)
  require_once('Apache/Solr/Service.php');

  // create a new solr service instance - host, port, and webapp
  // path (all defaults in this example)
  $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');

  // if magic quotes is enabled then stripslashes will be needed
  if (get_magic_quotes_gpc() == 1)
  {
    $corrected_query = stripslashes($corrected_query);
  }

  // in production code you'll always want to use a try /catch for any
  // possible exceptions emitted  by searching (i.e. connection
  // problems or a query parsing error)
  try
  {
    $additionalParameters = array(
    'sort' => 'pageRankFile desc'
  );
  if($pagerank)
  {
    $results = $solr->search($corrected_query, 0, $limit,$additionalParameters);
  }
  else
  {
    $results = $solr->search($corrected_query, 0, $limit);
  }
    
  }
  catch (Exception $e)
  {
    // in production you'd probably log or email this error to an admin
    // and then show a special message to the user but for this example
    // we're going to show the full exception
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}

?>
<html>
  <head>
    <title>PHP Solr Client Example</title>
    <style>
    div { 
        display: block;
        font-size: 1em;
        margin-top: 0;
        margin-bottom: 0;
        margin-left: 0;
        margin-right: 0;
       
    }
    a {
    color: inherit;
    text-decoration: none;
    }

    a:hover 
    {
         color:red; 
         text-decoration:underline; 
         cursor:pointer;  
    }

    input[type=text], select {
    padding: 10px 15px;
    margin: 8px 0;
    display: inline-block;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
    }

    input[type=submit] {
      padding: 14px 20px;
      margin: 8px 0;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    body {
      margin-left: 25px;
    }

    


</style>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
$(document).ready(function() {


<?php
  echo "var cquery = '{$corrected_query}';";

 ?>

  $("#clink").click(function(){
    $("#spellcheck").val("true");
    $("#searchform").submit();
    $("#spellcheck").val("false");

  });


  $("#q").on("input", function(e) {
    var val = $(this).val().toLowerCase().trim();
    if(val === "") return;
    var space = (val.indexOf(" ")!=-1);
    var words;
    if(space) {
      words= val.split(" ");
      val= words[words.length-1];
      words.splice(words.length-1,1);
    }
    
    
    //You could use this to limit results
    //if(val.length < 3) return;
    // console.log(val);
    var regex = new RegExp("^[a-zA-Z]+$");
    $.ajax( {
          url: "http://localhost:8983/solr/myexample/suggest?indent=on&wt=json",
          dataType: "jsonp",
            jsonp : 'json.wrf',
          data: {
            q: val
          },  
          success: function( data ) {
            var dataList = $("#searchresults");
            dataList.empty();
            var result = data.suggest.suggest[Object.keys(data.suggest.suggest)[0]].suggestions
            if(result.length) {
              for(var i=0, len=result.length; i<len; i++) {
                if(regex.test(result[i].term))
                {  
                  if(space)
                  {
                    var opt = $("<option></option>").attr("value", words.join(" ") + " " + result[i].term);
                  }
                  else
                  {
                    var opt = $("<option></option>").attr("value", result[i].term);
                  }
                  dataList.append(opt);
                }
              }
            }
          }
        } );
   
   });
})
</script>
  </head>
  <body>
    <form accept-charset="utf-8" method="get" id="searchform">
      <input style="width:250px;" id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>" list="searchresults" placeholder="search something..." autocomplete="off"/>
      <datalist id="searchresults"></datalist>
      <input type="hidden" name="spellcheck" id="spellcheck" value="false">
      <input type="submit" value="Search"/>
      <input type="checkbox" value="true"<?php if($pagerank) echo "checked='checked'"; ?> name="sort"/><label for="pagerank">use pagerank</label>
    </form>

<br/>
<?php
  // echo SpellCorrector::correct($_REQUEST['q']);

// display results
if ($results)
{
  $total = (int) $results->response->numFound;
  $start = min(1, $total);
  $end = min($limit, $total);

  echo '<div> Results ' . $start. ' - ' . $end . ' of ' . $total. ': </div><br>';

  // iterate result documents
  if(strcmp(strtolower($query),strtolower(trim($corrected_query)))!=0)
  {
    echo '<div> Showing results for: <b>' . $corrected_query . ' </b></div>';

    echo '<div style="color:blue;"> Search instead for: <b><a href="#" id="clink" onclick="get_results('. $query .')">' . $query . ' </a></b>
  </div> <br>';
  }

  if(!$results->response->docs)
  {
    echo '<div> No Results Found. </div> <br>';
  }
  else
  {

    foreach ($results->response->docs as $doc)
    {
          if(!$doc->og_url || !$doc->og_title)
          {
            continue;
          }
          echo '<div class="search-result">';
          echo '<div style="color:blue;font-weight: bold;"><a target="_blank" href="'. $doc->og_url . '">' . $doc->og_title . '</a></div>';
          echo '<div style="color:green"><a href="'. $doc->og_url .'" target="_blank">' . $doc->og_url . '</a></div>';
          echo '<div>' . $doc->id . '</div>';
          echo '<div>' . $doc->og_description . '</div>';
      
          //echo $corrected_query;
          $foundSnippet = false; 
          if(strpos(strtolower($doc->og_description), strtolower(' ' . $corrected_query. ' ')) !== false) {
            
            $searchPat = "/" . ' ' . $corrected_query. ' ' . "/i";
            $replacePat = "<b>$0</b>";
            $foundSnippet=true;
            echo '<div>' . preg_replace($searchPat,$replacePat,$doc->og_description) . ' </div>'; 
            //echo '<div> ' . $doc->og_description . ' </div>';
          }
          elseif (strpos(strtolower($doc->og_title), strtolower(' ' . $corrected_query. ' ')) !== false) {
            
            $searchPat = "/" . ' ' . $corrected_query. ' ' . "/i";
            $replacePat = "<b>$0</b>";
            $foundSnippet=true;

            echo '<div>' .preg_replace($searchPat,$replacePat,$doc->og_title) . ' </div>'; 
          }
          
          else
          {


          $url_start = strrpos($doc->id, '/') +1;

          $url = substr($doc->id, $url_start);
            
          $html = file_get_html('HTML_files/'. $url);
          if($html) {
          $eles = $html->find('body');
          $flag=false;
          foreach($eles as $e) {
            $lines = $e->find('div'); 

            foreach($lines as $line) {

              if($flag)
              {
                break;
              }

              if(strpos(strtolower($line->plaintext), strtolower(' ' . $corrected_query. ' ')) !== false) {
                  // $snippet_start = strpos(strtolower($line->plaintext), strtolower($query));
                  // $str = substr($line->plaintext, $snippet_start-1, 200);
                  // //$str = $line->plaintext;
                  // // if (strlen($str) > 156)
                  // //   $str = substr($str, 0, 156) . '...';
                  // $searchPat = "/" . ' ' . $query. ' ' . "/i";
                  // $replacePat = "<b>$0</b>";
                   
                  // echo '<div> ... ' . preg_replace($searchPat,$replacePat,$str) . ' </div>'; 
                  $explodeQuery = "/ ".$corrected_query." /i";
                  list($startPart, $endPart) = preg_split($explodeQuery, $line->plaintext);
                  
                  $endSnip = substr($endPart, 0, 90);
                  $frontSnip = substr($startPart, -90, 90);

                  if (preg_match('/(&gt|&lt|\/|\{|\}|\[|\]|\%|>|<|=)/i', $endSnip) || preg_match('/(&gt|&lt|\/|\{|\}|\[|\]|\%|>|<|=)/i', $frontSnip)){
                          continue;
                  }
                     
                  
                  echo "...".$frontSnip."<b>"." ".$corrected_query." "."</b>".$endSnip."...";
                  $foundSnippet=true;
                  $flag = true;
              }
            }
          }
        }
        

        if(!$foundSnippet)
        {
            if(strpos($corrected_query," ") !== false) {
            $queryTerms = explode(" ", $corrected_query);
            $fflag=false;

            foreach($queryTerms as $term)
            {

              if($fflag)
              {
                break;
              }  

          if (strpos(strtolower($doc->og_title), strtolower(' ' .$term. ' ')) !== false) {
            # code...
            //echo "here"+$term;
            $searchPat = "/" . ' ' .$term. ' ' . "/i";
            $replacePat = "<b>$0</b>";

            echo '<div>' . preg_replace($searchPat,$replacePat,$doc->og_title) . ' </div>'; 
                  $fflag = true;

          }
          elseif(strpos(strtolower($doc->og_description), strtolower(' ' .$term. ' ')) !== false) {
            $searchPat = "/" . ' ' .$term. ' ' . "/i";
            $replacePat = "<b>$0</b>";

            echo '<div> ' . preg_replace($searchPat,$replacePat,$doc->og_description) . ' </div>'; 
                  $fflag = true;

          }
          else
          {


          $url_start = strrpos($doc->id, '/') +1;

          $url = substr($doc->id, $url_start);
          $html = file_get_html('HTML_files/'. $url);
          if($html) {
          $eles = $html->find('body');
          $flag=false;
          foreach($eles as $e) {
            $lines = $e->find('div'); 

            foreach($lines as $line) {

              if($flag)
              {
                break;
              }

              if(strpos(strtolower($line->plaintext), strtolower(' ' .$term. ' ')) !== false) {
                  // $snippet_start = strpos(strtolower($line->plaintext), strtolower($term));
                  // $str = substr($line->plaintext, $snippet_start-1, 200);
                  // //$str = $line->plaintext;
                  // // if (strlen($str) > 156)
                  // //   $str = substr($str, 0, 156) . '...';
                  // $searchPat = "/" . ' ' .$term. ' ' . "/i";
                  // $replacePat = "<b>$0</b>";

                  // echo '<div> ... ' . preg_replace($searchPat,$replacePat,$str) . '... </div>'; 
         
                  $explodeQry = "/ ".$term." /i";
                  list($startPart, $endPart) = preg_split($explodeQry, $line->plaintext);
                  
                  $endSnip = substr($endPart, 0, 90);
                  $frontSnip = substr($startPart, -90, 90);

                  if (preg_match('/(&gt|&lt|\/|\{|\}|\[|\]|\%|>|<|=)/i', $endSnip) || preg_match('/(&gt|&lt|\/|\{|\}|\[|\]|\%|>|<|=)/i', $frontSnip)){
                          continue;
                  }
                     
                  
                  echo "...".$frontSnip."<b>"." ".$term." "."</b>".$endSnip."...";
                  $flag = true;
                  $fflag = true;
              }
            }
          }
        }
        }
            }


          }
        }
      }
          echo '</div><br>';


    }
  }

}
?>
  </body>
</html>