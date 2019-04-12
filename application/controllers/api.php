<?php
   
require APPPATH . 'libraries/REST_Controller.php';
     
class Api extends REST_Controller {
    
      /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function __construct() {
       parent::__construct();
       $this->load->database();
       $this->load->model("base_model");
    }
       

    /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function index_get($method = null,$id=0)
    {   
        $data = [];
        switch ($method) {
            case 'organizations':
                return $this->organizations();
                break;
            case 'offices':
                return $this->offices();
                break;
            case 'sales_report':
                return $this->sales_report($id);
                break;
            default:
                $data = ["message"=>"Invalid Request"];
                $this->response($data, REST_Controller::HTTP_BAD_REQUEST);
                break;
        }
    }

    private function organizations(){
        #get the president first 
        $presidents = $this->base_model->getEmployee('President');
        foreach ($presidents as $key => $president) {
            $unders = $presidents[$key]['employeeUnder'] = $this->base_model->getEmployeeUnder($president["employeeNumber"]);
            if(!empty($unders)){
                foreach ($unders as $underkey => $under) {
                    $undersUnder = $presidents[$key]['employeeUnder'][$underkey]['employeeUnder'] = $this->base_model->getEmployeeUnder($under["employeeNumber"]);
                    foreach ($undersUnder as $undersunderkey => $uu) {
                       $presidents[$key]['employeeUnder'][$underkey]['employeeUnder'][$undersunderkey]['employeeUnder'] = $this->base_model->getEmployeeUnder($uu["employeeNumber"]);
                    }
                }
            }
        }

        $this->response($presidents, REST_Controller::HTTP_OK);
    }

    private function offices(){
        $offices = $this->base_model->getOffices();
        foreach ($offices as $key => $office) {
            $offices[$key]['employees'] = $this->base_model->getOfficeEmployees($office['officeCode']);
        }
        $this->response($offices, REST_Controller::HTTP_OK);
    }

    private function sales_report($employeeNumber = null){
        $employees = $this->base_model->getEmployeeDetails($employeeNumber);
        foreach ($employees as $key => $employee) {
           $customerOrders = $this->base_model->getCustomersOrdersByEmployeeNumber($employee['employeeNumber']);
            $prodcutLines = [];
            $total_employee_commision = $total_employee_commission = 0;
            $total_employee_sales = $total_employee_commission = 0;
            foreach ($customerOrders as $ckey => $order){
                $total_commisions = $total_sales = $commission = $total_quantity = 0;
                $prodcutLines[$order['productLine']]['productLines'] = $order['productLine'];
                $prodcutLines[$order['productLine']]['textDescription'] = $order['textDescription'];
                # get commission
                $ratio = explode(":", $order['productScale']);
                $orderPrice = round($order['sale'],2);
                $commission =  round($orderPrice / $ratio[1],2);

                $total_commisions += $commission;
                $total_quantity += $order['quantityOrdered'];
                $total_sales += $orderPrice;
                if(isset($prodcutLines[$order['productLine']]['commission'])&&isset($prodcutLines[$order['productLine']]['quantity'])&&isset($prodcutLines[$order['productLine']]['sales'])){
                    $prodcutLines[$order['productLine']]['commission'] += $total_commisions;
                    $prodcutLines[$order['productLine']]['quantity'] += $total_quantity;
                    $prodcutLines[$order['productLine']]['sales'] += $total_sales;
                }else{
                    $prodcutLines[$order['productLine']]['commission'] = $total_commisions;
                    $prodcutLines[$order['productLine']]['quantity'] = $total_quantity;
                    $prodcutLines[$order['productLine']]['sales'] = $total_sales;
                }

                if(!isset($prodcutLines[$order['productLine']]['products'])){
                    $prodcutLines[$order['productLine']]['products'] = [];
                }
                # get array key
                $array_key = array_search($order["productCode"], array_column($prodcutLines[$order['productLine']]['products'], 'productCode')); 
                if(empty($array_key)){
                    $product = array(
                        "productCode" => $order['productCode'],
                        "productName" => $order['productName'],
                        "quantity" => $order['quantityInStock'],
                        "sales" => $order['sale'],
                        "numberOfCustomerBought" => $order['quantityOrdered'],
                    );
                    array_push($prodcutLines[$order['productLine']]['products'], $product);
                }else{
                    $prodcutLines[$order['productLine']]['products'][$array_key]["quantity"] += $order['quantityInStock']; 
                    $prodcutLines[$order['productLine']]['products'][$array_key]["sales"] += $order['sale']; 
                    $prodcutLines[$order['productLine']]['products'][$array_key]["numberOfCustomerBought"] += $order['quantityOrdered']; 
                }

                # add total employe sales and commision 
                $total_employee_commision += $total_commisions;
                $total_employee_sales += $total_sales;
            }
            $employees[$key]['totalCommision'] = $total_employee_commision;
            $employees[$key]['totalSales'] = $total_employee_sales;
            $employees[$key]['productLines'] = array_values($prodcutLines);
        }

        $this->response($employees, REST_Controller::HTTP_OK);
    }

    /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function index_post()
    {
        $input = $this->input->post();
        $this->db->insert('items',$input);
     
        $this->response(['Item created successfully.'], REST_Controller::HTTP_OK);
    } 
     
    /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function index_put($id)
    {
        $input = $this->put();
        $this->db->update('items', $input, array('id'=>$id));
     
        $this->response(['Item updated successfully.'], REST_Controller::HTTP_OK);
    }
     
    /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function index_delete($id)
    {
        $this->db->delete('items', array('id'=>$id));
       
        $this->response(['Item deleted successfully.'], REST_Controller::HTTP_OK);
    }
        
}