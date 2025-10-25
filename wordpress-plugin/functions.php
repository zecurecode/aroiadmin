<?php
//Icluding our time functions
include('adminfunctions.php');
//Generate shortcodes...
add_shortcode('gettid', 'gettid_function');
add_shortcode('gettid2', 'gettid_function2');

add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'parent-style' )
	);

	if ( is_rtl() ) {
		wp_enqueue_style( 'parent-rtl', get_template_directory_uri() . '/rtl.css' );
		wp_enqueue_style( 'child-rtl',
			get_stylesheet_directory_uri() . '/rtl.css',
			array( 'parent-rtl' )
		);
	}

	wp_enqueue_script( 'child-lafka-front',
		get_stylesheet_directory_uri() . '/js/lafka-front.js',
		array( 'lafka-front' ),
		false,
		true
	);
	
	//wp_enqueue_script( 'orderfunc', get_stylesheet_directory_uri() . '/js/orders.js' );
}
//Fjern zoom på single image product Ivana 15.09.2021
function remove_image_zoom_support() {
    remove_theme_support( 'wc-product-gallery-zoom' );
}
add_action( 'wp', 'remove_image_zoom_support', 100 );
//fjern klikk på single image product Ivana 15.09.2021
function e12_remove_product_image_link( $html, $post_id ) {
    return preg_replace( "!<(a|/a).*?>!", '', $html );
}
add_filter( 'woocommerce_single_product_image_thumbnail_html', 'e12_remove_product_image_link', 10, 2 );
add_filter( 'woocommerce_product_tabs', 'woo_remove_product_tabs', 98 );
// fjern Tillegsinformasjon Ivana 15.09.2021
function woo_remove_product_tabs( $tabs ) {

    unset( $tabs['additional_information'] );   // Remove the additional information tab

    return $tabs;

}

//Håkon 16092021. Showing hidden meta fields oh yeah!
add_filter( 'is_protected_meta', '__return_false' ); 


//Håkon functions 23.09.2021 start og yeah in da morning

//Get the ID from the calling blog
function getCaller(){
	$siteid = get_current_blog_id();
	return $siteid;
	
	/**
	* NAME		SITE		USERID
	* Namsos	7			10
	* Lade		4			11
	* Moan		6			12
	* Gramyra	5			13
	* Frosta   10			14
	* Steinkjer 13			17
	* malvik    15
	**/
}

/**AutoOrder Start**/ 
 

//send order to database ivana 30.03.2021


add_action( 'woocommerce_new_order', 'your_order_details',  1, 1  );
add_action( 'woocommerce_payment_complete', 'orderIsPaid',  1, 1  );

function orderIsPaid($order_id){
	$caller = getCaller();
	$license = 0;
	switch($caller){
		case 7:
			$license = 6714;
			break;
		case 4:
			$license = 12381;
			break;
		case 6:
			$license = 5203;
			break;
		case 5:
			$license = 6715;
			break;
		case 10:
			$license = 14780;
			break;
		case 13:
			$license = 30221;
			break;
		case 15:
			$license = 14946;
			break;
		case 9:
			$license = 1000;
			break;
		default:
			$license = 0;
			break;
		
	}
	
/* Configure your remote DB settings here */
        $host = 'localhost:3306';
        $user = 'adminaroi';
        $pass ='b^754Xws';
        $db = 'admin_aroi';
		
        $new_wpdb = mysqli_connect($host, $user, $pass, $db);
        if (!$new_wpdb) {
            echo mysqli_error($new_wpdb);
        }
        $order = new WC_Order($order_id);
        $order_num = $order_id; 
        //$fornavn = $order->get_billing_first_name();
        //$etternavn= $order->get_billing_last_name();
		//$telefon=$order->get_billing_phone();
		//$epost=$order->get_billing_email();
        //$orderstatus=$order->get_status();
		//$payref = $order->get_payment_method();
		//$orderstatus=0;
		$order_master = "UPDATE orders SET paid = 1 WHERE site = $caller AND ordreid = $order_num";
	    //$order_master = "INSERT INTO `orders`( `fornavn`, `etternavn`, `telefon`, `ordreid`, `ordrestatus`, `epost`, `curl`, `site`)VALUES('$fornavn','$etternavn','$telefon','$order_num', '$orderstatus', '$epost', 0, '$caller')";
        $order_master_exe = mysqli_query($new_wpdb, $order_master);
        if ($order_master) {
            mysqli_close($new_wpdb);
        }
		
		//Calling our script...
		$url = "https://aroiasia.no/admin/api.php";
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    $output= curl_exec($ch);
	    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
	
	}//End function


