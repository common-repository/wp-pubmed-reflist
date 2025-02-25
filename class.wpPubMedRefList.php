<?php
class wpPubMedRefList{
	
	private $options = array();
	public $remote_method = '';
	private $eutilsURL = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils';

	function __construct(){
		$this->options = get_option('wp_pubmed_reflist');	
		if( ini_get('allow_url_fopen') ) {
   			$this->remote_method = 'allow_url_fopen';
		}elseif(function_exists('curl_version')){
		   	$this->remote_method = 'curl';
		}
	}
	
	/*
	key is the key to a particular query, e.g. a faculty name
	
	2015-07-21
	Add subset parameter for random subset > 1. Subset ONLY applies when random is set via negative limit.
	
	2015-01-25
	Current caching system is probably inefficient, since it caches the final html. This can be redundant if the same 
	query is using different formatting options. But that shouldn't happen too often.
	
	*/
	function wp_pubmed_reflist($key, $limit=50, $style, $wrap, $linktext, $subset = 1, $showlink){

		$debug = false;
		$html =  $msg = '';
		$this->query = $this->build_recursive_query($key);
		$this->query = str_replace("\n",' ', $this->query);
		$this->query = preg_replace('/\s+/','+', $this->query);
		# if we just want the pubmed link don't bother with doing a query
		$showlink = strtolower($showlink);
		if($showlink != 'link only'){
			$msg = "load from cache<br>";
			$cache_key = "$key.$limit.$style.$wrap";
			if(	$debug || !isset($this->options['facprops'][$cache_key]['last_update']) ){
				$elapsed = 0;
			}else{
				$elapsed = time() - $this->options['facprops'][$cache_key]['last_update'];
			}	
			if(	$debug 
				|| !isset($this->options['facprops'][$cache_key]['reflist']) 
				|| $elapsed > 60*60*24 
				#||true # uncomment for debugging
				){
				#do the update
				$msg = "updating from pubmed last update:".date("Y-m-d H:i:s", @$this->options['facprops'][$cache_key]['last_update']);
				$query_results = $this->reflist_query($key, $limit);
				$formatter = new wpPubMedReflistViews();
				$this->options['facprops'][$cache_key]['reflist'] = $formatter->format_refs($query_results, $style, $wrap, $limit, $subset);
			
				# update the timestamp array
				$this->options['facprops'][$cache_key]['last_update'] = time();

				# save the ref list to the options table
				update_option('wp_pubmed_reflist', $this->options);		
			}
			$html = $this->options['facprops'][$cache_key]['reflist'];
		}
		switch($showlink){
			case 'false':
			case 'no':
				break;
			default:
				$html .= "<a href='http://www.ncbi.nlm.nih.gov/pubmed?term=$this->query'>$linktext</a>";
		}
		return "<!-- $msg -->$html";
		
	}
	
	/*
	returns associative array
	$refs = array(
		pmid => array of citation objects
		extras => extra citations
	)
	
	Use negative limit to pick one random reference from a list of abs[$limit]
	*/
	function reflist_query($key, $limit){
		$query = $this->query;
		if ($query == "($key)") return "please enter a query for $key in the admin panel<br>";
		
		# Step 1: Call esearch to get a list of PMIDs
		$limit = abs($limit);
		$query = str_replace(' ','+',$query)."&retmax=$limit";
		$url = "$this->eutilsURL/esearch.fcgi?db=pubmed&term=$query";
		$xml = $this->fetchXML($url); 
		# extract PMIDs
		$id_list = array();
		if(isset($xml->IdList->Id)){
			foreach ($xml->IdList->Id as $pmid){ 
				$id_list[] = (string)$pmid;
			}
		}
		#Step 2 call efetch to get the actual citations
		$refs = array();
		$refs['pmid'] = array();
		if(!empty($id_list) ){
			$url = "$this->eutilsURL/efetch.fcgi?db=pubmed&id=".implode(',',$id_list)."&retmode=xml";
			$xml = $this->fetchXML($url);
		
			$i = 0;

			foreach ($xml->PubmedArticle as $article){
				if($i >= $limit) break;
				$i++;
				$p = new PMIDeFetch($article);
				$citation = $p->citation(); 
				$refs['pmid'][] = $citation;
			}
		}
		
		$refs['extras'] = $this->add_extras($key);
		return $refs;
		
		# make the list
		$html = "<ol>";
		$html .= "<li>".implode("</li>\n<li>",$refs)."</li>";
		$html .= "</ol>";
		return $html;
	}
	
