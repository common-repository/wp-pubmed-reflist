<?php
/*
this file added to version 0.11 to make OO and to collapse options to a single array

Based on 
	http://codex.wordpress.org/Creating_Options_Pages
and	
	Otto's WordPress Settings API Tutorial
	http://ottopress.com/2009/wordpress-settings-api-tutorial/

revised structure for the options array as a single wp-options property 

	array(
		faclist => array(),
		facprops=> array(
					facname => array(
								query, 
								reflist => string
								extras => string #todo
								last_update => string/timestamp
								
				)
	
	)
	
*/

# calling the function to create the object defers construction until the rest of WP codex has loaded.
add_action('admin_menu', 'pubmed_refs_admin_add_page');

function pubmed_refs_admin_add_page() {
	$wpPubMedRefListSettingsPage = new wpPubMedRefListSettings;
	/*
	pre 0.7 Add an item to the Settings menu on the admin page
	
	add_options_page($page_title, $menu_title, $capability, $menu_slug, $function);
	menu slug is a url-friendly version of the menu name. Used as the "page name" in other functions.
	
	post 0.7 Add an item to the Tools menu so Editors can see it
	add_management_page( $page_title, $menu_title, $capability, $menu_slug, $function );
	
	note that the wpPubMedRefListSettings constructor also modifies permissions to allow Editors to save.
	*/

	add_management_page(
		'WP PubMed Reflist', 	# page title
		'WP PubMed Reflist', 	# menu title
		'edit_posts',    	#capability
		'wp_pubmed_reflist', 	#menu_slug
		array($wpPubMedRefListSettingsPage, 'options_page') # callback function
	);
}

class wpPubMedRefListSettings{

	private $options = array();
	
	function __construct(){
		# set callback for initializing admin pages
		add_action('admin_init', array($this, 'admin_init'));
		# Allow Editor Role to edit settings
		update_user_option( get_current_user_id(), 'manage_options', true, false );	
	}

	/*
	Initalization: define form sections and callbacks.
	
	add_settings_section( $id, $title, $callback, $page )
	add_settings_field( $id, $title, $callback, $page, $section, $args )
	
	*/
	