function your_order_details($order_id){
//Sending orders to pckasse
	$caller = getCaller();
	$license = 0;
	switch($caller){
		case 7:
			$license = 6714;
			break;
		case 4:
			$license = 12381;
			break;
		case 6:
			$license = 5203;
			break;
		case 5:
			$license = 6715;
			break;
		case 10:
			$license = 13245;
			break;
		case 13:
			$license = 30221;
			break;
		case 15:
			$license = 14946;
			break;
		case 9:
			$license = 1000;
			break;
		default:
			$license = 0;
			break;
		
	}
	
/* Configure your remote DB settings here */
        $host = 'localhost:3306';
        $user = 'adminaroi';
        $pass ='b^754Xws';
        $db = 'admin_aroi';
		
        $new_wpdb = mysqli_connect($host, $user, $pass, $db);
        if (!$new_wpdb) {
            echo mysqli_error($new_wpdb);
        }
        $order = new WC_Order($order_id);
        $order_num = $order_id; 
        $fornavn = $order->get_billing_first_name();
        $etternavn= $order->get_billing_last_name();
		$telefon=$order->get_billing_phone();
		$epost=$order->get_billing_email();
		//$hentes = $order->get_meta('hentes_kl');
		//print_r($order);
        //$orderstatus=$order->get_status();
		$orderstatus=0;
		 
	    $order_master = "INSERT INTO `orders`( `fornavn`, `etternavn`, `telefon`, `ordreid`, `ordrestatus`, `epost`, `curl`, `site`)VALUES('$fornavn','$etternavn','$telefon','$order_num', '$orderstatus', '$epost', 0, '$caller')";
        $order_master_exe = mysqli_query($new_wpdb, $order_master);
        if ($order_master) {
            mysqli_close($new_wpdb);
        }
	
}//End function
		
		
//Change alert message...



//12.08.2021 // Ny endring 10/11/21

add_action('woocommerce_before_order_notes', 'print_donation_notice', 10 );
function print_donation_notice() {
	 date_default_timezone_set('Europe/Oslo');
	 $open_time =strtotime(getOpen(getCaller()));
	//echo $open_time;
    $close_time = strtotime(getClose(getCaller()));
    //echo "-:-";
    // Current time
     //$current_time = current_time( 'timestamp' );
	 
	 $current_time = strtotime('now');
	 
	 //echo $current_time;
	//echo date('H-i', $current_time);
	//echo "::::____::::::";
	$status = getStatus(getCaller());
	if($status == 0){
		echo "<h3 style='color:red; text-align:center;'>Vognen er stengt.<br> Du kan fortsatt bestille for neste dag.</h3>";
	}
	//echo $status;
	//echo date('D');
    // Closed
    if( $current_time > $close_time || $current_time <= $open_time || $status == 0) {
	   wc_print_notice( sprintf(
        __("Bestillinger som kommer utenfor vår åpningstid blir gjennomgått så snart butikken åpner. Så snart butikkens medarbeidere har klargjort maten, får du beskjed på e-mail og sms.", "woocommerce"),
        '<strong>' . __("Information:", "woocommerce") . '</strong>',
        strip_tags( wc_price( WC()->cart->get_subtotal() * 0.03 ) )
    ), 'success' );
         
    } else {
        // Default value
   
}
//echo date('H-i', $current_time);

}
//Meta KaOs!:)!

//add_action('save_post_product', 'updateAddons', 10, 3 );
//add_action('added_post_meta', 'updateAddons', 10, 3 );

function updateAddons($product_id){

	//Todo
	//Get productMeta dsk_addon
	//Rewrite to accetable code for _product_addons

/**
    $thedata = Array(
    'pa_autore'=>Array( 
    'name'=>'pa_autore', 
    'value'=>10
    )
    );
**/
	$addondata = 'a:1:{i:0;a:9:{s:4:"name";s:7:"SalatLO";s:5:"limit";s:0:"";s:11:"description";s:0:"";s:4:"type";s:8:"checkbox";s:8:"position";i:0;s:10:"variations";i:0;s:9:"attribute";i:0;s:7:"options";a:2:{i:0;a:6:{s:5:"label";s:5:"Agurk";s:5:"image";s:0:"";s:5:"price";s:2:"10";s:3:"min";s:0:"";s:3:"max";s:0:"";s:7:"default";s:1:"1";}i:1;a:6:{s:5:"label";s:5:"Tomat";s:5:"image";s:0:"";s:5:"price";s:0:"";s:3:"min";s:0:"";s:3:"max";s:0:"";s:7:"default";s:1:"0";}}s:8:"required";i:0;}}';
	//$addondata = get_post_meta( $product_id, '_product_addons' );

	$newtest = unserialize($addondata);

    if ( ! add_post_meta( $product_id, 'dsk_addons',$newtest, true)){
	update_post_meta($product_id, 'dsk_addons',$newtest);
	}

}

