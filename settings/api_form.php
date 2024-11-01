<?php
	
	/*
	The form that adds new faculty gets submitted each time whether or not there is anything new
	We use hidden input fields to make sure we keep all the old values
	*/
		$apikey = '';
		if($this->options && isset($this->options['apikey']) ) $apikey = $this->options['apikey'];
		echo "<label>NCBI API key:</label><input id='wp_pubmed_reflist_api' name='wp_pubmed_reflist_api' size='40' type='text' value='{$this->apikey}'  />";