	function admin_init(){
		$this->options = get_option('wp_pubmed_reflist');
		$this->formats = get_option('wp_pubmed_reflist_styles');
		$this->apikey = get_option('wp_pubmed_reflist_api');
		# stylelist will be empty on first install or upgrade from < 0.9; initialize to squelch warning in set_default_styles
		# if the styles don't exist set the default ones here so they are available even if the user never hits the styles tab.
		if(!isset($this->formats['stylelist'])){
			$this->formats['stylelist'] = array();
			$this->set_default_styles();
		}
		# set default style to NIH if it isn't set
		if(!isset($this->formats['default_style'])){
			$this->formats['default_style'] = 'NIH';
			update_option('wp_pubmed_reflist_styles', $this->formats);		
		}
		/*
		Registry for query managment tab
		*/
		register_setting( 
			'pubmed_refs_options',                   # option group to be called by settings_fields() in form generation
			'wp_pubmed_reflist',                     # option name
			array($this, 'pubmed_queries_validate')  # sanitize callback
			);
		# add new section for adding keys
		add_settings_section(
			'wp_pubmed_reflist_faclist',        	# id  	
			'New key', 								# title
			'wpPubMedReflistViews::faclist_text', 	# callback
			'wp_pubmed_reflist'						# page
			);
		add_settings_field(
			'wp_pubmed_reflist_faclist', 
			'', 
			array($this, 'faclist_form'), 
			'wp_pubmed_reflist', 
			'wp_pubmed_reflist_faclist'
			);
		# edit queries section
		add_settings_section(
			'wp_pubmed_reflist_qlist',      	# id
			'Queries',                       	# title
			'wpPubMedReflistViews::query_form_text',	# callback 
			'wp_pubmed_reflist'               	# page
			);
		add_settings_field(
			'wp_pubmed_reflist_qlist',        	# id
			'',                              	# title
			array($this,'query_form'),        	# callback
			'wp_pubmed_reflist',             	# page
			'wp_pubmed_reflist_qlist'         	# section
			);
	
		/*
		Registry for styles managment tab
		*/
	
		register_setting( 
			'pubmed_ref_styles',                    	# option group
			'wp_pubmed_reflist_styles',                	# option name
			array($this, 'pubmed_formats_validate')    	# sanitize callback
			);
		# edit formats
		add_settings_section(
			'wp_pubmed_styles',      	# id
			'Add style',                       	# title
			'wpPubMedReflistViews::styles_form_text',	# callback 
			'wp_pubmed_reflist_styles'               	# page
			);
		add_settings_field(
			'wp_pubmed_reflist_faclist',      	# id
			'',                              	# title
			array($this, 'styles_form'),      	# callback
			'wp_pubmed_reflist_styles',        	# page
			'wp_pubmed_styles'              	# section	 
			);
		add_settings_section(
			'wp_pubmed_style_data_section',      	# id
			'Style data',                       	# title
			'wpPubMedReflistViews::styles_data_form_text',	# callback 
			'wp_pubmed_reflist_styles'               	# page
			);
		add_settings_field(
			'wp_pubmed_style_data',         	# id
			'',                              	# title
			array($this,'style_props_form'),   	# callback
			'wp_pubmed_reflist_styles',        	# page
			'wp_pubmed_style_data_section'              	# section
			);
		add_settings_section(
			'wp_pubmed_wrappers',      	# id
			'Bibliography format',                       	# title
			'wpPubMedReflistViews::styles_wrapper_text',	# callback 
			'wp_pubmed_reflist_styles'               	# page
			);
		add_settings_field(
			'wp_pubmed_wrappers',         	# id
			'',                              	# title
			array($this,'wrapper_form'),   	# callback
			'wp_pubmed_reflist_styles',        	# page
			'wp_pubmed_wrappers'              	# section
			);
		add_settings_section(
			'wp_pubmed_itals',      	# id
			'Italicized words',                       	# title
			'wpPubMedReflistViews::styles_ital_form_text',	# callback 
			'wp_pubmed_reflist_styles'               	# page
			);
		add_settings_field(
			'wp_pubmed_style_italicize',         	# id
			'',                              	# title
			array($this,'styles_ital_form'),   	# callback
			'wp_pubmed_reflist_styles',        	# page
			'wp_pubmed_itals'              	# section
			);
		add_settings_section(
			'wp_pubmed_bolds',      	# id
			'Highlighted words',                       	# title
			'wpPubMedReflistViews::styles_bold_form_text',	# callback 
			'wp_pubmed_reflist_styles'               	# page
			);
		add_settings_field(
			'wp_pubmed_style_bold',         	# id
			'',                              	# title
			array($this,'styles_bold_form'),   	# callback
			'wp_pubmed_reflist_styles',        	# page
			'wp_pubmed_bolds'              	# section
			);
		/*
		Registry for NCBI api key managment tab
		*/
	
		register_setting( 
			'pubmed_ref_api',                    	# option group
			'wp_pubmed_reflist_api',                	# option name
			array($this, 'pubmed_formats_validate')    	# sanitize callback
			); 
		add_settings_section(
			'wp_pubmed_reflist_apikey',      	# id
			'Add NCBI API key',                       	# title
			'wpPubMedReflistViews::api_key_form_text',	# callback 
			'wp_pubmed_reflist_api'               	# page
			);
		add_settings_field(
			'wp_pubmed_reflist_api',      	# id
			'',                              	# title
			array($this, 'api_form'),      	# callback
			'wp_pubmed_reflist_api',        	# page
			'wp_pubmed_reflist_apikey'              	# section	 
			);			
	}