//add_shortcode('test', 'showData');
function showData(){
	$addondata = 'a:1:{i:0;a:9:{s:4:"name";s:7:"SalatLO";s:5:"limit";s:0:"";s:11:"description";s:0:"";s:4:"type";s:8:"checkbox";s:8:"position";i:0;s:10:"variations";i:0;s:9:"attribute";i:0;s:7:"options";a:2:{i:0;a:6:{s:5:"label";s:5:"Agurk";s:5:"image";s:0:"";s:5:"price";s:2:"10";s:3:"min";s:0:"";s:3:"max";s:0:"";s:7:"default";s:1:"1";}i:1;a:6:{s:5:"label";s:5:"Tomat";s:5:"image";s:0:"";s:5:"price";s:0:"";s:3:"min";s:0:"";s:3:"max";s:0:"";s:7:"default";s:1:"0";}}s:8:"required";i:0;}}';
	//$addondata = get_post_meta( $product_id, '_product_addons' );

	$newtest = unserialize($addondata);
	//echo $newtest;
	print_r($newtest);
}

add_action( 'save_post', 'update_product_addons', 30, 3 );
//add_action('woocommerce_process_product_meta', 'update_product_addons', 30, 3);
function update_product_addons( $post_id, $post, $update ) {
	
	//update_post_meta($post_id, 'dsk_addons_updated', 'pre start');
	
	if ($post->post_status != 'publish' || $post->post_type != 'product') {
        //return;
    }

    if (!$product = wc_get_product( $post )) {
        return;
    }
	$toUpate = get_post_meta($post_id, 'dsk_addons_updated', true);
	//Check if the product addons have been added already!
	if(1 == 1){
		//if($toUpate == "no"){
	
	$product_addons = get_dsk_product_addons( $post_id );
		if ( $update == false ) {
			add_post_meta( $post_id, '_product_addons', $product_addons );
			update_post_meta($post_id, 'dsk_addons_updated', 'First if');
		} else {
			update_post_meta( $post_id, '_product_addons', $product_addons );
			update_post_meta($post_id, 'dsk_addons_updated', 'Second if');
		}
		
	}//End fuction
}

function get_dsk_product_addons( $product_id ) {
	
	$product_addons = array();
	
	$product_addons_dsk = get_post_meta( $product_id, 'dsk_addons', true );
	$product_addons_dsk = json_decode( $product_addons_dsk, true );
	
	if ( !empty( $product_addons_dsk ) ) {
		
		$addonsIndex = 0;
		foreach($product_addons_dsk as $key => $addon) {
			
			$addon_options 	= array();
			foreach($addon as $addonKey => $addonValue) {
			
				$label = sanitize_text_field( stripslashes( $addonKey ) );
				$image = '';
				$price = '';
				if ( !empty( $addonValue ) ) {
					$price = wc_format_decimal( sanitize_text_field( stripslashes( $addonValue ) ) );
				}
				$min = '';
				$max = '';
				$default = '';
				$addon_options[] = array(
					'label'   => $label,
					'image'   => $image,
					'price'   => $price,
					'min'     => $min,
					'max'     => $max,
					'default' => $default,
				);
				
			}
			
			$addon_required = 0;
			if (strpos($key, '*') !== false) {
				$addon_required = 1;
			}
			
			$data                = array();
			$data['name']        = sanitize_text_field( stripslashes( $key ) );
			$data['limit']       = '';
			$data['description'] = '';
			$data['type']        = 'radiobutton';
			$data['position']    = absint( $addonsIndex );
			$data['variations']  = 0;
			$data['attribute']   = 0;
			$data['options']     = $addon_options;
			$data['required']    = $addon_required;
			
			// Add to array.
			$product_addons[] = $data;
			
			$addonsIndex = $addonsIndex + 1;
		}//End Foreach
		
	if($post_id != null){
	update_post_meta($post_id, 'dsk_addons_updated', 'yes');	
	}
	}//End IF
	
	else{
		update_post_meta($post_id, 'dsk_addons_updated', 'no');
	}
	
	uasort( $product_addons, 'addons_cmp' );
	
	return $product_addons;
}

function addons_cmp( $a, $b ) {
	if ( $a['position'] == $b['position'] ) {
		return 0;
	}

	return ( $a['position'] < $b['position'] ) ? -1 : 1;
}