	function add_extras($key){
		$extras_str = trim(@$this->options['facprops'][$key]['extras']);
		$extras = explode("\n", $extras_str);
		# array_filter trims out any line that returns false 
		# future todo: add callback
		return array_filter($extras);
	}
	/*
	# use || to build OR clauses
	
	Changed in version 0.6 to make this more robust. Use *key* to mark a substitution.
	*/
	function build_recursive_query($query){
		#echo "working on $query<hr>";
		#if the whole thing is a key, make that the new query
		if($this->getQueryFromOptions($query)){
			$new_query = $this->getQueryFromOptions($query);
		}else{
			$new_query = $query;
		}
		# do * substitutions on the whole query
		$recursion = false;
		preg_match_all('/\*(.*)\*/U', $new_query, $m);
	#	echo "<pre>".print_r($m, true)."</pre>";
		foreach($m[1] as $i => $rawKey){
			if($this->getQueryFromOptions($rawKey)){
				$new_query = str_replace("*$rawKey*",$this->getQueryFromOptions($rawKey),$new_query);
				$recursion = true;
			}
		}
		# do the || clauses
		$clauses = explode('||', $new_query);
		$qarr = array();
		foreach($clauses as $clause){
			$clause = trim($clause);
			if($this->getQueryFromOptions($clause)){
				$qarr[] = '('.$this->getQueryFromOptions($clause).')';
				$recursion = true;
			}else{
				$qarr[] = $clause;
			}	
		}
		$new_query = implode('+OR+', $qarr);
		if($recursion == true){ 
			$new_query = $this->build_recursive_query($new_query);
		}	
		#echo "$new_query<hr>";
		return $new_query;
	}
	
	function getQueryFromOptions($key){
		$key = trim($key, "\n ()");
		if(	
			is_array($this->options['facprops']) &&
			array_key_exists($key, $this->options['facprops']) && 
			isset($this->options['facprops'][$key]['query']) &&
			$this->options['facprops'][$key]['query'] != ''
			){
				return $this->options['facprops'][$key]['query'];
			}
			return false;
	}
	
	function fetchXML($url){
		$delay = 400000; # 0.4 seconds in microseconds
		$apikey = trim(get_option('wp_pubmed_reflist_api'));
		if(isset($apikey) && $apikey != ''){
			$delay = 100000; # 0.1 seconds in microseconds
			$url .= "&api_key=$apikey";
		}
		#trigger_error("url:$url");
		usleep($delay);
		switch($this->remote_method){
			case 'allow_url_fopen':
				$encoded_url = urlencode($url);
				#$xml = simplexml_load_file($encoded_url);
				$string = file_get_contents($url);
				break;
			case 'curl':
		  		$ch = curl_init($url);    
		 		curl_setopt  ($ch, CURLOPT_HEADER, false); 
		 		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    
		  		$string = curl_exec($ch);
				break;
		}		
		$string = self::cdatafy($string);
  		$xml = simplexml_load_string($string);
		return $xml;
	}

	/*
	Wrap content of ArticleTitle and AbstractText in CDATA sections to preserve HTML such as superscripts.
	*/
	static function cdatafy($string){
		$tags = array('ArticleTitle','AbstractText');
		foreach($tags as $tag){
			$string = preg_replace("/(<$tag.*>)(.*)(<\/$tag>)/U",'$1<![CDATA[$2]]>$3', $string);
		}
		return $string;
	}


}