	/*
	This is the wrapper HTML form displayed when the Settings menu is called
	unlike other places in WP, this seems to just echo the content to the screen, rather than returning
	a string.
	
	The settings_fields function tells WP, what "option-group" we are working with.
	
	do_settings_sections Prints out all settings sections added to a particular settings page.
	*/
	function options_page(){
		echo "<h2>PubMed Reflist</h2>
		Manage PubMed queries for use with the [pmid-refs key=<key> limit=<number>] shorttag.";
		
		if(!extension_loaded('openssl')){
			echo "<h3 style='color:red;'>php openssl module not found. This is required to use https to contact PubMed E-utilities.</h3>";
		}
		if(!extension_loaded('SimpleXML')){
			echo "<h3 style='color:red;'>php SimpleXML module not found. This is required to process NCBI query results.</h3>";
		}
		
		$wpPubMedRefList = new wpPubMedRefList;
		if($wpPubMedRefList->remote_method == ''){
			wpPubMedReflistViews::noFetchMethod();
			return true;
		}else{
			# for testing
		#	echo "<p>$wpPubMedRefList->remote_method</p>";
		}
		echo "<div class='wrap'>";
		/*
		Tabbed management
		modified from 		http://code.tutsplus.com/tutorials/the-complete-guide-to-the-wordpress-settings-api-part-5-tabbed-navigation-for-your-settings-page--wp-24971
		*/
		$active_tab = 'query';
		if( isset( $_GET[ 'tab' ] ) ) {
    		$active_tab = $_GET[ 'tab' ];
		} // end if
		$query_tab_class = $format_tab_class = $api_tab_class = $help_tab_class = 'nav-tab';
		$active_tab_class = $active_tab.'_tab_class';
		# commented out until I can figure out weird reordering and layout behavior of the tabs
	#	$$active_tab_class = 'nav-tab-active';	
		echo "<h2 class='nav-tab-wrapper'>
			<a href='?page=wp_pubmed_reflist&tab=query' class='$query_tab_class'>Queries</a>
			<a href='?page=wp_pubmed_reflist&tab=format' class='$format_tab_class'>Reference Styles</a>
			<a href='?page=wp_pubmed_reflist&tab=api' class='$api_tab_class'>NCBI API key</a>
			<a href='?page=wp_pubmed_reflist&tab=help' class='$help_tab_class'>Help</a>
		</h2>";
		echo "<form action='options.php' method='post'>";
		switch($active_tab){
			case 'help':
				wpPubmedReflistViews::help();
				break;
			case 'format':
				settings_fields('pubmed_ref_styles'); 
				do_settings_sections('wp_pubmed_reflist_styles');
				submit_button('Save Changes','submit', true, array('tab'=>'format'));
				break;
			case 'api':
				settings_fields('pubmed_ref_api'); 
				do_settings_sections('wp_pubmed_reflist_api'); # param is a page name, not a section id
				submit_button('Save Changes','submit', true, array('tab'=>'api'));
				break;
			case 'query':
			default:
				settings_fields('pubmed_refs_options'); 
				do_settings_sections('wp_pubmed_reflist');
				submit_button('Save Changes','submit', true, array('tab'=>'query'));
	}
		echo "</form></div>";
		return;
	}	
	
	/*
	Forms for the Queries tab
	*/
	function query_form(){
		require_once(__DIR__."/settings/query_form.php");	
	}

	function api_form(){
		require_once(__DIR__."/settings/api_form.php");	
	}

	function faclist_form(){
		require_once(__DIR__."/settings/faclist_form.php");	
	}
	
	/*
	The form that adds new styles gets submitted each time whether or not there is anything new
	We use hidden input fields to make sure we keep all the old values
	*/
	function styles_form(){
		$this->set_default_styles();
		$styleProps = $this->formats['styleprops'];
		if($this->formats && isset($this->formats['stylelist']) ) $styleList = $this->formats['stylelist'];
		
		$i = 0;
		foreach ($styleList as $name){
			#$name = strtolower($name);
			if ($name != "" && !(isset($styleProps[$name]['delete']) && $styleProps[$name]['delete'] == 'on')){
				echo "<input id='wp_pubmed_reflist_styles' name='wp_pubmed_reflist_styles[stylelist][$i]' type='hidden' value='{$name}' />";
				$i++;
			}
		}		
		echo "Add new:<input id='wp_pubmed_reflist_formats' name='wp_pubmed_reflist_styles[stylelist][$i]' size='40' type='text' value='' />";
	
	}
	
