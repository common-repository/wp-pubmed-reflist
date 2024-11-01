<?php
	/*
	Generate the forms for query editing
	*/

		# get the previous value returns false if empty
		$faclist = @$this->options['faclist'];
		$facprops = @$this->options['facprops'];
		#echo "<pre>options: \nfaclist\n"; var_dump($faclist);echo "facprops\n";var_dump($facprops);echo "</pre>"; 	
		$hiddens = '';	
		$formTable = "<table><tr><th>Search Key</th><th>Query</th><th>Extras</th><th>Delete?</th></tr>";
		if($faclist){
			sort($faclist);
			foreach($faclist as $name){
				# sanitize the faclist name
				$name = trim($name);
				$pattern = '/[^a-zA-Z0-9\s_\-\(\)]/';
				$sanitizedName = preg_replace($pattern, '_', $name);
				if($sanitizedName != $name){
					$name = $sanitizedName;
				/*	echo "$name contains characters not allowed in the key<br />";
					# delete it if it is already in the db.
					if(($key = array_search($name, $this->options['faclist'])) !== false) {
						unset($this->options['faclist'][$key]);
					}
					if(isset($facprops[$name]['query'])){
						$facprops[$sanitizedName]['query'] = $facprops[$name]['query'];
						unset ($facprops[$name]['query']);
					}							
					continue; */
				}
				if ($name == '' || (isset($facprops[$name]['delete']) && $facprops[$name]['delete'] == 'on')) continue;
				#$name = strtolower($name);
				$val = $extras = "";
				$formTable .= "<tr><td>$name</td>";
				# query
				if(isset($facprops[$name]['query'])) $val = $facprops[$name]['query'];
				$formTable .= "<td><textarea id='wp_pubmed_reflist_qlist' ".
				"name='wp_pubmed_reflist[facprops][$name][query]' 
				cols='60' rows='2' >$val</textarea></td>";
				# extra citations not in pubmed
				if(isset($facprops[$name]['extras'])) $extras = $facprops[$name]['extras'];
				$formTable .= "<td><textarea id='wp_pubmed_reflist_qlist' ".
				"name='wp_pubmed_reflist[facprops][$name][extras]' 
				cols='60' rows='2' >$extras</textarea></td>";
				# delete?
				$formTable .= "<td><input type='checkbox' id='wp_pubmed_reflist_qlist' ".
				"name='wp_pubmed_reflist[facprops][$name][delete]' /></td>
				<tr>";
				
				$hiddens .= "<input id='wp_pubmed_reflist_faclist' name='wp_pubmed_reflist[facprops][$name][last_update]' type='hidden' value='0' />\n";

			}
		}	
		$formTable .= "</table>";
		echo $formTable.$hiddens;