function action_woocommerce_before_order_notes( $checkout ) { 
// timezone Oslo
date_default_timezone_set('Europe/Oslo');
    // Open and close time
    $open_time =strtotime(getOpen(getCaller()));
	//echo $open_time;
    $close_time = strtotime(getClose(getCaller()));
    //echo "-:-";
    // Current time
     //$current_time = current_time( 'timestamp' );
	 $current_time = strtotime('now');
	 
	 //echo $current_time;
	//echo date('H-i', $current_time);
	//echo "::::____::::::";
	$makingtime = gettid_function(getCaller());
	$maketimestr = " +$makingtime minutes";
	//echo $maketimestr;
	//echo $opentimetest;
	
	getNextOpen();
	//echo date('Y-m-d', strtotime(' +1 day'));
    // Closed
    if( $current_time > $close_time || $current_time <= $open_time ) {
        echo "<h5>Vognen er nå stengt. Du kan fortsatt bestille, og maten er klar til henting på første ledige tidspunkt.</h5>";
        
       // As soon as possible
       // $asa_possible = strtotime( $maketimestr, $current_time );
	   //$asa_possible = strtotime( '+1 hour', $current_time );
		//Set the default time...
		$asa_possible=$open_time;
		$options[date( 'H:i', $open_time )] = __( date( 'H:i', $open_time ), 'woocommerce');
			
		
        $asa_possible = ceil( $asa_possible / ( 15 * 60 ) ) * ( 15 * 60);
		//$asa_possible+= $opentimetest;
        //echo "here";
		//echo date( 'H:i', $asa_possible );
		//echo date( 'H:i', $open_time );
		//echo date( 'H:i', $close_time);
        // Add a new option every 15 minutes
        while( $asa_possible <= $close_time &&  $asa_possible >= $open_time ) {
			$value = date( 'H:i', $asa_possible );
            $options[$value] = $value;
            
            // Add 15 minutes
		$asa_possible = strtotime( '+15 minutes', $asa_possible );
			
		}
    } else {
		
        echo "<h5>Det tar omtrent $makingtime minutter før din bestilling er klar for henting</h5>";
        // As soon as possible
        $asa_possible = strtotime( $maketimestr, $current_time );
		
		//Set the default time...
		$options[date( 'H:i', $asa_possible )] = __( date( 'H:i', $asa_possible ), 'woocommerce');
        
        // Round to next 15 minutes (15 * 60 seconds)
        $asa_possible = ceil( $asa_possible / ( 15 * 60 ) ) * ( 15 * 60);
		
	
		
        
        // Add a new option every 15 minutes
        while( $asa_possible <= $close_time && $asa_possible >= $open_time ) {
            $value = date( 'H:i', $asa_possible );
            $options[$value] = $value;
            
            // Add 15 minutes
            $asa_possible = strtotime( '+15 minutes', $asa_possible );
			//echo $asa_possible;
        }
    }

    // Add field
    woocommerce_form_field( 'hentes_kl', array(
        'type'          => 'select',
        'class'         => array( 'wps-drop' ),
        'label'         => __('Hentetidspunkt', 'woocommerce' ),
        'options'       => $options,
    ), $checkout->get_value( 'hentes_kl' ));

	//echo $checkout->get_value( 'delivery_time' ));
	 //$order->add_order_note( $options );

	//Update meta!
	//update_post_meta($post_id, 'dsk_hentetid', $options);
	//$order->update_meta_data( '_hentetid', $options );
 
}

add_action( 'woocommerce_before_order_notes', 'action_woocommerce_before_order_notes', 10, 1 );

add_action('woocommerce_checkout_update_order_meta', 'wps_select_checkout_field_update_order_meta');
function wps_select_checkout_field_update_order_meta( $order_id ) {

   if ($_POST['hentes_kl']) update_post_meta( $order_id, 'hentes_kl', esc_attr($_POST['hentes_kl']));

}


add_action( 'woocommerce_admin_order_data_after_billing_address', 'wps_select_checkout_field_display_admin_order_meta', 10, 1 );
function wps_select_checkout_field_display_admin_order_meta($order){

    echo '<p><strong>'.__('Hentes kl ').':</strong> ' . get_post_meta( $order->id, 'hentes_kl', true ) . '</p>';

}

add_filter('woocommerce_email_order_meta_keys', 'wps_select_order_meta_keys');
function wps_select_order_meta_keys( $keys ) {

    $keys['hentes_kl:'] = 'hentes_kl';
    return $keys;
    
}

add_filter('woocommerce_after_add_to_cart_button', 'dsk_show_arrows', 10, 1);
add_filter('woocommerce_before_single_product', 'dsk_show_arrows', 10, 1);
function dsk_show_arrows(){
	
	// Get parent product categories on single product pages
$terms = wp_get_post_terms( get_the_id(), 'product_cat', array( 'include_children' => false ) );

// Get the first main product category (not a child one)
$term = reset($terms);
$term_link =  get_term_link( $term->term_id, 'product_cat' ); // The link
echo '<h2 class="dsklink"><a href="'.$term_link.'"><i class="fas fa-angle-left" style="font-size:48px;color:#8a3794"></i></a></h2>';

}