	function style_props_form(){
		# get the previous value returns false if empty
		$stylelist = $this->formats['stylelist'];
		$styleprops = $this->formats['styleprops'];
		echo "<table border='1'>".
			 "		<tr>".
			 "			<th style='width:auto;'>Default</th>".
			 "			<th>Style Name</th><th>Format</th>".
			 "			<th style='width:auto;'>Author limit</th>".
			 "			<th style='width:auto;'>Delete?</th>".
			 "		</tr>";
		if($stylelist){
			sort($stylelist);
			foreach($stylelist as $name){
				# sanitize the stylelist name
				$sanitizedName = preg_replace('/[^a-zA-Z0-9\s-_\(\)]/', '', $name);
				if($sanitizedName != $name){
					echo "$name contains characters not allowed in the key";
					# delete it if it is already in the db.
					if(($key = array_search($name, $this->formats['stylelist'])) !== false) {
						unset($this->formats['stylelist'][$key]);
					}
					if(isset($styleprops[$name]['format'])){
						$styleprops[$sanitizedName]['format'] = $styleprops[$name]['format'];
						unset ($styleprops[$name]['format']);
					}							
					continue;
				}
				if ($name == '' || (isset($styleprops[$name]['delete']) && $styleprops[$name]['delete'] == 'on')) continue;
				#$name = strtolower($name);
				$val = "";
				
				# radio buttons to set default style
				$checked = '';
				if(isset($this->formats['default_style']) && $this->formats['default_style'] == $name){ 
					$checked = 'checked';
				}	
				echo "<tr>
					<td><input id='wp_pubmed_reflist_stylelist' name='wp_pubmed_reflist_styles[default_style]' ".
					"type='radio' value='$name' $checked/></td>";
				# style name	
				echo "<td>$name</td>";
				$template = '';
				if(isset($styleprops[$name]['format'])) $template = $styleprops[$name]['format'];
				
				# max number of authors in author list
				$authlimit = '';
				if(isset($styleprops[$name]['authlimit'])) $authlimit = $styleprops[$name]['authlimit'];
				# number of authors to show in author list if > $authlimit
				$authshow = '';
				if(isset($styleprops[$name]['authshow'])) $authshow = $styleprops[$name]['authshow'];
	
				if(in_array($name, $this->default_styles)){
					echo "<td><input id='wp_pubmed_reflist_stylelist' name='wp_pubmed_reflist_styles[styleprops][$name][format]'". 
					"type='hidden' value='$template' />".htmlentities($template)."</td>
					<td>
					<input id='wp_pubmed_reflist_stylelist' ".
					"name='wp_pubmed_reflist_styles[styleprops][$name][authlimit]' 
					type='hidden' value='$authlimit' />Limit: $authlimit<br>
					<input id='wp_pubmed_reflist_stylelist' ".
					"name='wp_pubmed_reflist_styles[styleprops][$name][authshow]' 
					type='hidden' value='$authshow' />Show: $authshow
					</td><td></td>";
				}else{
					# reference template
					echo "<td><textarea id='wp_pubmed_reflist_stylelist' ".
					"name='wp_pubmed_reflist_styles[styleprops][$name][format]' 
					cols='60' rows='2' >$template</textarea></td>";
					# author list limit
					echo "<td>
					Limit<input type='text' size='3' id='wp_pubmed_reflist_stylelist' ".
					"name='wp_pubmed_reflist_styles[styleprops][$name][authlimit]' value='$authlimit' /><br>
					Show<input type='text' size='3' id='wp_pubmed_reflist_stylelist' ".
					"name='wp_pubmed_reflist_styles[styleprops][$name][authshow]' value='$authshow' />
					</td>";
					# delete?
					echo "<td><input type='checkbox' id='wp_pubmed_reflist_stylelist' ".
					"name='wp_pubmed_reflist_styles[styleprops][$name][delete]' /></td>
					<tr>";
					# last update
					echo "<input id='wp_pubmed_reflist_stylelist'". 
					"name='wp_pubmed_reflist_styles[styleprops][$name][last_update]' type='hidden' value='0' />";
				}
			}
		}	
		echo "</table>";
	
	}
	
