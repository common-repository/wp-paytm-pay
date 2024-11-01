<div class="wrap">
<?php 
	global $wpdb;
	echo "<h2>" . __( 'Paytm Donation Details' );
	$page_handle = 'wp_paytm_donation';
?>  
</div>

<?php
if(!class_exists('WP_List_Table')){ 
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if(isset($_GET['saved']) && $_GET['saved'] == true)
{
	echo "<h3 style='color:#35a00a;'>Record Deleted Successfully</h3>";
}

if(isset($_GET['id']) && isset($_GET['action']) && $_GET['id'] > 0 && $_GET['action'] == 'delete')
{
	$id = $_GET['id'];
	$wpdb->query(" DELETE FROM ".$wpdb->prefix . "paytm_donation WHERE id = $id ");
	$page = $_GET['page'];
	echo "<script type='text/javascript'>document.location='admin.php?page=".$page."&saved=true';</script>";
}

class wp_paytm_pay_List_Table extends WP_List_Table 
{
	function __construct() {
		parent::__construct( array(
			'singular'=> 'Paytm Payment Details', //Singular label
			'plural' => 'Paytm Payment Details', //plural label, also this well be one of the table css class
			'ajax'	=> false //We won't support Ajax for this table
		) );
	}
	
	function get_columns() {
		return $columns= array(
			'id'=>__('ID'),
			'order_id'=>__('Order Id'),
			'name'=>__('Name'),
			'phone'=>__('Phone'),
			'email'=>__('Email'),
			'address'=>__('Address'), 
			'city'=>__('City'),
			'state'=>__('State'),
			'country'=>__('Country'),
			'zip'=>__('Zipcode'),
			'amount'=>__('Donation'),
            'pan_no'=>__('PAN Card'),
			'date'=>__('Date'),
			'payment_status'=>__('Payment Status'),
		);
	}
	
	function get_sortable_columns() {
        $sortable_columns = array(
            'id' => array('id',false),
            'name' => array('name',false), 
            'date' => array('date',false)
        );
        return $sortable_columns;
    }
	
	function prepare_items() 
	{
        global $wpdb, $_wp_column_headers;
        $screen = get_current_screen();
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();               
        $this->_column_headers = array($columns, $hidden, $sortable);    

        $table_data = $wpdb->prefix."paytm_donation";
        $query = "SELECT * FROM $table_data";
	
        /* -- Ordering parameters -- */
		//Parameters that are going to be used to order the result
		$orderby = !empty($_GET["orderby"]) ? $_GET["orderby"] : 'date';
		$order = !empty($_GET["order"]) ? $_GET["order"] : 'DESC';
		if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }

	
        /* -- Pagination parameters -- */
        //Number of elements in your table?
        $totalitems = $wpdb->query($query); //return the total number of affected rows

        //How many to display per page?
        $perpage = 10;
        //Which page is this?
        $paged = !empty($_GET["paged"]) ? $_GET["paged"] : '';
        //Page Number
        if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
        //How many pages do we have in total?
        $totalpages = ceil($totalitems/$perpage);
	    
	    //adjust the query to take pagination into account
	    if(!empty($paged) && !empty($perpage)){
		   $offset=($paged-1)*$perpage;
			$query.=' LIMIT '.(int)$offset.','.(int)$perpage;
	    }
        /* -- Register the pagination -- */
        $this->set_pagination_args( array(
                "total_items" => $totalitems,
                "total_pages" => $totalpages,
                "per_page" => $perpage,
        ) );
        //The pagination links are automatically built according to those parameters

        /* -- Register the Columns -- */
        $columns = $this->get_columns();
        $_wp_column_headers[$screen->id]=$columns;

        /* -- Fetch the items -- */
        $data = $wpdb->get_results($query, ARRAY_A);

        $this->items = $data;

        return count($this->items);
        die();	
	}

	function column_id($item)  //name of column on which below display edit and delete button
	{
		$actions = array(
			'delete' => sprintf('<a href="?page=%s&action=%s&id=%s">Delete</a>',$_REQUEST['page'],'delete',$item['id']),
		);
		 
		return sprintf('%1$s %2$s', $item['id'], $this->row_actions($actions));
    }
	
	function column_default( $item, $column_name )
	{
		switch( $column_name ) {
			case 'id':
			case 'order_id':
			case 'name':
			case 'phone':
			case 'email':
			case 'address':
			case 'city':
			case 'state':
			case 'country':
			case 'zip':
			case 'amount':
	        case 'pan_no':    
			case 'date':
			case 'payment_status':
			return $item[$column_name];
			default:
		}
	}
} //class 

$wp_list_table = new wp_paytm_pay_List_Table();
$wp_list_table->prepare_items();
$TotalListRecord = $wp_list_table->prepare_items();
$wp_list_table->display();  
?>