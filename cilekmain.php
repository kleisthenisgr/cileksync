<?php
    /*
     * Plugin Name:       Cilek Synchronization
     * Plugin URI:        https://enterthe.shop
     * Description:       Stores the xml specified products into the wordpress, updating the database. With its secondary function, it scans the products on the database belonging to Cilek and if the product is not present on the xml, record is deleted.
     * Version:           1.3
     * Requires at least: 5.2
     * Requires PHP:      7.2
     * Author:            Alexios-Theocharis Koilias
     * Author URI:        https://enterthe.shop
     */
     
    // Add a custom menu page in the admin panel
    function insert_product_menu_page() {
        add_menu_page(
            'Cilek SYNC',
            'Cilek SYNC',
            'manage_options',
            'insert-products',
            'insert_product_page',
            'https://enterthe.shop/pixil-frame-0.png',
            85
        );
    }
    add_action('admin_menu', 'insert_product_menu_page');
     
    // Callback function for the custom menu page
    function insert_product_page() {
        if (isset($_POST['insert_products'])) {
            $inserted_count=0;
            insert_product_button($inserted_count);
            echo '<div class="notice notice-success"><p>Εισήχθησαν ' . $inserted_count . ' προϊόντα!';
            echo '</p></div>';
        } elseif (isset($_POST['remove_unknown_records'])) {
            $deleted_count = remove_unknown_records();
            echo '<div class="notice notice-success"><p>' . $deleted_count . ' προϊόντα απαλείφθηκαν!</p></div>';
        }
        elseif (isset($_POST['update_product_values']))
        {
            $count= 0;
            $update_count=0;
            update_product_values_button($count,$update_count);
            echo '<div class="notice notice-success"><p>Προσπελάστηκαν ' . $count . ' προϊόντα, έχρηζαν ενημέρωσης: ' . $update_count;
            echo '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Εισαγωγή νέων προϊόντων Cilek <small><i>(χρόνος εκτέλεσης ~30 δευτερόλεπτα)</i></small></h1>
            <form method="post">
                <?php wp_nonce_field('insert_products_action', 'insert_products_nonce'); ?>
                <input type="submit" name="insert_products" class="button-primary" value="Ανίχνευση και προσθήκη">
                <input type="submit" name="remove_unknown_records" class="button" value="Απαλoιφή αγνώστων εγγραφών">
                <input type="submit" name="update_product_values" class="button" value="Ενημέρωση χαρακτηριστικών">
     
     
            </form>
        </div>
        <?php
    }
     
    function check_product_exist($key, $product_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT post_id from {$wpdb->postmeta} WHERE meta_key = '{$key}' AND meta_value= %d", $product_id));
    }
     
    function remove_unknown_records() {
        global $wpdb;
        $url = 'https://www.cilek.gr/modules/linkwisexml/feed.xml';
        $xml = simplexml_load_file($url);
     
        $product_ids_in_xml = array();
     
        foreach ($xml->products->product as $product) {
            $product_id = (string) $product->id;
            $product_ids_in_xml[] = $product_id;
        }
     
        $post_ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'product_id'");
     
        $removed_count = 0;
     
        foreach ($post_ids as $post_id) {
            $product_id = get_post_meta($post_id, 'product_id', true);
            $product_url = get_post_meta($post_id, '_product_url', true);
            // Check if _product_url contains 'cilek.gr'
            if (!in_array($product_id, $product_ids_in_xml) && is_int(strpos($product_url, 'cilek.gr'))) {
                $product_name = get_the_title($post_id);
     
                wp_delete_post($post_id, true);
                $removed_count++;
     
                echo 'Αφαιρέθηκε ασυμφωνία: ' . $product_name . '<br>';
            }
        }
     
        return $removed_count;
    }
     
     
        // Function to insert product image
    function insert_product_image($image_url, $product_id) {
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($image_url);
     
        if ($image_data !== false) {
            $filename = basename($image_url);
            $upload_file = wp_upload_bits($filename, null, $image_data);
     
            if (!$upload_file['error']) {
                $attachment = array(
                    'post_mime_type' => $upload_file['type'],
                    'post_title' => sanitize_file_name($filename),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
     
                $attach_id = wp_insert_attachment($attachment, $upload_file['file'], $product_id);
     
                if (!is_wp_error($attach_id)) {
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    $attach_data = wp_generate_attachment_metadata($attach_id, $upload_file['file']);
                    wp_update_attachment_metadata($attach_id, $attach_data);
     
                    return $attach_id;
                }
            }
        }
     
        return false;
    }
        
     
     
    function insert_product(){//&$count_records){
        
        set_time_limit(300);
        $i3=0;
        // Set the maximum amount of time the script can spend waiting for data from the remote server (adjust as needed)
        ini_set('default_socket_timeout', 120);
        
        $url = 'https://www.cilek.gr/modules/linkwisexml/feed.xml';
        $xml = simplexml_load_file($url);
        
         foreach ($xml->products->product as $product) {
             if ($i3>24)
             {
                 break;
             };
            $id_read = (string) $product->id;
            $name_read = (string) $product->name;
            $category_read = (string) $product->category;
            $description_read = (string) $product->description;
            $brand_read = (string) $product->brand;
            $link_read = (string) $product->link;
            $image_read= (string) $product->image;
            $additionalImages = [];
            if (isset($image_read))
            {
                $additionalImages[] =(string) $image_read;
            }
            foreach ($product->additionalimage as $additionalImage) {
                $additionalImages[] =  $additionalImage;
            }
            
            $initialPrice_read = (float)$product->initialPrice;
            $discount_read = (float)$product->discount;
            $price_read = (float)$product->price;
            $mpn_read = (string)$product->mpn;
            $availability_read = (string)$product->availability;
            if ($availability_read == "Άμεσα διαθέσιμο")
            {
                $availability_read='instock';
            }
            else
            {
                $availability_read='onbackorder';
            }
            
            $custom_id= $id_read;
            $name = $name_read;
            $description= $description_read;
            $excerpt= '';
            $regular_price = $initialPrice_read;
            $sale_price= $discount_read;
            $price= $price_read;
            $stock= '';
            $sku= $mpn_read;
            $stock_status= $availability_read;
            $stock_manage= 'no';
            $visibility= 'visible';
            $totalsales= 0;
            $downloadable= 'no';
            $virtual= 'no';
            $purchase_note= '';
            $featured= 'yes';
            $backorders='yes';
            $weight= '';
            $length= '';
            $width= '';
            $height= '';
            $sale_price_dates_from= '2022-12-31';
            $sale_price_dates_to= '2028-07-07';
            $sold_individually= 'no';
            $backorders= 'yes';
            $category= explode('>', $category_read);
            $category1='';
            $category2='';
            $category3='';
            foreach ($category as $index => $temp) {
                if ($index === 0) {
                    $category1 =(string) $temp;
                } elseif ($index === 1) {
                    $category2 =(string) $temp;
                } elseif ($index === 2) {
                    $category3 =(string) $temp;
                }
            }
            $tag1 = $brand_read;
     
        $existing_product_id = check_product_exist('product_id', $custom_id);
      
     
     
    if ($existing_product_id)
    {
        continue;
     
    }
    else{
        
        $defaults = array(
            'post_content' => $description,
            'post_content_filtered' => '',
            'post_title' => $name,
            'post_excerpt' => $excerpt,
            'post_status' => 'publish',  //afora dimosieusi i proxeiro (publish/draft)
            'post_type' => 'product', //afora typo anartisis (post/product)
            'comment_status' => '',
            'ping_status' => '',
            'post_password' => '',
            'to_ping' => '',
            'pinged' => '',
            'post_parent' => 0,
            'menu_order' => 0,
            'guid' => '',
            'import_id' => 0,
            'context' => '',
            'post_date' => '',
            'post_date_get' => '',
            
        );
        $product_id = wp_insert_post( $defaults);
        
         $product_gallery = array();
     
    // Set the first image as the product thumbnail
        if (!empty($additionalImages)) {
            $thumbnail_id = insert_product_image($additionalImages[0], $product_id);
     
            if ($thumbnail_id) {
                set_post_thumbnail($product_id, $thumbnail_id);
            }
        }
     
        // Add remaining images to the product gallery
        for ($i = 1; $i < count($additionalImages); $i++) {
            $attachment_id = insert_product_image($additionalImages[$i], $product_id);
     
            if ($attachment_id) {
                $product_gallery[] = $attachment_id;
            }
        }
     
        
        // ta meta data den apothikeyontai se array plin eikonwn
        update_post_meta($product_id, 'product_id', $custom_id); 
        update_post_meta($product_id, '_product_image_gallery', implode(',', $product_gallery));
        update_post_meta($product_id, '_price', $price); //float
        update_post_meta($product_id, '_regular_price', $regular_price);    //float
        update_post_meta($product_id, '_sale_price', $sale_price);    //float
        update_post_meta($product_id, '_stock', $stock);    //integer
        update_post_meta($product_id, '_sku', $sku); //string
        update_post_meta($product_id, '_stock_status', $stock_status); //instock/outofstock
        update_post_meta($product_id, '_manage_stock', $stock_manage); //yes/no
        update_post_meta($product_id, '_visibility', $visibility); //visible/hidden
        update_post_meta($product_id, 'total_sales', $totalsales); //integer
        update_post_meta($product_id, '_downloadable', $downloadable); //yes/no
        update_post_meta($product_id, '_virtual', $virtual);    //yes/no
        update_post_meta($product_id, '_purchase_note', $purchase_note); //string
        update_post_meta($product_id, '_featured', $featured);  //yes/no
        update_post_meta($product_id, '_weight', $weight);  //float
        update_post_meta($product_id, '_length', $length);  //float
        update_post_meta($product_id, '_width', $width);    //float
        update_post_meta($product_id, '_height', $height);  //float
        update_post_meta($product_id, '_sale_price_dates_from', $sale_price_dates_from);
        update_post_meta($product_id, '_sale_price_dates_to', $sale_price_dates_to);
        update_post_meta($product_id, '_sold_individually', $sold_individually);    //yes/no
        update_post_meta($product_id, '_backorders', $backorders);  //yes/no
        update_post_meta($product_id, '_product_url', $link_read); //string
        
        //orismos katigoriwn proiontos
        
        wp_set_object_terms($product_id, array($category1), 'product_cat');
     
        //orismos etiketwn proiontos
        wp_set_object_terms($product_id, array($tag1), 'product_tag');
        
     
        $i3++;
     
            echo ' Προστέθηκε προϊόν: ' . $name .'<br>';
            }
        $count_records=$i3;
        }    
    }
     
    //synartisi enimerwsis timwn vasis dedomenwn
    function update_product_values(){//&$count, &$update_count)
            set_time_limit(300);
            ini_set('default_socket_timeout', 120);
            $i2=0;
            $url = 'https://www.cilek.gr/modules/linkwisexml/feed.xml';
            //$url = 'https://enterthe.shop/test.xml';
            $xml = simplexml_load_file($url);
            $current_time = new DateTime();
            $i=0;
            foreach ($xml->products->product as $product) {
                
                if ($i2>299)
                {
                    //break;
                }
                
                
                $id_read = (string) $product->id;
                $name_read = (string) $product->name;
                $description_read = (string) $product->description;
                $link_read = (string) $product->link;
                $initialPrice_read = (float)$product->initialPrice;
                $discount_read = (float)$product->discount;
                $price_read = (float)$product->price;
                $availability_read = (string)$product->availability;
                $sku_read= (string) $product->mpn;
                if ($availability_read == 'Άμεσα διαθέσιμο')
                {
                    $availability_read='instock';
                }
                else
                {
                    $availability_read='onbackorder';
                }
                $existing_product_id = check_product_exist('product_id', $id_read);
     
                if ($existing_product_id)
                {
                    
                    $old_product = wc_get_product($existing_product_id); //prosthiki string se idi yparxon id
     
                    
                    $old_product_date = $old_product->get_date_modified();
                    {
                        if ($old_product_date) {
                            $time_diff = $current_time->diff($old_product_date);
                        
                            // xroniki dikleida gia paraleipsi prosfata enimerwmenwn
                            if ($time_diff->i >= 0) 
                            {
                                $old_product_link = get_post_meta($existing_product_id, '_product_url', true);
                                {
                                    if (!(is_int(strpos($old_product_link, 'cilek.gr'))))
                                    {
                                        continue;
                                    }
                                    
                                }
                                $old_product_title = (string)$old_product->get_name();
                                $old_product_description = (string)$old_product->get_description();
                                $old_product_regular_price = (float)$old_product->get_regular_price();
                                $old_product_price = (float)$old_product->get_price();
                                $old_product_sale_price = (float)$old_product->get_sale_price();
                                $old_product_sku = (string)$old_product->get_sku();
                                $old_product_availability = (string)$old_product->get_stock_status();
                                $check_change=0;
                                // enimerwsi diaforetikwn stoixeiwn
                                if ($old_product_title!=$name_read)
                                {
                                    $old_product->set_name((string)$name_read);
                                    $check_change++;
                                }
                                if ($old_product_description!=$description_read)
                                {
                                    $old_product->set_description((string)$description_read);
                                    $check_change++;
                                }
                                if ($old_product_regular_price!=$initialPrice_read)
                                {
                                    //$old_product->set_regular_price($initialPrice_read);
                                    update_post_meta($existing_product_id, '_regular_price', $initialPrice_read);    //float
                                    $check_change++;
                                }
                                if ($old_product_sale_price!=$discount_read)
                                {
                                    //$old_product->set_sale_price($initialPrice_read-$discount_read);
                                    update_post_meta($existing_product_id, '_sale_price', $discount_read);    //float
                                    $check_change++;
                                }
                                if ($old_product_price!=$price_read)
                                {
                                    //$old_product->set_price($initialPrice_read-$discount_read);
                                    update_post_meta($existing_product_id, '_price', $price_read); //float
                                    $check_change++;
                                }
                                if ($old_product_sku!=$sku_read)
                                {
                                    $old_product->set_sku((string)$sku_read);
                                    $check_change++;
                                }
                                if ($old_product_availability!=$availability_read)
                                {
                                    update_post_meta($existing_product_id, '_stock_status', $availability_read); //instock/outofstock
                                    $check_change++;
                                }
     
                                // Save to database (Note: You might want to clear cache of your page and then reload, if it still doesn't show up go to the product page and check there.)
                                $old_product->save();
                               if ($check_change>0)
                               {
                                     echo 'Επιβήθηκαν αλλαγές σε: ' . $old_product_title . '<br>';
                                     $i++;
                                     $update_count=$i;
                                     
                                     $defaults = array(
                                    'ID' => $existing_product_id,
                                    'post_content' => $description_read,
                                    'post_content_filtered' => '',
                                    'post_title' => $name_read,
                                    'post_excerpt' => '',
                                    'post_status' => 'publish',  //afora dimosieusi i proxeiro (publish/draft)
                                    'post_type' => 'product', //afora typo anartisis (post/product)
                                    'comment_status' => '',
                                    'ping_status' => '',
                                    'post_password' => '',
                                    'to_ping' => '',
                                    'pinged' => '',
                                    'post_parent' => 0,
                                    'menu_order' => 0,
                                    'guid' => '',
                                    'import_id' => 0,
                                    'context' => '',
                                    'post_date' => '',
                                    'post_date_get' => '',
                                    
                                );
                                
                                wp_update_post( $defaults);
                                     
     
                               }
                                
                                
                                
                            }
                                $i2++;
                                $count=$i2;
                        }
                        else
                        {
                            continue;
                        }
                            
                    }
                    
                    
                }
                else
                {
                    continue;
                }
     
            }
        }
    ///////////////////////////////////////////////////////////////////////////////////////////////////////
     
    function insert_product_button(&$count_records){
        
        set_time_limit(300);
        $i3=0;
        // Set the maximum amount of time the script can spend waiting for data from the remote server (adjust as needed)
        ini_set('default_socket_timeout', 120);
        
        $url = 'https://www.cilek.gr/modules/linkwisexml/feed.xml';
        $xml = simplexml_load_file($url);
        
         foreach ($xml->products->product as $product) {
             if ($i3>24)
             {
                 break;
             };
            $id_read = (string) $product->id;
            $name_read = (string) $product->name;
            $category_read = (string) $product->category;
            $description_read = (string) $product->description;
            $brand_read = (string) $product->brand;
            $link_read = (string) $product->link;
            $image_read= (string) $product->image;
            $additionalImages = [];
            if (isset($image_read))
            {
                $additionalImages[] =(string) $image_read;
            }
            foreach ($product->additionalimage as $additionalImage) {
                $additionalImages[] =  $additionalImage;
            }
            
            $initialPrice_read = (float)$product->initialPrice;
            $discount_read = (float)$product->discount;
            $price_read = (float)$product->price;
            $mpn_read = (string)$product->mpn;
            $availability_read = (string)$product->availability;
            if ($availability_read == "Άμεσα διαθέσιμο")
            {
                $availability_read='instock';
            }
            else
            {
                $availability_read='onbackorder';
            }
            
            $custom_id= $id_read;
            $name = $name_read;
            $description= $description_read;
            $excerpt= '';
            $regular_price = $initialPrice_read;
            $sale_price= $discount_read;
            $price= $price_read;
            $stock= '';
            $sku= $mpn_read;
            $stock_status= $availability_read;
            $stock_manage= 'no';
            $visibility= 'visible';
            $totalsales= 0;
            $downloadable= 'no';
            $virtual= 'no';
            $purchase_note= '';
            $featured= 'yes';
            $backorders='yes';
            $weight= '';
            $length= '';
            $width= '';
            $height= '';
            $sale_price_dates_from= '2022-12-31';
            $sale_price_dates_to= '2028-07-07';
            $sold_individually= 'no';
            $backorders= 'yes';
            $category= explode('>', $category_read);
            $category1='';
            $category2='';
            $category3='';
            foreach ($category as $index => $temp) {
                if ($index === 0) {
                    $category1 =(string) $temp;
                } elseif ($index === 1) {
                    $category2 =(string) $temp;
                } elseif ($index === 2) {
                    $category3 =(string) $temp;
                }
            }
            $tag1 = $brand_read;
     
        $existing_product_id = check_product_exist('product_id', $custom_id);
      
     
     
    if ($existing_product_id)
    {
        continue;
     
    }
    else{
        
        $defaults = array(
            'post_content' => $description,
            'post_content_filtered' => '',
            'post_title' => $name,
            'post_excerpt' => $excerpt,
            'post_status' => 'publish',  //afora dimosieusi i proxeiro (publish/draft)
            'post_type' => 'product', //afora typo anartisis (post/product)
            'comment_status' => '',
            'ping_status' => '',
            'post_password' => '',
            'to_ping' => '',
            'pinged' => '',
            'post_parent' => 0,
            'menu_order' => 0,
            'guid' => '',
            'import_id' => 0,
            'context' => '',
            'post_date' => '',
            'post_date_get' => '',
            
        );
        $product_id = wp_insert_post( $defaults);
        
         $product_gallery = array();
     
    // Set the first image as the product thumbnail
        if (!empty($additionalImages)) {
            $thumbnail_id = insert_product_image($additionalImages[0], $product_id);
     
            if ($thumbnail_id) {
                set_post_thumbnail($product_id, $thumbnail_id);
            }
        }
     
        // Add remaining images to the product gallery
        for ($i = 1; $i < count($additionalImages); $i++) {
            $attachment_id = insert_product_image($additionalImages[$i], $product_id);
     
            if ($attachment_id) {
                $product_gallery[] = $attachment_id;
            }
        }
     
        
        // ta meta data den apothikeyontai se array plin eikonwn
        update_post_meta($product_id, 'product_id', $custom_id); 
        update_post_meta($product_id, '_product_image_gallery', implode(',', $product_gallery));
        update_post_meta($product_id, '_price', $price); //float
        update_post_meta($product_id, '_regular_price', $regular_price);    //float
        update_post_meta($product_id, '_sale_price', $sale_price);    //float
        update_post_meta($product_id, '_stock', $stock);    //integer
        update_post_meta($product_id, '_sku', $sku); //string
        update_post_meta($product_id, '_stock_status', $stock_status); //instock/outofstock
        update_post_meta($product_id, '_manage_stock', $stock_manage); //yes/no
        update_post_meta($product_id, '_visibility', $visibility); //visible/hidden
        update_post_meta($product_id, 'total_sales', $totalsales); //integer
        update_post_meta($product_id, '_downloadable', $downloadable); //yes/no
        update_post_meta($product_id, '_virtual', $virtual);    //yes/no
        update_post_meta($product_id, '_purchase_note', $purchase_note); //string
        update_post_meta($product_id, '_featured', $featured);  //yes/no
        update_post_meta($product_id, '_weight', $weight);  //float
        update_post_meta($product_id, '_length', $length);  //float
        update_post_meta($product_id, '_width', $width);    //float
        update_post_meta($product_id, '_height', $height);  //float
        update_post_meta($product_id, '_sale_price_dates_from', $sale_price_dates_from);
        update_post_meta($product_id, '_sale_price_dates_to', $sale_price_dates_to);
        update_post_meta($product_id, '_sold_individually', $sold_individually);    //yes/no
        update_post_meta($product_id, '_backorders', $backorders);  //yes/no
        update_post_meta($product_id, '_product_url', $link_read); //string
        
        //orismos katigoriwn proiontos
        
        wp_set_object_terms($product_id, array($category1), 'product_cat');
     
        //orismos etiketwn proiontos
        wp_set_object_terms($product_id, array($tag1), 'product_tag');
        
     
        $i3++;
     
            echo ' Προστέθηκε προϊόν: ' . $name .'<br>';
            }
        $count_records=$i3;
        }    
    }
     
    //synartisi enimerwsis timwn vasis dedomenwn
    function update_product_values_button(&$count, &$update_count){
            set_time_limit(300);
            ini_set('default_socket_timeout', 120);
            $i2=0;
            $url = 'https://www.cilek.gr/modules/linkwisexml/feed.xml';
            //$url = 'https://enterthe.shop/test.xml';
            $xml = simplexml_load_file($url);
            $current_time = new DateTime();
            $i=0;
            foreach ($xml->products->product as $product) {
                
                if ($i2>299)
                {
                    //break;
                }
                
                
                $id_read = (string) $product->id;
                $name_read = (string) $product->name;
                $description_read = (string) $product->description;
                $link_read = (string) $product->link;
                $initialPrice_read = (float)$product->initialPrice;
                $discount_read = (float)$product->discount;
                $price_read = (float)$product->price;
                $availability_read = (string)$product->availability;
                $sku_read= (string) $product->mpn;
                if ($availability_read == 'Άμεσα διαθέσιμο')
                {
                    $availability_read='instock';
                }
                else
                {
                    $availability_read='onbackorder';
                }
                $existing_product_id = check_product_exist('product_id', $id_read);
     
                if ($existing_product_id)
                {
                    
                    $old_product = wc_get_product($existing_product_id); //prosthiki string se idi yparxon id
     
                    
                    $old_product_date = $old_product->get_date_modified();
                    {
                        if ($old_product_date) {
                            $time_diff = $current_time->diff($old_product_date);
                        
                            // xroniki dikleida gia paraleipsi prosfata enimerwmenwn
                            if ($time_diff->i >= 0) 
                            {
                                $old_product_link = get_post_meta($existing_product_id, '_product_url', true);
                                {
                                    if (!(is_int(strpos($old_product_link, 'cilek.gr'))))
                                    {
                                        continue;
                                    }
                                    
                                }
                                $old_product_title = (string)$old_product->get_name();
                                $old_product_description = (string)$old_product->get_description();
                                $old_product_regular_price = (float)$old_product->get_regular_price();
                                $old_product_price = (float)$old_product->get_price();
                                $old_product_sale_price = (float)$old_product->get_sale_price();
                                $old_product_sku = (string)$old_product->get_sku();
                                $old_product_availability = (string)$old_product->get_stock_status();
                                $check_change=0;
                                // enimerwsi diaforetikwn stoixeiwn
                                if ($old_product_title!=$name_read)
                                {
                                    $old_product->set_name((string)$name_read);
                                    $check_change++;
                                }
                                if ($old_product_description!=$description_read)
                                {
                                    $old_product->set_description((string)$description_read);
                                    $check_change++;
                                }
                                if ($old_product_regular_price!=$initialPrice_read)
                                {
                                    //$old_product->set_regular_price($initialPrice_read);
                                    update_post_meta($existing_product_id, '_regular_price', $initialPrice_read);    //float
                                    $check_change++;
                                }
                                if ($old_product_sale_price!=$discount_read)
                                {
                                    //$old_product->set_sale_price($initialPrice_read-$discount_read);
                                    update_post_meta($existing_product_id, '_sale_price', $discount_read);    //float
                                    $check_change++;
                                }
                                if ($old_product_price!=$price_read)
                                {
                                    //$old_product->set_price($initialPrice_read-$discount_read);
                                    update_post_meta($existing_product_id, '_price', $price_read); //float
                                    $check_change++;
                                }
                                if ($old_product_sku!=$sku_read)
                                {
                                    $old_product->set_sku((string)$sku_read);
                                    $check_change++;
                                }
                                if ($old_product_availability!=$availability_read)
                                {
                                    update_post_meta($existing_product_id, '_stock_status', $availability_read); //instock/outofstock
                                    $check_change++;
                                }
     
                                // Save to database (Note: You might want to clear cache of your page and then reload, if it still doesn't show up go to the product page and check there.)
                                $old_product->save();
                               if ($check_change>0)
                               {
                                     echo 'Επιβήθηκαν αλλαγές σε: ' . $old_product_title . '<br>';
                                     $i++;
                                     $update_count=$i;
                                     
                                     $defaults = array(
                                    'ID' => $existing_product_id,
                                    'post_content' => $description_read,
                                    'post_content_filtered' => '',
                                    'post_title' => $name_read,
                                    'post_excerpt' => '',
                                    'post_status' => 'publish',  //afora dimosieusi i proxeiro (publish/draft)
                                    'post_type' => 'product', //afora typo anartisis (post/product)
                                    'comment_status' => '',
                                    'ping_status' => '',
                                    'post_password' => '',
                                    'to_ping' => '',
                                    'pinged' => '',
                                    'post_parent' => 0,
                                    'menu_order' => 0,
                                    'guid' => '',
                                    'import_id' => 0,
                                    'context' => '',
                                    'post_date' => '',
                                    'post_date_get' => '',
                                    
                                );
                                
                                wp_update_post( $defaults);
                                     
     
                               }
                                
                                
                                
                            }
                                $i2++;
                                $count=$i2;
                        }
                        else
                        {
                            continue;
                        }
                            
                    }
                    
                    
                }
                else
                {
                    continue;
                }
     
            }
        }
        
     
    // Schedule the functions to run at specific intervals
    function schedule_cilek_sync() {
        if (!wp_next_scheduled('insert_product_event')) {
            wp_schedule_event(time(), 'daily', 'insert_product_event');
        }
     
        if (!wp_next_scheduled('remove_unknown_records_event')) {
            wp_schedule_event(time(), 'hourly', 'remove_unknown_records_event');
        }
     
        if (!wp_next_scheduled('update_product_values_event')) {
            wp_schedule_event(time(), 'hourly', 'update_product_values_event');
        }
    }
     
    add_action('wp', 'schedule_cilek_sync');
     
    // Hook the functions to their corresponding scheduled events
    add_action('insert_product_event', 'insert_product');
    add_action('remove_unknown_records_event', 'remove_unknown_records');
    add_action('update_product_values_event', 'update_product_values');