	function wrapper_form(){
		echo "under construction";
	}

	/*
	The form that adds new styles gets submitted each time whether or not there is anything new
	We use hidden input fields to make sure we keep all the old values
	*/
	
	function set_default_styles(){
		$this->default_styles = array('ASM','Cell', 'NIH','PNAS');
		if (!in_array('ASM', $this->formats['stylelist']) ){
			$this->formats['stylelist'][] = 'ASM';
		}
		$this->formats['styleprops']['ASM']['format'] = '<b>_Author</b>. _Year. _Title. _Journal <b>_Volume</b>:_Pages.';
		$this->formats['styleprops']['ASM']['authlimit'] = 99;

		if (!in_array('Cell', $this->formats['stylelist']) ){
			$this->formats['stylelist'][] = 'Cell';
		}
		$this->formats['styleprops']['Cell']['format'] = '_Author (_Year). _Title. _Journal<i>_Volume</i>,_Pages.';
		$this->formats['styleprops']['Cell']['authlimit'] = 10;

		if (!in_array('NIH', $this->formats['stylelist']) ){
			$this->formats['stylelist'][] = 'NIH';
		}
		$this->formats['styleprops']['NIH']['format'] = '_Author. _Title. _Journal. _Year;_Volume (_Issue):_Pages. _Epub. _DOI. _PMIDL _PMCL.';
		$this->formats['styleprops']['NIH']['authlimit'] = 6;

		if (!in_array('PNAS', $this->formats['stylelist']) ){
			$this->formats['stylelist'][] = 'PNAS';
		}
		$this->formats['styleprops']['PNAS']['format'] = '_Author(_Year)_Title. <i>_Journal</i> _Volume (_Issue):_Pages.';
		$this->formats['styleprops']['PNAS']['authlimit'] = 6;
		$this->formats['styleprops']['PNAS']['authshow'] = 1;
		
		# set default italicization list
		if($this->formats['itals'] == '' ){
			$this->formats['itals'] = 
				"in vivo\n".	
				"in vitro\n".	
				"in silico\n".	
				"E. coli\n".	
				"Escherichia coli\n".	
				"B. subtilis\n".	
				"Bacillus subtilis\n".	
				"S. cerevisiae\n".	
				"Saccharomyces cerevisiae\n"	
			;
		}
		
		update_option('wp_pubmed_reflist_styles', $this->formats);		


	}
	
	function styles_ital_form(){
		$itals = '';
		if($this->formats && isset($this->formats['itals']) ) $itals = $this->formats['itals'];
		echo "<textarea id='wp_pubmed_reflist_stylelist' ".
			"name='wp_pubmed_reflist_styles[itals]' 
			cols='40' rows='10' >$itals</textarea>";	
	}

	function styles_bold_form(){
		$itals = '';
		if($this->formats && isset($this->formats['bold']) ) $itals = $this->formats['bold'];
		echo "<textarea id='wp_pubmed_reflist_stylelist' ".
			"name='wp_pubmed_reflist_styles[bold]' 
			cols='40' rows='10' >$itals</textarea>";	
	}
	/*
	Input validation
	TODO!
	*/
	function pubmed_queries_validate($text){
	#	echo "is this callback used? $text";
		return $text;
	}
	function pubmed_faclist_validate($text){
		return $text;
	}
	function pubmed_formats_validate($text){
		return $text;
	}

}