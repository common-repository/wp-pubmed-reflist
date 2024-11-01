<?php
	
	/*
	The form that adds new faculty gets submitted each time whether or not there is anything new
	We use hidden input fields to make sure we keep all the old values
	*/

		$faclist = array();
		$facprops = @$this->options['facprops'];
		if($this->options && isset($this->options['faclist']) ) $faclist = $this->options['faclist'];
		#$faclist = get_option('wp_pubmed_reflist_faclist');
		#echo "<pre>options: "; var_dump($this->options);echo "</pre>"; 	
		$i = 0;
		#sort($faclist);
		foreach ($faclist as $name){
			$name = strtolower($name);
			if ($name != "" && !(isset($facprops[$name]['delete']) && $facprops[$name]['delete'] == 'on')){
				echo "<input id='wp_pubmed_reflist_faclist' name='wp_pubmed_reflist[faclist][$i]' type='hidden' value='{$name}' />";
				$i++;
			}
		}		
		echo "Add new:<input id='wp_pubmed_reflist' name='wp_pubmed_reflist[faclist][$i]' size='40' type='text' value='' />";
	
